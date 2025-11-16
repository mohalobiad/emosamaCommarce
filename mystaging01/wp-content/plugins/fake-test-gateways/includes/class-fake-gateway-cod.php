<?php
/**
 * Fake Cash on Delivery gateway for WooCommerce.
 *
 * @package Fake_Test_Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Fake_Gateway_COD' ) ) {
    /**
     * Fake Cash on Delivery gateway class.
     */
    class Fake_Gateway_COD extends WC_Payment_Gateway {
        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            $this->id                 = 'fake_cod';
            $this->icon               = '';
            $this->has_fields         = false;
            $this->method_title       = __( 'Fake Cash on Delivery', 'fake-test-gateways' );
            $this->method_description = __( 'Test-only fake cash on delivery payment method.', 'fake-test-gateways' );

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title', __( 'Fake Cash on Delivery', 'fake-test-gateways' ) );
            $this->description = $this->get_option( 'description', __( 'Test-only fake cash on delivery payment method.', 'fake-test-gateways' ) );
            $this->enabled     = $this->get_option( 'enabled', 'yes' );

            // Save settings.
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'   => __( 'Enable/Disable', 'fake-test-gateways' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Fake Cash on Delivery', 'fake-test-gateways' ),
                    'default' => 'yes',
                ),
                'title'       => array(
                    'title'       => __( 'Title', 'fake-test-gateways' ),
                    'type'        => 'text',
                    'description' => __( 'Controls the title seen during checkout.', 'fake-test-gateways' ),
                    'default'     => __( 'Fake Cash on Delivery', 'fake-test-gateways' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'fake-test-gateways' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description shown to customers.', 'fake-test-gateways' ),
                    'default'     => __( 'Test-only fake cash on delivery payment method.', 'fake-test-gateways' ),
                ),
            );
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id Order ID.
         *
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                return array(
                    'result'   => 'failure',
                    'message'  => __( 'Unable to process order with Fake COD gateway.', 'fake-test-gateways' ),
                    'redirect' => '',
                );
            }

            // Mark as processing to simulate payment capture and add a note.
            $order->update_status( 'processing', __( 'Paid via Fake COD (test gateway).', 'fake-test-gateways' ) );

            // Reduce stock levels and clear cart as in real payment.
            wc_reduce_stock_levels( $order_id );
            if ( WC()->cart ) {
                WC()->cart->empty_cart();
            }

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }

        /**
         * Always make the gateway available for testing.
         *
         * @return bool
         */
        public function is_available() {
            return 'yes' === $this->enabled;
        }
    }
}
