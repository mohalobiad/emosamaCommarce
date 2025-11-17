<?php
/**
 * Plugin Name:       AlSaadrose Email OTP Verification
 * Description:       Adds an OTP-based email verification flow for WooCommerce customers.
 * Version:           1.0.0
 * Author:            AlSaadrose
 * Text Domain:       alsaadrose-email-otp
 * Domain Path:       /languages
 *
 * Summary:
 * - Files:
 *   - wp-content/plugins/alsaadrose-email-otp-verification/alsaadrose-email-otp-verification.php (main loader)
 *   - wp-content/plugins/alsaadrose-email-otp-verification/includes/class-alsaadrose-email-otp.php (OTP logic)
 * - Hooks:
 *   - plugins_loaded -> init plugin hooks
 *   - register_activation_hook -> maybe create verification page
 *   - woocommerce_created_customer -> generate OTP + email
 *   - woocommerce_registration_auth_new_customer -> block auto-login until verified
 *   - woocommerce_registration_redirect -> send user to verification page
 *   - authenticate -> block login for unverified users
 *   - woocommerce_email_enabled_customer_new_account -> suppress default customer new-account email when OTP is pending
 *   - template_redirect -> process OTP/resend submissions and run the lost-password redirect immediately after success
 *   - template_redirect -> ensure lost-password reset links append ?show-reset-form=true&action so the Woodmart template shows the password fields
 *   - shortcode [alsaadrose_email_otp_verification]
 * - Settings:
 *   - OTP expiration: 15 minutes
 *   - Max verification attempts per OTP: 5
 *   - Resend cooldown: 5 minutes
 *   - Max resends per day: 5
 *   - Prevents duplicate “Your account has been created” email; only OTP email is sent for frontend registrations.
 *   - Successful verification redirects customers to the WooCommerce lost-password endpoint so they can set their password.
 *   - Lost-password URLs always append ?show-reset-form=true&action so reset links land on the password form step used by the theme.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ALSAADROSE_EMAIL_OTP_PLUGIN_FILE' ) ) {
    define( 'ALSAADROSE_EMAIL_OTP_PLUGIN_FILE', __FILE__ );
}

define( 'ALSAADROSE_EMAIL_OTP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALSAADROSE_EMAIL_OTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ALSAADROSE_EMAIL_OTP_PLUGIN_PATH . 'includes/class-alsaadrose-email-otp.php';

register_activation_hook( __FILE__, array( 'Alsaadrose_Email_Otp', 'activate' ) );

add_action( 'plugins_loaded', array( 'Alsaadrose_Email_Otp', 'init' ) );
