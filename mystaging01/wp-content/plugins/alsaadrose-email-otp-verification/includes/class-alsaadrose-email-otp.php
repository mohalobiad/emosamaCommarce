<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Alsaadrose_Email_Otp {
    const OTP_LENGTH           = 6;
    const OTP_EXPIRATION       = 15 * MINUTE_IN_SECONDS;
    const MAX_ATTEMPTS         = 5;
    const RESEND_COOLDOWN      = 5 * MINUTE_IN_SECONDS;
    const RESEND_MAX_PER_DAY   = 5;

    const META_VERIFIED        = 'alsaadrose_email_verified';
    const META_OTP_HASH        = 'alsaadrose_email_otp_hash';
    const META_OTP_EXPIRES     = 'alsaadrose_email_otp_expires';
    const META_OTP_ATTEMPTS    = 'alsaadrose_email_otp_attempts';
    const META_OTP_LAST_SENT   = 'alsaadrose_email_otp_last_sent';
    const META_OTP_RESENDS     = 'alsaadrose_email_otp_resend_count';
    const META_OTP_RESEND_DAY  = 'alsaadrose_email_otp_resend_day';
    const META_OTP_TOKEN       = 'alsaadrose_email_otp_token';

    const OPTION_PAGE_ID       = 'alsaadrose_email_otp_page_id';

    protected static $instance = null;
    protected $pending_user_context = null;
    protected $pending_redirect_url = '';
    protected $form_messages = array();
    protected $form_errors   = array();
    protected $current_token = '';

    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
    }

    public static function activate() {
        $page_id = (int) get_option( self::OPTION_PAGE_ID );

        if ( $page_id && get_post( $page_id ) ) {
            return;
        }

        $page = get_page_by_path( 'verify-account' );

        if ( ! $page ) {
            $page_id = wp_insert_post(
                array(
                    'post_title'   => __( 'Verify Your Account', 'alsaadrose-email-otp' ),
                    'post_name'    => 'verify-account',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[alsaadrose_email_otp_verification]',
                )
            );
        } else {
            $page_id = $page->ID;

            if ( ! has_shortcode( $page->post_content, 'alsaadrose_email_otp_verification' ) ) {
                wp_update_post(
                    array(
                        'ID'           => $page_id,
                        'post_content' => $page->post_content . "\n[alsaadrose_email_otp_verification]",
                    )
                );
            }
        }

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( self::OPTION_PAGE_ID, $page_id );
        }
    }

    protected function hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'woocommerce_created_customer', array( $this, 'handle_customer_created' ), 10, 3 );
        add_filter( 'woocommerce_registration_auth_new_customer', array( $this, 'prevent_auto_login' ), 10, 2 );
        add_filter( 'woocommerce_registration_redirect', array( $this, 'redirect_to_verification_page' ), 10, 2 );
        add_filter( 'authenticate', array( $this, 'maybe_block_unverified_login' ), 30, 3 );
        add_filter( 'woocommerce_email_enabled_customer_new_account', array( $this, 'maybe_disable_customer_new_account_email' ), 10, 3 );
        add_action( 'template_redirect', array( $this, 'handle_verification_requests' ), 1 );
        add_action( 'template_redirect', array( $this, 'maybe_force_reset_form_view' ), 20 );
        add_action( 'template_redirect', array( $this, 'maybe_do_post_verification_redirect' ), 99 );
        add_filter( 'woocommerce_reset_password_link', array( $this, 'filter_reset_password_link' ), 10, 2 );
        add_filter( 'lostpassword_url', array( $this, 'filter_lostpassword_url' ), 20, 2 );
        add_shortcode( 'alsaadrose_email_otp_verification', array( $this, 'render_shortcode' ) );
    }

    public function maybe_disable_customer_new_account_email( $enabled, $user, $email ) {
        if ( ! $enabled || ! $user instanceof WP_User ) {
            return $enabled;
        }

        $verified = (int) get_user_meta( $user->ID, self::META_VERIFIED, true );

        if ( $verified ) {
            return $enabled;
        }

        return false;
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'alsaadrose-email-otp', false, dirname( plugin_basename( ALSAADROSE_EMAIL_OTP_PLUGIN_FILE ) ) . '/languages' );
    }

    protected function get_wc_session() {
        if ( function_exists( 'WC' ) && isset( WC()->session ) ) {
            return WC()->session;
        }

        return null;
    }

    public function handle_customer_created( $customer_id, $new_customer_data = array(), $password_generated = false ) {
        if ( is_admin() && ! wp_doing_ajax() ) {
            update_user_meta( $customer_id, self::META_VERIFIED, 1 );
            return;
        }

        update_user_meta( $customer_id, self::META_VERIFIED, 0 );

        $token = $this->ensure_token_for_user( $customer_id );
        $otp   = $this->generate_and_store_otp( $customer_id, true );

        $this->send_verification_email( $customer_id, $otp, $token );

        $session = $this->get_wc_session();
        if ( $session ) {
            $session->set(
                'alsaadrose_pending_verification',
                array(
                    'user_id' => $customer_id,
                    'token'   => $token,
                )
            );
        } else {
            $this->pending_user_context = array(
                'user_id' => $customer_id,
                'token'   => $token,
            );
        }
    }

    protected function ensure_token_for_user( $user_id ) {
        $token = get_user_meta( $user_id, self::META_OTP_TOKEN, true );

        if ( empty( $token ) ) {
            $token = wp_generate_password( 32, false );
            update_user_meta( $user_id, self::META_OTP_TOKEN, $token );
        }

        return $token;
    }

    protected function generate_and_store_otp( $user_id, $is_initial = false ) {
        $max = (int) pow( 10, self::OTP_LENGTH ) - 1;
        $otp = str_pad( (string) wp_rand( 0, $max ), self::OTP_LENGTH, '0', STR_PAD_LEFT );

        update_user_meta( $user_id, self::META_OTP_HASH, password_hash( $otp, PASSWORD_DEFAULT ) );
        update_user_meta( $user_id, self::META_OTP_EXPIRES, time() + self::OTP_EXPIRATION );
        update_user_meta( $user_id, self::META_OTP_ATTEMPTS, 0 );
        update_user_meta( $user_id, self::META_OTP_LAST_SENT, time() );

        if ( $is_initial ) {
            update_user_meta( $user_id, self::META_OTP_RESENDS, 0 );
            update_user_meta( $user_id, self::META_OTP_RESEND_DAY, gmdate( 'Y-m-d' ) );
        } elseif ( ! get_user_meta( $user_id, self::META_OTP_RESEND_DAY, true ) ) {
            update_user_meta( $user_id, self::META_OTP_RESEND_DAY, gmdate( 'Y-m-d' ) );
        }

        return $otp;
    }

    protected function send_verification_email( $user_id, $otp, $token ) {
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return;
        }

        $verification_link = $this->get_verification_page_url( $token );

        $subject = sprintf( __( 'Verify your %s account', 'alsaadrose-email-otp' ), get_bloginfo( 'name' ) );

        $site_name = get_bloginfo( 'name' );

        $body  = '<html><body style="font-family: Arial, sans-serif; color: #222; background-color: #f6f6f6; padding: 20px;">';
        $body .= '<table width="100%" cellspacing="0" cellpadding="0" style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.08);">';
        $body .= '<tr><td style="background-color:#2d3748; padding: 24px 32px; text-align:center;">';
        $body .= '<h1 style="margin:0; font-size:22px; color:#ffffff;">' . sprintf( __( 'Welcome to %s', 'alsaadrose-email-otp' ), esc_html( $site_name ) ) . '</h1>';
        $body .= '</td></tr>';
        $body .= '<tr><td style="padding: 30px 32px; font-size: 15px; line-height: 1.6; color:#4a5568;">';
        $body .= '<p style="margin-top:0;">' . __( 'We are excited to have you on board! To keep your account secure, please use the following verification code within the next 15 minutes:', 'alsaadrose-email-otp' ) . '</p>';
        $body .= '<div style="text-align:center; margin: 28px 0;">';
        $body .= '<span style="display:inline-block; font-size: 32px; letter-spacing: 6px; font-weight: 600; color:#1a202c; padding: 14px 26px; border-radius: 8px; background:#edf2f7;">' . esc_html( $otp ) . '</span>';
        $body .= '</div>';
        $body .= '<p>' . __( 'Enter this code on the verification page to activate your account.', 'alsaadrose-email-otp' ) . '</p>';
        $body .= '<p style="margin-bottom: 0;">' . sprintf( __( 'If the button below does not work, copy and paste this link into your browser: %s', 'alsaadrose-email-otp' ), '<br><a href="' . esc_url( $verification_link ) . '" style="color:#3182ce; word-break: break-all;">' . esc_html( $verification_link ) . '</a>' ) . '</p>';
        $body .= '<div style="text-align:center; margin-top: 28px;">';
        $body .= '<a href="' . esc_url( $verification_link ) . '" style="display:inline-block; background:#3182ce; color:#fff; padding: 12px 32px; border-radius: 50px; text-decoration:none; font-weight:600;">' . __( 'Verify my account', 'alsaadrose-email-otp' ) . '</a>';
        $body .= '</div>';
        $body .= '</td></tr>';
        $body .= '<tr><td style="background:#f7fafc; padding: 18px 32px; font-size: 13px; color:#718096; text-align:center;">';
        $body .= __( 'If you did not create an account, you can safely ignore this email.', 'alsaadrose-email-otp' );
        $body .= '</td></tr>';
        $body .= '</table>';
        $body .= '</body></html>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $body, $headers );
    }

    public function prevent_auto_login( $should_auth, $customer_id ) {
        if ( ! $customer_id ) {
            return $should_auth;
        }

        $verified = (int) get_user_meta( $customer_id, self::META_VERIFIED, true );

        return $verified ? $should_auth : false;
    }

    public function redirect_to_verification_page( $redirect, $user = null ) {
        $context = null;
        $session = $this->get_wc_session();

        if ( $session ) {
            $context = $session->get( 'alsaadrose_pending_verification' );
            $session->set( 'alsaadrose_pending_verification', null );
        }

        if ( ! $context ) {
            $context = $this->pending_user_context;
            $this->pending_user_context = null;
        }

        if ( empty( $context['token'] ) ) {
            return $redirect;
        }

        return $this->get_verification_page_url( $context['token'] );
    }

    protected function get_verification_page_url( $token = '' ) {
        $page_id = (int) get_option( self::OPTION_PAGE_ID );
        $url     = '';

        if ( $page_id ) {
            $url = get_permalink( $page_id );
        }

        if ( ! $url ) {
            $page = get_page_by_path( 'verify-account' );
            if ( $page ) {
                $url = get_permalink( $page );
            }
        }

        if ( ! $url ) {
            $url = home_url( '/verify-account/' );
        }

        if ( $token ) {
            $url = add_query_arg( 'otp_token', rawurlencode( $token ), $url );
        }

        return $url;
    }

    public function maybe_block_unverified_login( $user, $username, $password ) {
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        if ( $user instanceof WP_User ) {
            $verified = (int) get_user_meta( $user->ID, self::META_VERIFIED, true );

            if ( ! $verified ) {
                $token = $this->ensure_token_for_user( $user->ID );
                $link  = esc_url( $this->get_verification_page_url( $token ) );
                $message = sprintf(
                    __( 'Your account is not verified yet. Please check your email for the code or visit <a href="%s">the verification page</a>.', 'alsaadrose-email-otp' ),
                    $link
                );

                return new WP_Error( 'alsaadrose_email_not_verified', wp_kses_post( $message ) );
            }
        }

        return $user;
    }

    public function render_shortcode() {
        $token = $this->current_token;

        if ( empty( $token ) && isset( $_GET['otp_token'] ) ) {
            $token = sanitize_text_field( wp_unslash( $_GET['otp_token'] ) );
        }

        $messages = $this->form_messages;
        $errors   = $this->form_errors;

        if ( empty( $token ) ) {
            $errors[] = __( 'Please use the link from your email to open this page so we can identify your account.', 'alsaadrose-email-otp' );
        }

        ob_start();
        ?>
        <div class="alsaadrose-email-otp-form">
            <?php foreach ( $messages as $message ) : ?>
                <div class="alsaadrose-email-otp-message"><?php echo wp_kses_post( $message ); ?></div>
            <?php endforeach; ?>

            <?php foreach ( $errors as $error ) : ?>
                <div class="alsaadrose-email-otp-error"><?php echo wp_kses_post( $error ); ?></div>
            <?php endforeach; ?>

            <form method="post" class="alsaadrose-email-otp-verify-form">
                <label for="alsaadrose-otp-code"><?php esc_html_e( 'Enter verification code', 'alsaadrose-email-otp' ); ?></label>
                <input type="text" id="alsaadrose-otp-code" name="alsaadrose_otp_code" pattern="\d{6}" maxlength="6" required value="" />
                <input type="hidden" name="alsaadrose_otp_token" value="<?php echo esc_attr( $token ); ?>" />
                <?php wp_nonce_field( 'alsaadrose_verify_otp', 'alsaadrose_otp_verify_nonce' ); ?>
                <button type="submit" name="alsaadrose_otp_action" value="verify"><?php esc_html_e( 'Verify', 'alsaadrose-email-otp' ); ?></button>
            </form>

            <form method="post" class="alsaadrose-email-otp-resend-form">
                <input type="hidden" name="alsaadrose_otp_token" value="<?php echo esc_attr( $token ); ?>" />
                <?php wp_nonce_field( 'alsaadrose_resend_otp', 'alsaadrose_otp_resend_nonce' ); ?>
                <button type="submit" name="alsaadrose_otp_action" value="resend"><?php esc_html_e( 'Resend Code', 'alsaadrose-email-otp' ); ?></button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    protected function get_user_id_by_token( $token ) {
        if ( empty( $token ) ) {
            return 0;
        }

        $users = get_users(
            array(
                'meta_key'     => self::META_OTP_TOKEN,
                'meta_value'   => $token,
                'meta_compare' => '=',
                'number'       => 1,
                'fields'       => 'ID',
            )
        );

        return $users ? (int) $users[0] : 0;
    }

    protected function attempt_verification( $token, $otp_input ) {
        $user_id = $this->get_user_id_by_token( $token );

        if ( ! $user_id ) {
            return new WP_Error( 'alsaadrose_invalid_token', __( 'Verification link is invalid. Please use the link from your email.', 'alsaadrose-email-otp' ) );
        }

        $verified = (int) get_user_meta( $user_id, self::META_VERIFIED, true );
        if ( $verified ) {
            return new WP_Error( 'alsaadrose_already_verified', __( 'This account is already verified. Please log in.', 'alsaadrose-email-otp' ) );
        }

        if ( ! preg_match( '/^\d{' . self::OTP_LENGTH . '}$/', $otp_input ) ) {
            return new WP_Error( 'alsaadrose_invalid_format', __( 'Please enter the 6-digit code from the email.', 'alsaadrose-email-otp' ) );
        }

        $hash     = get_user_meta( $user_id, self::META_OTP_HASH, true );
        $expires  = (int) get_user_meta( $user_id, self::META_OTP_EXPIRES, true );
        $attempts = (int) get_user_meta( $user_id, self::META_OTP_ATTEMPTS, true );

        if ( empty( $hash ) ) {
            return new WP_Error( 'alsaadrose_missing_otp', __( 'Please request a new verification code.', 'alsaadrose-email-otp' ) );
        }

        if ( $attempts >= self::MAX_ATTEMPTS ) {
            return new WP_Error( 'alsaadrose_too_many_attempts', __( 'Too many attempts. Please request a new code.', 'alsaadrose-email-otp' ) );
        }

        if ( time() > $expires ) {
            return new WP_Error( 'alsaadrose_expired', __( 'The verification code has expired. Please request a new one.', 'alsaadrose-email-otp' ) );
        }

        if ( ! password_verify( $otp_input, $hash ) ) {
            $attempts++;
            update_user_meta( $user_id, self::META_OTP_ATTEMPTS, $attempts );

            if ( $attempts >= self::MAX_ATTEMPTS ) {
                return new WP_Error( 'alsaadrose_too_many_attempts', __( 'Too many attempts. Please request a new code.', 'alsaadrose-email-otp' ) );
            }

            return new WP_Error( 'alsaadrose_wrong_code', __( 'The code you entered is incorrect.', 'alsaadrose-email-otp' ) );
        }

        update_user_meta( $user_id, self::META_VERIFIED, 1 );
        delete_user_meta( $user_id, self::META_OTP_HASH );
        delete_user_meta( $user_id, self::META_OTP_EXPIRES );
        delete_user_meta( $user_id, self::META_OTP_ATTEMPTS );
        delete_user_meta( $user_id, self::META_OTP_LAST_SENT );
        delete_user_meta( $user_id, self::META_OTP_RESENDS );
        delete_user_meta( $user_id, self::META_OTP_RESEND_DAY );
        delete_user_meta( $user_id, self::META_OTP_TOKEN );

        $redirect = $this->get_lost_password_url();

        return array(
            'redirect' => $redirect,
        );
    }

    protected function get_lost_password_url() {
        if ( function_exists( 'wc_get_endpoint_url' ) && function_exists( 'wc_get_page_permalink' ) ) {
            $myaccount = wc_get_page_permalink( 'myaccount' );
            if ( $myaccount ) {
                $url = wc_get_endpoint_url( 'lost-password', '', $myaccount );
                return $this->append_reset_form_query_args( $url );
            }
        }

        return $this->append_reset_form_query_args( wp_lostpassword_url() );
    }

    protected function append_reset_form_query_args( $url ) {
        if ( empty( $url ) ) {
            return $url;
        }

        $fragment = '';

        if ( false !== strpos( $url, '#' ) ) {
            list( $url, $fragment ) = explode( '#', $url, 2 );
            $fragment = '#' . $fragment;
        }

        $url = remove_query_arg( 'show-reset-form', $url );
        $url = add_query_arg( 'show-reset-form', 'true', $url );

        $has_action = false;
        $parts      = wp_parse_url( $url );

        if ( isset( $parts['query'] ) ) {
            $query_params = array();
            parse_str( $parts['query'], $query_params );
            $has_action = array_key_exists( 'action', $query_params );
        }

        if ( ! $has_action ) {
            $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'action';
        }

        return $url . $fragment;
    }

    protected function queue_post_verification_redirect( $url ) {
        if ( empty( $url ) ) {
            return;
        }

        $this->pending_redirect_url = $url;

        $session = $this->get_wc_session();
        if ( $session ) {
            $session->set( 'alsaadrose_email_otp_redirect', $url );
        }
    }

    public function handle_verification_requests() {
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

        if ( 'POST' !== $request_method || empty( $_POST['alsaadrose_otp_action'] ) ) {
            return;
        }

        $this->current_token = isset( $_POST['alsaadrose_otp_token'] ) ? sanitize_text_field( wp_unslash( $_POST['alsaadrose_otp_token'] ) ) : '';
        $action              = sanitize_text_field( wp_unslash( $_POST['alsaadrose_otp_action'] ) );

        if ( 'verify' === $action ) {
            if ( empty( $_POST['alsaadrose_otp_verify_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['alsaadrose_otp_verify_nonce'] ) ), 'alsaadrose_verify_otp' ) ) {
                $this->form_errors[] = __( 'Security check failed. Please try again.', 'alsaadrose-email-otp' );
                return;
            }

            $otp_input = isset( $_POST['alsaadrose_otp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['alsaadrose_otp_code'] ) ) : '';
            $result    = $this->attempt_verification( $this->current_token, $otp_input );

            if ( is_wp_error( $result ) ) {
                $this->form_errors[] = $result->get_error_message();
                return;
            }

            $this->form_messages[] = __( 'Verification successful. Redirecting you to set your password…', 'alsaadrose-email-otp' );

            if ( isset( $result['redirect'] ) ) {
                $this->queue_post_verification_redirect( $result['redirect'] );
            }
        } elseif ( 'resend' === $action ) {
            if ( empty( $_POST['alsaadrose_otp_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['alsaadrose_otp_resend_nonce'] ) ), 'alsaadrose_resend_otp' ) ) {
                $this->form_errors[] = __( 'Security check failed. Please try again.', 'alsaadrose-email-otp' );
                return;
            }

            $result = $this->handle_resend( $this->current_token );

            if ( is_wp_error( $result ) ) {
                $this->form_errors[] = $result->get_error_message();
            } else {
                $this->form_messages[] = __( 'A new code has been sent to your email.', 'alsaadrose-email-otp' );
            }
        }
    }

    public function maybe_do_post_verification_redirect() {
        $redirect = $this->pending_redirect_url;

        $session = $this->get_wc_session();
        if ( $session ) {
            $session_redirect = $session->get( 'alsaadrose_email_otp_redirect' );
            if ( $session_redirect ) {
                $redirect = $session_redirect;
                $session->set( 'alsaadrose_email_otp_redirect', null );
            }
        }

        if ( $redirect ) {
            wp_safe_redirect( $redirect );
            exit;
        }
    }

    public function maybe_force_reset_form_view() {
        if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'lost-password' ) ) {
            return;
        }

        $key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( empty( $key ) || empty( $login ) ) {
            return;
        }

        if ( isset( $_GET['show-reset-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( ! function_exists( 'wc_get_current_url' ) ) {
            return;
        }

        $current_url = esc_url_raw( wc_get_current_url() );
        $redirect    = $this->append_reset_form_query_args( $current_url );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Ensure WooCommerce reset-password links (emails + set-password CTA) land on the Woodmart reset form.
     */
    public function filter_reset_password_link( $url, $user ) {
        return $this->append_reset_form_query_args( $url );
    }

    /**
     * Append show-reset-form/action args whenever WordPress builds the My Account lost-password URL.
     */
    public function filter_lostpassword_url( $url, $redirect ) {
        if ( ! $this->is_myaccount_lost_password_url( $url ) ) {
            return $url;
        }

        return $this->append_reset_form_query_args( $url );
    }

    protected function is_myaccount_lost_password_url( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        if ( ! function_exists( 'wc_get_page_permalink' ) || ! function_exists( 'wc_get_endpoint_url' ) ) {
            return false;
        }

        $account_page = wc_get_page_permalink( 'myaccount' );

        if ( empty( $account_page ) ) {
            return false;
        }

        $endpoint_path = wp_parse_url( wc_get_endpoint_url( 'lost-password', '', $account_page ), PHP_URL_PATH );
        $url_path      = wp_parse_url( $url, PHP_URL_PATH );

        if ( empty( $endpoint_path ) || empty( $url_path ) ) {
            return false;
        }

        return untrailingslashit( $endpoint_path ) === untrailingslashit( $url_path );
    }

    protected function handle_resend( $token ) {
        $user_id = $this->get_user_id_by_token( $token );

        if ( ! $user_id ) {
            return new WP_Error( 'alsaadrose_invalid_token', __( 'Verification link is invalid. Please use the link from your email.', 'alsaadrose-email-otp' ) );
        }

        $verified = (int) get_user_meta( $user_id, self::META_VERIFIED, true );
        if ( $verified ) {
            return new WP_Error( 'alsaadrose_already_verified', __( 'This account is already verified. Please log in.', 'alsaadrose-email-otp' ) );
        }

        $last_sent = (int) get_user_meta( $user_id, self::META_OTP_LAST_SENT, true );
        if ( $last_sent && ( time() - $last_sent ) < self::RESEND_COOLDOWN ) {
            return new WP_Error( 'alsaadrose_resend_cooldown', __( 'Please wait a few minutes before requesting a new code.', 'alsaadrose-email-otp' ) );
        }

        $today       = gmdate( 'Y-m-d' );
        $stored_day  = get_user_meta( $user_id, self::META_OTP_RESEND_DAY, true );
        $resend_count = (int) get_user_meta( $user_id, self::META_OTP_RESENDS, true );

        if ( $stored_day !== $today ) {
            $resend_count = 0;
            update_user_meta( $user_id, self::META_OTP_RESEND_DAY, $today );
        }

        if ( $resend_count >= self::RESEND_MAX_PER_DAY ) {
            return new WP_Error( 'alsaadrose_resend_limit', __( 'You have reached the maximum number of resend attempts today.', 'alsaadrose-email-otp' ) );
        }

        $otp = $this->generate_and_store_otp( $user_id );

        $resend_count++;
        update_user_meta( $user_id, self::META_OTP_RESENDS, $resend_count );

        $token = $this->ensure_token_for_user( $user_id );
        $this->send_verification_email( $user_id, $otp, $token );

        return true;
    }
}