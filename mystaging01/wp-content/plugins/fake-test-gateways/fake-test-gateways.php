<?php
/**
 * Plugin Name: Fake Test Gateways for WooCommerce
 * Plugin URI: https://example.com/
 * Description: Provides fake/test payment gateways for WooCommerce checkout testing.
 * Version: 1.0.0
 * Author: ChatGPT
 * Author URI: https://openai.com/
 * License: GPL2+
 * Text Domain: fake-test-gateways
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Fake_Test_Gateways_Plugin' ) ) {
    /**
     * Main plugin class to bootstrap fake gateways.
     */
    class Fake_Test_Gateways_Plugin {
        /**
         * Singleton instance.
         *
         * @var Fake_Test_Gateways_Plugin
         */
        protected static $instance = null;

        /**
         * Retrieve singleton instance.
         *
         * @return Fake_Test_Gateways_Plugin
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Fake_Test_Gateways_Plugin constructor.
         */
        protected function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 20 );
        }

        /**
         * Initialize plugin functionality once WooCommerce is available.
         */
        public function init_plugin() {
            if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
                // WooCommerce not active, display admin notice if user can manage WooCommerce.
                add_action( 'admin_notices', array( $this, 'admin_notice_missing_wc' ) );

                return;
            }

            $this->include_gateway_files();
            $this->register_gateways();
        }

        /**
         * Include gateway class files.
         */
        protected function include_gateway_files() {
            require_once __DIR__ . '/includes/class-fake-gateway-cod.php';
            require_once __DIR__ . '/includes/class-fake-gateway-bank.php';
        }

        /**
         * Register gateway classes with WooCommerce.
         */
        protected function register_gateways() {
            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
        }

        /**
         * Filter callback to add our gateways to WooCommerce.
         *
         * @param array $gateways Existing gateways.
         *
         * @return array
         */
        public function add_gateways( $gateways ) {
            $gateways[] = 'Fake_Gateway_COD';
            $gateways[] = 'Fake_Gateway_Bank';

            return $gateways;
        }

        /**
         * Display an admin notice when WooCommerce is not active.
         */
        public function admin_notice_missing_wc() {
            if ( current_user_can( 'activate_plugins' ) ) {
                echo '<div class="notice notice-error"><p>';
                esc_html_e( 'Fake Test Gateways for WooCommerce requires WooCommerce to be active.', 'fake-test-gateways' );
                echo '</p></div>';
            }
        }
    }
}

Fake_Test_Gateways_Plugin::instance();
