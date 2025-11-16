<?php
/**
 * Plugin Name:       AlSaadrose Checkout Customizations
 * Plugin URI:        https://example.com/
 * Description:       Centralizes AlSaadrose WooCommerce checkout customizations, including a configurable Cash on Delivery fee.
 * Version:           1.1.0
 * Author:            AlSaadrose
 * Text Domain:       alsaadrose-checkout
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ALSAADROSE_STATUS_ON_THE_WAY' ) ) {
    define( 'ALSAADROSE_STATUS_ON_THE_WAY', 'wc-on-the-way' );
}

if ( ! defined( 'ALSAADROSE_STATUS_READY_SHIPPING' ) ) {
    define( 'ALSAADROSE_STATUS_READY_SHIPPING', 'wc-ready-shipping' );
}

if ( ! defined( 'ALSAADROSE_STATUS_READY_SHIPPING_LEGACY' ) ) {
    define( 'ALSAADROSE_STATUS_READY_SHIPPING_LEGACY', 'wc-ready-for-shipping' );
}

if ( ! defined( 'ALSAADROSE_STATUS_READY_SHIPPING_TRUNCATED' ) ) {
    define( 'ALSAADROSE_STATUS_READY_SHIPPING_TRUNCATED', 'wc-ready-for-shipp' );
}

/**
 * Admin notice if WooCommerce is missing.
 */
function alsaadrose_missing_woocommerce_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    if ( class_exists( 'WooCommerce' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>' .
        esc_html__( 'AlSaadrose Checkout Customizations requires WooCommerce to be installed and active.', 'alsaadrose-checkout' ) .
        '</p></div>';
}
add_action( 'admin_notices', 'alsaadrose_missing_woocommerce_notice' );

/**
 * Init all Woo hooks only if WooCommerce is active.
 */
function alsaadrose_checkout_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_filter( 'woocommerce_email_classes', 'alsaadrose_register_custom_status_emails' );
    add_filter( 'woocommerce_email_actions', 'alsaadrose_register_custom_status_email_actions' );
    add_action( 'init', 'alsaadrose_register_custom_order_statuses' );
    add_action( 'init', 'alsaadrose_register_ready_shipping_action_aliases', 11 );
    add_action( 'init', 'alsaadrose_migrate_legacy_ready_shipping_statuses', 12 );
    add_filter( 'woocommerce_register_shop_order_post_statuses', 'alsaadrose_register_shop_order_post_statuses' );
    add_filter( 'wc_order_statuses', 'alsaadrose_add_custom_order_statuses' );

    // Tab in WooCommerce settings
    add_filter( 'woocommerce_settings_tabs_array', 'alsaadrose_add_settings_tab', 50 );
    add_action( 'woocommerce_settings_tabs_alsaadrose_checkout', 'alsaadrose_render_settings_tab' );
    add_action( 'woocommerce_update_options_alsaadrose_checkout', 'alsaadrose_update_settings' );

    // Apply COD fee on cart/checkout totals
    add_action( 'woocommerce_cart_calculate_fees', 'alsaadrose_apply_cod_fee', 99 );

    // Persist and react to checkout payment method changes
    add_action( 'woocommerce_checkout_update_order_review', 'alsaadrose_maybe_store_checkout_payment_method' );
    add_action( 'wp_enqueue_scripts', 'alsaadrose_enqueue_checkout_scripts' );
}
add_action( 'plugins_loaded', 'alsaadrose_checkout_init', 20 );

/**
 * Register custom WooCommerce email classes for order status updates.
 *
 * @param array $emails Registered WooCommerce email classes.
 *
 * @return array
 */
function alsaadrose_register_custom_status_emails( $emails ) {
    foreach ( alsaadrose_load_custom_status_email_classes() as $email_id => $email_instance ) {
        $emails[ $email_id ] = $email_instance;
    }

    return $emails;
}

/**
 * Ensure our custom emails are loaded once and returned for reuse.
 *
 * @return WC_Email[]
 */
function alsaadrose_load_custom_status_email_classes() {
    static $loaded_emails = null;

    if ( null !== $loaded_emails ) {
        return $loaded_emails;
    }

    $loaded_emails = array();
    $classes_file   = __DIR__ . '/includes/class-alsaadrose-custom-status-emails.php';

    if ( file_exists( $classes_file ) ) {
        require_once $classes_file;
    }

    if ( class_exists( 'Alsaadrose_Email_On_The_Way' ) ) {
        $loaded_emails['on_the_way_email'] = new Alsaadrose_Email_On_The_Way();
    }

    if ( class_exists( 'Alsaadrose_Email_Ready_For_Shipping' ) ) {
        $loaded_emails['ready_for_shipping_email'] = new Alsaadrose_Email_Ready_For_Shipping();
    }

    return $loaded_emails;
}

/**
 * Register custom order status actions with the WooCommerce transactional mailer.
 *
 * @param array $actions Existing WooCommerce email actions.
 *
 * @return array
 */
function alsaadrose_register_custom_status_email_actions( $actions ) {
    $actions[] = 'woocommerce_order_status_on-the-way';
    $actions[] = 'woocommerce_order_status_ready-for-shipping';
    $actions[] = 'woocommerce_order_status_' . alsaadrose_status_without_wc_prefix( ALSAADROSE_STATUS_READY_SHIPPING );

    return array_values( array_unique( $actions ) );
}

/**
 * Register custom WooCommerce order statuses.
 */
function alsaadrose_register_custom_order_statuses() {
    foreach ( alsaadrose_get_custom_order_status_definitions() as $status_key => $status_args ) {
        register_post_status( $status_key, $status_args );
    }
}

/**
 * Ensure hooks fire for the legacy ready-for-shipping slug.
 */
function alsaadrose_register_ready_shipping_action_aliases() {
    $current_slug = alsaadrose_status_without_wc_prefix( ALSAADROSE_STATUS_READY_SHIPPING );

    add_action( 'woocommerce_order_status_' . $current_slug, 'alsaadrose_fire_ready_shipping_alias_action', 5, 2 );
    add_action( 'woocommerce_order_status_' . $current_slug . '_notification', 'alsaadrose_fire_ready_shipping_alias_notification', 5, 2 );
}

/**
 * Trigger the legacy action name so existing integrations keep working.
 *
 * @param int|false   $order_id Order ID.
 * @param WC_Order|false $order Order instance.
 */
function alsaadrose_fire_ready_shipping_alias_action( $order_id, $order = false ) {
    do_action( 'woocommerce_order_status_ready-for-shipping', $order_id, $order );
}

/**
 * Trigger the legacy notification action name so queued emails also fire.
 *
 * @param int|false   $order_id Order ID.
 * @param WC_Order|false $order Order instance.
 */
function alsaadrose_fire_ready_shipping_alias_notification( $order_id, $order = false ) {
    do_action( 'woocommerce_order_status_ready-for-shipping_notification', $order_id, $order );
}

/**
 * Ensure WooCommerce recognises the custom order statuses everywhere counts/filters are built.
 *
 * @param array $statuses Existing registered WooCommerce order statuses.
 *
 * @return array
 */
function alsaadrose_register_shop_order_post_statuses( $statuses ) {
    foreach ( alsaadrose_get_custom_order_status_definitions() as $status_key => $status_args ) {
        $statuses[ $status_key ] = $status_args;
    }

    return $statuses;
}

/**
 * Inject custom statuses into the WooCommerce status list.
 *
 * @param array $order_statuses
 *
 * @return array
 */
function alsaadrose_add_custom_order_statuses( $order_statuses ) {
    $updated_statuses = array();
    $definitions      = alsaadrose_get_custom_order_status_definitions();

    foreach ( $order_statuses as $status_key => $status_label ) {
        $updated_statuses[ $status_key ] = $status_label;

        if ( 'wc-processing' === $status_key ) {
            foreach ( $definitions as $custom_key => $custom_args ) {
                $updated_statuses[ $custom_key ] = isset( $custom_args['label'] ) ? $custom_args['label'] : $status_label;
            }
        }
    }

    // In case "processing" was not present, append our statuses at the end.
    foreach ( $definitions as $custom_key => $custom_args ) {
        if ( ! isset( $updated_statuses[ $custom_key ] ) && isset( $custom_args['label'] ) ) {
            $updated_statuses[ $custom_key ] = $custom_args['label'];
        }
    }

    return $updated_statuses;
}

/**
 * Central definition for custom order statuses.
 *
 * @return array
 */
function alsaadrose_get_custom_order_status_definitions() {
    return array(
        ALSAADROSE_STATUS_ON_THE_WAY => array(
            'label'                     => _x( 'On the way', 'Order status', 'alsaadrose-checkout' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'On the way <span class="count">(%s)</span>',
                'On the way <span class="count">(%s)</span>',
                'alsaadrose-checkout'
            ),
        ),
        ALSAADROSE_STATUS_READY_SHIPPING => array(
            'label'                     => _x( 'Ready for shipping', 'Order status', 'alsaadrose-checkout' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Ready for shipping <span class="count">(%s)</span>',
                'Ready for shipping <span class="count">(%s)</span>',
                'alsaadrose-checkout'
            ),
        ),
    );
}

/**
 * Convert legacy ready-for-shipping statuses that exceeded the database length.
 */
function alsaadrose_migrate_legacy_ready_shipping_statuses() {
    if ( 'yes' === get_option( 'alsaadrose_ready_shipping_status_migrated', 'no' ) ) {
        return;
    }

    global $wpdb;

    $legacy_slugs = array(
        ALSAADROSE_STATUS_READY_SHIPPING_LEGACY,
        ALSAADROSE_STATUS_READY_SHIPPING_TRUNCATED,
    );

    $placeholders = implode( ',', array_fill( 0, count( $legacy_slugs ), '%s' ) );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $updated = $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_status = %s WHERE post_type = 'shop_order' AND post_status IN ($placeholders)",
            array_merge( array( ALSAADROSE_STATUS_READY_SHIPPING ), $legacy_slugs )
        )
    );

    if ( false !== $updated ) {
        update_option( 'alsaadrose_ready_shipping_status_migrated', 'yes' );
    }
}

/**
 * Helper to remove the wc- prefix from a status slug.
 *
 * @param string $status_slug Status slug including the wc- prefix.
 *
 * @return string
 */
function alsaadrose_status_without_wc_prefix( $status_slug ) {
    return preg_replace( '/^wc-/', '', $status_slug );
}

/**
 * Add the AlSaadrose tab to WooCommerce settings.
 *
 * WooCommerce → Settings → AlSaadrose Checkout
 */
function alsaadrose_add_settings_tab( $tabs ) {
    $tabs['alsaadrose_checkout'] = __( 'AlSaadrose Checkout', 'alsaadrose-checkout' );
    return $tabs;
}

/**
 * Render fields in our custom tab.
 */
function alsaadrose_render_settings_tab() {
    woocommerce_admin_fields( alsaadrose_get_settings_definition() );
}

/**
 * Save settings when admin clicks "Save changes".
 */
function alsaadrose_update_settings() {
    woocommerce_update_options( alsaadrose_get_settings_definition() );
}

/**
 * Settings definition used by Woo helper functions.
 */
function alsaadrose_get_settings_definition() {
    return array(
        'section_title' => array(
            'name' => __( 'AlSaadrose Checkout Settings', 'alsaadrose-checkout' ),
            'type' => 'title',
            'desc' => __( 'Configure Cash on Delivery fees for your store.', 'alsaadrose-checkout' ),
            'id'   => 'alsaadrose_checkout_section_title',
        ),

        'cod_fee_enabled' => array(
            'name'     => __( 'Enable COD fee', 'alsaadrose-checkout' ),
            'type'     => 'checkbox',
            'desc'     => __( 'Enable charging an additional fee when customers select Cash on Delivery.', 'alsaadrose-checkout' ),
            'id'       => 'alsaad_cod_fee_enabled',
            'default'  => 'no', // WooCommerce يتكفل يحوّلها yes/no
            'desc_tip' => true,
        ),

        'cod_fee_amount' => array(
            'name'     => __( 'Cash on Delivery fee amount (in store currency)', 'alsaadrose-checkout' ),
            'type'     => 'number',
            'id'       => 'alsaad_cod_fee_amount',
            'default'  => 10,
            'desc'     => __( 'Set the fee that will be added to orders paid using Cash on Delivery.', 'alsaadrose-checkout' ),
            'desc_tip' => true,
            'custom_attributes' => array(
                'step' => '0.01',
                'min'  => '0',
            ),
        ),

        'section_end' => array(
            'type' => 'sectionend',
            'id'   => 'alsaadrose_checkout_section_end',
        ),
    );
}

/**
 * Apply the Cash on Delivery fee when COD is the chosen payment method.
 *
 * @param WC_Cart $cart
 */
function alsaadrose_apply_cod_fee( $cart ) {
    // لا تشتغل في لوحة التحكم العادية
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    if ( ! $cart instanceof WC_Cart ) {
        return;
    }

    if ( ! WC()->session || ! WC()->cart ) {
        return;
    }

    $enabled = get_option( 'alsaad_cod_fee_enabled', 'no' );
    if ( 'yes' !== $enabled ) {
        return;
    }

    $amount = floatval( get_option( 'alsaad_cod_fee_amount', 0 ) );
    if ( $amount <= 0 ) {
        return;
    }

    $chosen_method = alsaadrose_get_current_checkout_payment_method();
    if ( 'cod' !== $chosen_method ) {
        return;
    }

    // Add COD fee only when the selected payment method is 'cod'.
    $cart->add_fee(
        __( 'Cash on Delivery fees', 'alsaadrose-checkout' ),
        $amount,
        false
    );
}

// Retrieve and persist the checkout payment method so COD fee logic stays in sync.
function alsaadrose_get_current_checkout_payment_method() {
    $method = null;

    if ( isset( $_POST['payment_method'] ) ) {
        $method = wc_clean( wp_unslash( $_POST['payment_method'] ) );
    } elseif ( isset( $_POST['post_data'] ) ) {
        $raw_post_data = wp_unslash( $_POST['post_data'] );

        if ( is_string( $raw_post_data ) ) {
            parse_str( $raw_post_data, $parsed_data );

            if ( isset( $parsed_data['payment_method'] ) ) {
                $method = wc_clean( $parsed_data['payment_method'] );
            }
        }
    }

    if ( $method && WC()->session ) {
        WC()->session->set( 'chosen_payment_method', $method );
    } elseif ( WC()->session ) {
        $stored_method = WC()->session->get( 'chosen_payment_method' );

        if ( $stored_method ) {
            $method = $stored_method;
        }
    }

    return $method;
}

// Store the checkout payment method when WooCommerce recalculates the order review.
function alsaadrose_maybe_store_checkout_payment_method( $posted_data ) {
    if ( ! WC()->session ) {
        return;
    }

    if ( empty( $posted_data ) ) {
        return;
    }

    parse_str( wp_unslash( $posted_data ), $data );

    if ( empty( $data['payment_method'] ) ) {
        return;
    }

    $method = wc_clean( $data['payment_method'] );
    WC()->session->set( 'chosen_payment_method', $method );
}

// Trigger checkout recalculation when payment method changes so COD fee is updated in the UI.
function alsaadrose_enqueue_checkout_scripts() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
        return;
    }

    wp_enqueue_script(
        'alsaadrose-checkout',
        plugin_dir_url( __FILE__ ) . 'assets/js/alsaadrose-checkout.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
}
