<?php
/**
 * Plugin Name: AlSaadrose UAE City Manager
 * Description: Manage bilingual UAE city options for the Woodmart child checkout form.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

if ( ! class_exists( 'AlSaadrose_UAE_City_Manager' ) ) {
        class AlSaadrose_UAE_City_Manager {
                const OPTION_NAME = 'aucm_bilingual_cities';
                const NONCE_ACTION = 'aucm_manage_cities';
                const PAGE_SLUG = 'aucm-city-manager';

                /**
                 * Constructor.
                 */
                public function __construct() {
                        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
                        add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
                        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
                }

                /**
                 * Register the admin page under WooCommerce.
                 */
                public function register_admin_page() {
                        add_submenu_page(
                                'woocommerce',
                                esc_html__( 'UAE Cities', 'woodmart-child' ),
                                esc_html__( 'UAE Cities', 'woodmart-child' ),
                                'manage_options',
                                self::PAGE_SLUG,
                                array( $this, 'render_admin_page' )
                        );
                }

                /**
                 * Enqueue styles/scripts for admin UI.
                 *
                 * @param string $hook Current page hook.
                 */
                public function enqueue_assets( $hook ) {
                        if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
                                return;
                        }

                        wp_enqueue_style(
                                'aucm-admin',
                                plugins_url( 'assets/admin.css', __FILE__ ),
                                array(),
                                '1.0.0'
                        );
                        wp_enqueue_script(
                                'aucm-admin',
                                plugins_url( 'assets/admin.js', __FILE__ ),
                                array( 'jquery' ),
                                '1.0.0',
                                true
                        );

                        wp_localize_script(
                                'aucm-admin',
                                'AUCMAdmin',
                                array(
                                        'editLabel' => esc_html__( 'Editing', 'woodmart-child' ),
                                )
                        );
                }

                /**
                 * Handle saving or deleting cities from admin page.
                 */
                public function handle_form_submission() {
                        if ( ! isset( $_POST['aucm_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                                return;
                        }

                        if ( ! current_user_can( 'manage_options' ) ) {
                                return;
                        }

                        check_admin_referer( self::NONCE_ACTION, 'aucm_nonce' );

                        $action = sanitize_text_field( wp_unslash( $_POST['aucm_action'] ) );
                        $cities = self::get_cities();

                        if ( 'save' === $action ) {
                                $english = isset( $_POST['aucm_city_en'] ) ? sanitize_text_field( wp_unslash( $_POST['aucm_city_en'] ) ) : '';
                                $arabic  = isset( $_POST['aucm_city_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['aucm_city_ar'] ) ) : '';
                                $index   = isset( $_POST['aucm_city_index'] ) ? intval( $_POST['aucm_city_index'] ) : -1;

                                if ( '' === $english || '' === $arabic ) {
                                        add_settings_error( self::PAGE_SLUG, 'aucm_missing_values', esc_html__( 'Both English and Arabic names are required.', 'woodmart-child' ), 'error' );
                                        return;
                                }

                                $city = array(
                                        'en' => $english,
                                        'ar' => $arabic,
                                );

                                if ( $index >= 0 && isset( $cities[ $index ] ) ) {
                                        $cities[ $index ] = $city;
                                        $message = esc_html__( 'City updated successfully.', 'woodmart-child' );
                                } else {
                                        $cities[] = $city;
                                        $message = esc_html__( 'City added successfully.', 'woodmart-child' );
                                }

                                self::save_cities( $cities );
                                add_settings_error( self::PAGE_SLUG, 'aucm_saved', $message, 'updated' );
                                return;
                        }

                        if ( 'delete' === $action ) {
                                $index = isset( $_POST['aucm_city_index'] ) ? intval( $_POST['aucm_city_index'] ) : -1;

                                if ( $index >= 0 && isset( $cities[ $index ] ) ) {
                                        unset( $cities[ $index ] );
                                        $cities = array_values( $cities );
                                        self::save_cities( $cities );
                                        add_settings_error( self::PAGE_SLUG, 'aucm_deleted', esc_html__( 'City removed.', 'woodmart-child' ), 'updated' );
                                } else {
                                        add_settings_error( self::PAGE_SLUG, 'aucm_missing_city', esc_html__( 'City not found.', 'woodmart-child' ), 'error' );
                                }
                        }
                }

                /**
                 * Render the admin page output.
                 */
                public function render_admin_page() {
                        $cities = self::get_cities();
                        ?>
                        <div class="wrap aucm-wrap">
                                <h1><?php esc_html_e( 'UAE Cities', 'woodmart-child' ); ?></h1>
                                <p><?php esc_html_e( 'Add, update, or remove checkout cities in both English and Arabic.', 'woodmart-child' ); ?></p>
                                <?php settings_errors( self::PAGE_SLUG ); ?>
                                <form method="post" class="aucm-form">
                                        <?php wp_nonce_field( self::NONCE_ACTION, 'aucm_nonce' ); ?>
                                        <input type="hidden" name="aucm_action" value="save">
                                        <input type="hidden" name="aucm_city_index" id="aucm_city_index" value="">
                                        <table class="form-table" role="presentation">
                                                <tr>
                                                        <th scope="row"><label for="aucm_city_en"><?php esc_html_e( 'City name (English)', 'woodmart-child' ); ?></label></th>
                                                        <td>
                                                                <input type="text" class="regular-text" name="aucm_city_en" id="aucm_city_en" value="" required>
                                                                <p class="description"><?php esc_html_e( 'Displayed on the checkout when the English locale is active.', 'woodmart-child' ); ?></p>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <th scope="row"><label for="aucm_city_ar"><?php esc_html_e( 'City name (Arabic)', 'woodmart-child' ); ?></label></th>
                                                        <td>
                                                                <input type="text" class="regular-text" name="aucm_city_ar" id="aucm_city_ar" value="" required>
                                                                <p class="description"><?php esc_html_e( 'Displayed on the checkout when the Arabic locale is active.', 'woodmart-child' ); ?></p>
                                                        </td>
                                                </tr>
                                        </table>
                                        <p>
                                                <button type="submit" class="button button-primary">
                                                        <?php esc_html_e( 'Save city', 'woodmart-child' ); ?>
                                                </button>
                                                <button type="button" class="button button-secondary" id="aucm_reset_form">
                                                        <?php esc_html_e( 'Clear form', 'woodmart-child' ); ?>
                                                </button>
                                        </p>
                                </form>

                                <hr>

                                <h2><?php esc_html_e( 'Cities list', 'woodmart-child' ); ?></h2>
                                <p><?php esc_html_e( 'Click a chip to edit or use the “×” icon to remove it.', 'woodmart-child' ); ?></p>
                                <div class="aucm-city-chips" role="list">
                                        <?php if ( empty( $cities ) ) : ?>
                                                <p><?php esc_html_e( 'No cities found. Add your first city above.', 'woodmart-child' ); ?></p>
                                        <?php else : ?>
                                                <?php foreach ( $cities as $index => $city ) :
                                                        $english = isset( $city['en'] ) ? $city['en'] : '';
                                                        $arabic  = isset( $city['ar'] ) ? $city['ar'] : '';
                                                        ?>
                                                        <div class="aucm-chip" role="listitem" data-index="<?php echo esc_attr( $index ); ?>" data-en="<?php echo esc_attr( $english ); ?>" data-ar="<?php echo esc_attr( $arabic ); ?>">
                                                                <button type="button" class="aucm-chip__label" aria-label="<?php esc_attr_e( 'Edit city', 'woodmart-child' ); ?>">
                                                                        <span class="aucm-chip__arabic"><?php echo esc_html( $arabic ); ?></span>
                                                                        <span class="aucm-chip__divider">|</span>
                                                                        <span class="aucm-chip__english"><?php echo esc_html( $english ); ?></span>
                                                                </button>
                                                                <form method="post" class="aucm-chip__remove" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this city?', 'woodmart-child' ) ); ?>');">
                                                                        <?php wp_nonce_field( self::NONCE_ACTION, 'aucm_nonce' ); ?>
                                                                        <input type="hidden" name="aucm_action" value="delete">
                                                                        <input type="hidden" name="aucm_city_index" value="<?php echo esc_attr( $index ); ?>">
                                                                        <button type="submit" class="button-link" aria-label="<?php esc_attr_e( 'Remove city', 'woodmart-child' ); ?>">&times;</button>
                                                                </form>
                                                        </div>
                                                <?php endforeach; ?>
                                        <?php endif; ?>
                                </div>
                        </div>
                        <?php
                }

                /**
                 * Retrieve saved cities or defaults.
                 *
                 * @return array
                 */
                public static function get_cities() {
                        $cities = get_option( self::OPTION_NAME );
                        if ( ! is_array( $cities ) || empty( $cities ) ) {
                                $cities = self::get_default_cities();
                                update_option( self::OPTION_NAME, $cities );
                        }

                        return array_map( array( __CLASS__, 'normalize_city' ), $cities );
                }

                /**
                 * Save cities option.
                 *
                 * @param array $cities Cities.
                 */
                public static function save_cities( $cities ) {
                        update_option( self::OPTION_NAME, array_map( array( __CLASS__, 'normalize_city' ), $cities ) );
                }

                /**
                 * Normalize a city entry structure.
                 *
                 * @param array $city Raw data.
                 * @return array
                 */
                protected static function normalize_city( $city ) {
                        $en = '';
                        $ar = '';

                        if ( is_array( $city ) ) {
                                $en = isset( $city['en'] ) ? (string) $city['en'] : '';
                                $ar = isset( $city['ar'] ) ? (string) $city['ar'] : '';
                        } elseif ( is_string( $city ) ) {
                                $en = $city;
                                $ar = $city;
                        }

                        if ( '' === $ar ) {
                                $ar = $en;
                        }

                        if ( '' === $en ) {
                                $en = $ar;
                        }

                        return array(
                                'en' => $en,
                                'ar' => $ar,
                        );
                }

                /**
                 * Default cities list using the original Woodmart child configuration.
                 *
                 * @return array
                 */
                protected static function get_default_cities() {
                        $defaults = array(
                                'Abu Dhabi',
                                'Al Ain',
                                'Madinat Zayed',
                                'Ghayathi',
                                'Al Ruwais',
                                'Al Mirfa',
                                'Sila',
                                'Dalma',
                                'Dubai',
                                'Hatta',
                                'Sharjah',
                                'Al Dhaid',
                                'Khor Fakkan',
                                'Kalba',
                                'Dibba Al-Hisn',
                                'Ajman',
                                'Masfout',
                                'Manama',
                                'Umm Al Quwain',
                                'Falaj Al Mualla',
                                'Ras Al Khaimah',
                                'Rams',
                                'Al Jazirah Al Hamra',
                                'Fujairah',
                                'Dibba Al-Fujairah',
                                'Liwa',
                        );

                        $cities = array();
                        foreach ( $defaults as $city ) {
                                $cities[] = array(
                                        'en' => $city,
                                        'ar' => $city,
                                );
                        }

                        return $cities;
                }
        }

        new AlSaadrose_UAE_City_Manager();
}

if ( ! function_exists( 'aucm_get_current_language_code' ) ) {
        /**
         * Detect current language code with TranslatePress compatibility.
         *
         * @return string
         */
        function aucm_get_current_language_code() {
                        static $lang = null;

                        if ( null !== $lang ) {
                                return $lang;
                        }

                        $lang = '';

                        if ( function_exists( 'trp_get_current_language' ) ) {
                                $detected = trp_get_current_language();
                                if ( is_string( $detected ) ) {
                                        $lang = trim( $detected );
                                }
                        }

                        if ( '' === $lang && isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ) {
                                $lang = trim( $GLOBALS['TRP_LANGUAGE'] );
                        }

                        if ( '' === $lang ) {
                                $lang = determine_locale();
                        }

                        return $lang;
        }
}

if ( ! function_exists( 'aucm_is_arabic_language' ) ) {
        /**
         * Check whether the active language is Arabic.
         *
         * @return bool
         */
        function aucm_is_arabic_language() {
                        $code = strtolower( (string) aucm_get_current_language_code() );
                        if ( '' === $code ) {
                                return false;
                        }

                        return ( 'ar' === $code || 0 === strpos( $code, 'ar_' ) || 0 === strpos( $code, 'ar-' ) );
        }
}

if ( ! function_exists( 'aucm_get_checkout_city_options' ) ) {
        /**
         * Return formatted options for the checkout select field.
         *
         * @return array
         */
        function aucm_get_checkout_city_options() {
                        if ( ! class_exists( 'AlSaadrose_UAE_City_Manager' ) ) {
                                return array();
                        }

                        $cities   = AlSaadrose_UAE_City_Manager::get_cities();
                        $is_ar    = aucm_is_arabic_language();
                        $language = $is_ar ? 'ar' : 'en';
                        $options  = array();

                        foreach ( $cities as $city ) {
                                $label = isset( $city[ $language ] ) ? $city[ $language ] : '';
                                if ( '' === $label ) {
                                        $label = isset( $city['en'] ) ? $city['en'] : '';
                                }
                                if ( '' === $label ) {
                                        continue;
                                }
                                $options[ $label ] = $label;
                        }

                        return $options;
        }
}
