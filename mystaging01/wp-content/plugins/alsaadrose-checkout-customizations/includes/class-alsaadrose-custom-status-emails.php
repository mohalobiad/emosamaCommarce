<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Email' ) && defined( 'WC_ABSPATH' ) ) {
    $email_class_paths = array(
        'includes/emails/class-wc-email.php',
        'includes/abstracts/abstract-wc-email.php',
    );

    foreach ( $email_class_paths as $relative_path ) {
        $candidate = trailingslashit( WC_ABSPATH ) . $relative_path;

        if ( file_exists( $candidate ) ) {
            require_once $candidate;
            break;
        }
    }
}

if ( ! class_exists( 'WC_Email' ) ) {
    return;
}

/**
 * Email sent to customers when their order is marked "On the way".
 */
class Alsaadrose_Email_On_The_Way extends WC_Email {

    /**
     * Track orders that already triggered this email during the current request.
     *
     * @var array
     */
    protected $sent_for_orders = array();

    public function __construct() {
        $this->id             = 'on_the_way_email';
        $this->title          = __( 'Order On The Way', 'alsaadrose-checkout' );
        $this->description    = __( 'This email is sent to customers when their order status changes to On the way.', 'alsaadrose-checkout' );
        $this->heading        = __( 'Your order is on the way!', 'alsaadrose-checkout' );
        $this->subject        = __( 'Your order is on the way!', 'alsaadrose-checkout' );
        $this->customer_email = true;
        $this->template_html  = 'emails/on-the-way.php';
        $this->template_plain = 'emails/plain/on-the-way.php';
        $this->template_base  = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/';
        $this->placeholders   = array(
            '{order_number}' => '',
            '{order_date}'   => '',
        );

        $this->enabled = 'yes';

        $status_slug = alsaadrose_status_without_wc_prefix( ALSAADROSE_STATUS_ON_THE_WAY );

        add_action( 'woocommerce_order_status_' . $status_slug, array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_' . $status_slug . '_notification', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();
    }

    public function get_default_subject() {
        return __( 'Your order is on the way!', 'alsaadrose-checkout' );
    }

    public function get_default_heading() {
        return __( 'Your order is on the way!', 'alsaadrose-checkout' );
    }

    public function trigger( $order_id, $order = false ) {
        if ( $order_id ) {
            $this->object = wc_get_order( $order_id );
        } elseif ( $order instanceof WC_Order ) {
            $this->object = $order;
        }

        if ( ! $this->object instanceof WC_Order ) {
            return;
        }

        $tracked_order_id = $this->object->get_id();

        if ( $tracked_order_id && isset( $this->sent_for_orders[ $tracked_order_id ] ) ) {
            return;
        }

        $this->recipient = $this->object->get_billing_email();

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        if ( $tracked_order_id ) {
            $this->sent_for_orders[ $tracked_order_id ] = true;
        }

        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );

        $this->setup_locale();
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            ),
            '',
            $this->template_base
        );
    }
}

/**
 * Email sent to customers when their order is ready for shipping.
 */
class Alsaadrose_Email_Ready_For_Shipping extends WC_Email {

    /**
     * Track orders that already triggered this email during the current request.
     *
     * @var array
     */
    protected $sent_for_orders = array();

    public function __construct() {
        $this->id             = 'ready_for_shipping_email';
        $this->title          = __( 'Order Ready For Shipping', 'alsaadrose-checkout' );
        $this->description    = __( 'This email is sent to customers when their order status changes to Ready for shipping.', 'alsaadrose-checkout' );
        $this->heading        = __( 'Your order is ready for shipping', 'alsaadrose-checkout' );
        $this->subject        = __( 'Your order is ready for shipping', 'alsaadrose-checkout' );
        $this->customer_email = true;
        $this->template_html  = 'emails/ready-for-shipping.php';
        $this->template_plain = 'emails/plain/ready-for-shipping.php';
        $this->template_base  = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/';
        $this->placeholders   = array(
            '{order_number}' => '',
            '{order_date}'   => '',
        );

        $this->enabled = 'yes';

        $status_slug      = alsaadrose_status_without_wc_prefix( ALSAADROSE_STATUS_READY_SHIPPING );
        $legacy_status    = alsaadrose_status_without_wc_prefix( ALSAADROSE_STATUS_READY_SHIPPING_LEGACY );

        add_action( 'woocommerce_order_status_' . $status_slug, array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_' . $status_slug . '_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_' . $legacy_status, array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_' . $legacy_status . '_notification', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();
    }

    public function get_default_subject() {
        return __( 'Your order is ready for shipping', 'alsaadrose-checkout' );
    }

    public function get_default_heading() {
        return __( 'Your order is ready for shipping', 'alsaadrose-checkout' );
    }

    public function trigger( $order_id, $order = false ) {
        if ( $order_id ) {
            $this->object = wc_get_order( $order_id );
        } elseif ( $order instanceof WC_Order ) {
            $this->object = $order;
        }

        if ( ! $this->object instanceof WC_Order ) {
            return;
        }

        $tracked_order_id = $this->object->get_id();

        if ( $tracked_order_id && isset( $this->sent_for_orders[ $tracked_order_id ] ) ) {
            return;
        }

        $this->recipient = $this->object->get_billing_email();

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        if ( $tracked_order_id ) {
            $this->sent_for_orders[ $tracked_order_id ] = true;
        }

        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );

        $this->setup_locale();
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            ),
            '',
            $this->template_base
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            ),
            '',
            $this->template_base
        );
    }
}

