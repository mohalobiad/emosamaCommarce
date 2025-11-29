<?php
/**
 * Plugin Name: AlSaadrose WooCommerce Arabic Translations
 * Description: Adds custom Arabic translation fields for WooCommerce products and integrates with TranslatePress on the frontend.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register meta box for Arabic product fields.
 */
function tpplt_register_product_meta_box() {
    add_meta_box(
        'tpplt_arabic_translation',
        esc_html__( 'Arabic Translation (Custom)', 'tpplt' ),
        'tpplt_render_product_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'tpplt_register_product_meta_box' );

/**
 * Render the meta box content.
 *
 * @param WP_Post $post Current post object.
 */
function tpplt_render_product_meta_box( $post ) {
    wp_nonce_field( 'tpplt_save_meta', 'tpplt_nonce' );

    $title_ar = get_post_meta( $post->ID, '_tpplt_title_ar', true );
    $short_ar = get_post_meta( $post->ID, '_tpplt_short_ar', true );
    $desc_ar  = get_post_meta( $post->ID, '_tpplt_desc_ar', true );

    $has_data = ! empty( $title_ar ) || ! empty( $short_ar ) || ! empty( $desc_ar );
    ?>
    <p>
        <button type="button" class="button" id="tpplt-toggle-fields"><?php echo esc_html__( 'Add / Edit Arabic Translation', 'tpplt' ); ?></button>
    </p>
    <div id="tpplt-fields-wrap"<?php echo $has_data ? '' : ' style="display:none;"'; ?>>
        <p>
            <label for="tpplt_title_ar"><strong><?php echo esc_html__( 'Arabic Product Title', 'tpplt' ); ?></strong></label>
            <input type="text" class="widefat" name="tpplt_title_ar" id="tpplt_title_ar" value="<?php echo esc_attr( $title_ar ); ?>" />
        </p>
        <p>
            <label for="tpplt_short_ar"><strong><?php echo esc_html__( 'Arabic Short Description', 'tpplt' ); ?></strong></label>
            <textarea class="widefat" rows="5" name="tpplt_short_ar" id="tpplt_short_ar"><?php echo esc_textarea( $short_ar ); ?></textarea>
        </p>
        <p>
            <label for="tpplt_desc_ar"><strong><?php echo esc_html__( 'Arabic Description', 'tpplt' ); ?></strong></label>
        </p>
        <?php
        wp_editor(
            wp_kses_post( $desc_ar ),
            'tpplt_desc_ar',
            array(
                'textarea_name' => 'tpplt_desc_ar',
                'textarea_rows' => 8,
            )
        );
        ?>
    </div>
    <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
            $( '#tpplt-toggle-fields' ).on( 'click', function( event ) {
                event.preventDefault();
                $( '#tpplt-fields-wrap' ).slideToggle();
            } );
        } );
    </script>
    <?php
}

/**
 * Save the Arabic translation fields when the product is saved.
 *
 * @param int $post_id Post ID.
 */
function tpplt_save_product_meta( $post_id ) {
    if ( ! isset( $_POST['tpplt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tpplt_nonce'] ), 'tpplt_save_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'product' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        return;
    }

    $title_ar = isset( $_POST['tpplt_title_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['tpplt_title_ar'] ) ) : '';
    $short_ar = isset( $_POST['tpplt_short_ar'] ) ? wp_kses_post( wp_unslash( $_POST['tpplt_short_ar'] ) ) : '';
    $desc_ar  = isset( $_POST['tpplt_desc_ar'] ) ? wp_kses_post( wp_unslash( $_POST['tpplt_desc_ar'] ) ) : '';

    if ( '' !== $title_ar ) {
        update_post_meta( $post_id, '_tpplt_title_ar', $title_ar );
    } else {
        delete_post_meta( $post_id, '_tpplt_title_ar' );
    }

    if ( '' !== $short_ar ) {
        update_post_meta( $post_id, '_tpplt_short_ar', $short_ar );
    } else {
        delete_post_meta( $post_id, '_tpplt_short_ar' );
    }

    if ( '' !== $desc_ar ) {
        update_post_meta( $post_id, '_tpplt_desc_ar', $desc_ar );
    } else {
        delete_post_meta( $post_id, '_tpplt_desc_ar' );
    }
}
add_action( 'save_post_product', 'tpplt_save_product_meta' );

/**
 * Get current language code from TranslatePress.
 *
 * @return string
 */
function tpplt_get_trp_current_language( $product = null, $post_id = 0 ) {
    static $lang_cache = array();

    if ( $product instanceof WC_Product && ! $post_id ) {
        $post_id = $product->get_id();
    }

    $cache_key = $post_id ? 'id_' . $post_id : 'global';

    if ( isset( $lang_cache[ $cache_key ] ) ) {
        return $lang_cache[ $cache_key ];
    }

    $lang = '';

    if ( function_exists( 'trp_get_current_language' ) ) {
        $l = trp_get_current_language();
        if ( is_string( $l ) && '' !== $l ) {
            $lang = trim( $l );
        }
    }

    if ( '' === $lang && isset( $_REQUEST['TRP_LANGUAGE'] ) ) {
        $request_lang = sanitize_text_field( wp_unslash( $_REQUEST['TRP_LANGUAGE'] ) );
        if ( '' !== $request_lang ) {
            $lang = $request_lang;
        }
    }

    if ( '' === $lang && isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ) {
        $lang = trim( $GLOBALS['TRP_LANGUAGE'] );
    }

    if ( '' === $lang && function_exists( 'rest_get_server' ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        $server  = rest_get_server();
        $request = $server && method_exists( $server, 'get_current_request' ) ? $server->get_current_request() : null;

        if ( $request instanceof WP_REST_Request ) {
            foreach ( array( 'trp_language', 'trp_lang', 'language', 'lang', 'locale', 'TRP_LANGUAGE' ) as $param ) {
                $param_value = $request->get_param( $param );

                if ( is_string( $param_value ) && '' !== $param_value ) {
                    $lang = trim( $param_value );
                    break;
                }
            }
        }
    }

    if ( '' === $lang ) {
        $cookie_candidates = array( 'trp_language', 'TRP_LANGUAGE', 'trp_language_user' );

        foreach ( $cookie_candidates as $cookie_key ) {
            if ( isset( $_COOKIE[ $cookie_key ] ) && '' !== $_COOKIE[ $cookie_key ] ) {
                $lang = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) );
                break;
            }
        }
    }

    if ( '' === $lang ) {
        $header_candidates = array( 'HTTP_TRP_LANGUAGE', 'HTTP_X_TRP_LANGUAGE' );

        foreach ( $header_candidates as $header_key ) {
            if ( isset( $_SERVER[ $header_key ] ) && '' !== $_SERVER[ $header_key ] ) {
                $lang = sanitize_text_field( wp_unslash( $_SERVER[ $header_key ] ) );
                break;
            }
        }
    }

    if ( '' === $lang && $post_id ) {
        foreach ( array( '_trp_language', 'trp_language', 'language', 'lang', 'TRP_LANGUAGE' ) as $meta_key ) {
            $meta_lang = get_post_meta( $post_id, $meta_key, true );

            if ( is_string( $meta_lang ) && '' !== $meta_lang ) {
                $lang = trim( $meta_lang );
                break;
            }
        }
    }

    $lang_cache[ $cache_key ] = $lang;

    if ( '' !== $lang ) {
        wp_cache_set( 'tpplt_request_lang_' . $cache_key, $lang, 'tpplt', MINUTE_IN_SECONDS );
    }

    return $lang;
}

/**
 * Check if current language is Arabic (any ar*, like ar, ar_AR, ar-sa).
 *
 * @return bool
 */
function tpplt_is_arabic_language( $product = null, $post_id = 0 ) {
    if ( is_admin() ) {
        return false;
    }

    if ( $product instanceof WC_Product && ! $post_id ) {
        $post_id = $product->get_id();
    }

    if ( ! $post_id && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
        $post_id = $GLOBALS['post']->ID;
    }

    $code = tpplt_get_trp_current_language( $product, $post_id );

    if ( '' === $code ) {
        $code = wp_cache_get( 'tpplt_request_lang_' . ( $post_id ? 'id_' . $post_id : 'global' ), 'tpplt' );
    }

    if ( '' === $code ) {
        $code = tpplt_extract_arabic_route_language();
    }

    $code = strtolower( $code );

    if ( 'ar' === $code ) {
        return true;
    }

    if ( 0 === strpos( $code, 'ar_' ) ) {
        return true;
    }

    if ( 0 === strpos( $code, 'ar-' ) ) {
        return true;
    }

    $has_arabic_meta = false;

    if ( $post_id ) {
        $has_arabic_meta = '' !== get_post_meta( $post_id, '_tpplt_desc_ar', true ) || '' !== get_post_meta( $post_id, '_asp_at_variation_desc_ar', true );
    }

    if ( $has_arabic_meta && tpplt_is_arabic_route_hint() ) {
        return true;
    }

    return false;
}

/**
 * Detect Arabic hints from the current route or referer.
 *
 * @return string
 */
function tpplt_extract_arabic_route_language() {
    $paths = array();

    if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
        $paths[] = wp_unslash( $_SERVER['REQUEST_URI'] );
    }

    $referer = wp_get_referer();

    if ( $referer ) {
        $referer_path = wp_parse_url( $referer, PHP_URL_PATH );

        if ( $referer_path ) {
            $paths[] = $referer_path;
        }
    }

    foreach ( $paths as $path ) {
        if ( is_string( $path ) && preg_match( '#(^/ar(/|$))|(/ar/)|(/ar$)#i', $path ) ) {
            return 'ar';
        }
    }

    return '';
}

/**
 * Determine whether current request hints at an Arabic context.
 *
 * @return bool
 */
function tpplt_is_arabic_route_hint() {
    return '' !== tpplt_extract_arabic_route_language();
}

/**
 * Determine if the current context refers to a product variation.
 *
 * @param WC_Product|mixed $product Optional product object.
 * @param int              $post_id Optional post ID to check.
 *
 * @return bool
 */
function tpplt_is_variation_context( $product = null, $post_id = 0 ) {
    if ( $product instanceof WC_Product && $product->is_type( 'variation' ) ) {
        return true;
    }

    if ( ! $post_id && $product instanceof WC_Product ) {
        $post_id = $product->get_id();
    }

    if ( $post_id && 'product_variation' === get_post_type( $post_id ) ) {
        return true;
    }

    if ( empty( $post_id ) && isset( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof WP_Post ) {
        if ( 'product_variation' === $GLOBALS['post']->post_type ) {
            return true;
        }
    }

    return false;
}

/**
 * Filter product titles on the frontend for Arabic language.
 *
 * @param string $title   The current title.
 * @param int    $post_id The post ID.
 *
 * @return string
 */
function tpplt_filter_product_title( $title, $post_id ) {
    if ( is_admin() || ! $post_id ) {
        return $title;
    }

    if ( 'product' !== get_post_type( $post_id ) ) {
        return $title;
    }

    if ( ! tpplt_is_arabic_language( null, $post_id ) ) {
        return $title;
    }

    $arabic_title = get_post_meta( $post_id, '_tpplt_title_ar', true );

    if ( ! empty( $arabic_title ) ) {
        return $arabic_title;
    }

    return $title;
}
add_filter( 'the_title', 'tpplt_filter_product_title', 999, 2 );

/**
 * Filter the WooCommerce short description on the frontend when Arabic is active.
 *
 * @param string $content Short description content.
 *
 * @return string
 */
function tpplt_filter_short_description( $content ) {
    if ( is_admin() ) {
        return $content;
    }

    global $post;

    if ( tpplt_is_variation_context( null, $post ? $post->ID : 0 ) ) {
        return $content;
    }

    if ( ! $post || 'product' !== $post->post_type ) {
        return $content;
    }

    if ( ! tpplt_is_arabic_language( null, $post ? $post->ID : 0 ) ) {
        return $content;
    }

    $arabic_short = get_post_meta( $post->ID, '_tpplt_short_ar', true );

    if ( ! empty( $arabic_short ) ) {
        return $arabic_short;
    }

    return $content;
}
add_filter( 'woocommerce_short_description', 'tpplt_filter_short_description', 999 );

/**
 * Filter the main product description content when Arabic language is active.
 *
 * @param string $content The post content.
 *
 * @return string
 */
function tpplt_filter_product_content( $content ) {
    if ( is_admin() ) {
        return $content;
    }

    if ( ! is_singular( 'product' ) ) {
        return $content;
    }

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
        return $content;
    }

    if ( tpplt_is_variation_context( null, $post_id ) ) {
        return $content;
    }

    if ( ! tpplt_is_arabic_language( null, $post_id ) ) {
        return $content;
    }

    $arabic_desc = get_post_meta( $post_id, '_tpplt_desc_ar', true );

    if ( ! empty( $arabic_desc ) ) {
        return $arabic_desc;
    }

    return $content;
}
add_filter( 'the_content', 'tpplt_filter_product_content', 999 );

/**
 * Filter WooCommerce product name getter for Arabic language.
 *
 * @param string     $name    Product name.
 * @param WC_Product $product Product object.
 *
 * @return string
 */
function tpplt_wc_product_get_name_ar( $name, $product ) {
    if ( is_admin() ) {
        return $name;
    }

    if ( ! tpplt_is_arabic_language( $product ) ) {
        return $name;
    }

    if ( ! $product instanceof WC_Product ) {
        return $name;
    }

    $arabic_title = get_post_meta( $product->get_id(), '_tpplt_title_ar', true );

    if ( ! empty( $arabic_title ) ) {
        return $arabic_title;
    }

    return $name;
}
add_filter( 'woocommerce_product_get_name', 'tpplt_wc_product_get_name_ar', 9999, 2 );

/**
 * Filter WooCommerce product short description getter for Arabic language.
 *
 * @param string     $short   Product short description.
 * @param WC_Product $product Product object.
 *
 * @return string
 */
function tpplt_wc_product_get_short_desc_ar( $short, $product ) {
    if ( is_admin() ) {
        return $short;
    }

    if ( ! $product instanceof WC_Product ) {
        return $short;
    }

    if ( tpplt_is_variation_context( $product ) ) {
        return $short;
    }

    if ( ! tpplt_is_arabic_language( $product ) ) {
        return $short;
    }

    $arabic_short = get_post_meta( $product->get_id(), '_tpplt_short_ar', true );

    if ( ! empty( $arabic_short ) ) {
        return $arabic_short;
    }

    return $short;
}
add_filter( 'woocommerce_product_get_short_description', 'tpplt_wc_product_get_short_desc_ar', 9999, 2 );

/**
 * Filter WooCommerce product description getter for Arabic language.
 *
 * @param string     $desc    Product description.
 * @param WC_Product $product Product object.
 *
 * @return string
 */
function tpplt_wc_product_get_desc_ar( $desc, $product ) {
    if ( is_admin() ) {
        return $desc;
    }

    if ( ! $product instanceof WC_Product ) {
        return $desc;
    }

    if ( tpplt_is_variation_context( $product ) ) {
        return $desc;
    }

    if ( ! tpplt_is_arabic_language( $product ) ) {
        return $desc;
    }

    $arabic_desc = get_post_meta( $product->get_id(), '_tpplt_desc_ar', true );

    if ( ! empty( $arabic_desc ) ) {
        return $arabic_desc;
    }

    return $desc;
}
add_filter( 'woocommerce_product_get_description', 'tpplt_wc_product_get_desc_ar', 9999, 2 );

/**
 * Allow XLSX uploads wherever WooCommerce expects CSV files.
 *
 * @param array $filetypes Allowed file types.
 *
 * @return array
 */
function tpplt_allow_xlsx_filetypes( $filetypes ) {
    $filetypes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    return $filetypes;
}
add_filter( 'woocommerce_csv_import_valid_filetypes', 'tpplt_allow_xlsx_filetypes' );
add_filter( 'woocommerce_csv_product_import_valid_filetypes', 'tpplt_allow_xlsx_filetypes' );

/**
 * Use the custom importer that can read XLSX files by converting them to CSV.
 *
 * @param string $importer_class Default importer class name.
 *
 * @return string
 */
function tpplt_register_xlsx_enabled_importer( $importer_class ) {
    if ( ! class_exists( 'TPPLT_Product_Importer', false ) ) {
        $path = plugin_dir_path( __FILE__ ) . 'includes/class-tpplt-xlsx-importer.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }

    if ( class_exists( 'TPPLT_Product_Importer', false ) ) {
        return 'TPPLT_Product_Importer';
    }

    return $importer_class;
}
add_filter( 'woocommerce_product_csv_importer_class', 'tpplt_register_xlsx_enabled_importer' );

/**
 * Register custom mapping options for WooCommerce CSV import.
 *
 * @param array $options Existing mapping options.
 *
 * @return array
 */
function tpplt_import_mapping_options( $options ) {
    $options['tpplt_title_ar'] = esc_html__( 'Arabic product title (plugin)', 'tpplt' );
    $options['tpplt_short_ar'] = esc_html__( 'Arabic short description (plugin)', 'tpplt' );
    $options['tpplt_desc_ar']  = esc_html__( 'Arabic description (plugin)', 'tpplt' );

    return $options;
}
add_filter( 'woocommerce_csv_product_import_mapping_options', 'tpplt_import_mapping_options' );

/**
 * Map CSV column headers to custom plugin fields by default.
 *
 * @param array $columns Existing default column mappings.
 *
 * @return array
 */
function tpplt_import_default_columns( $columns ) {
    $columns['Name_ar']              = 'tpplt_title_ar';
    $columns['Short description_ar'] = 'tpplt_short_ar';
    $columns['Description_ar']       = 'tpplt_desc_ar';
    $columns['name_ar']              = 'tpplt_title_ar';
    $columns['short description_ar'] = 'tpplt_short_ar';
    $columns['description_ar']       = 'tpplt_desc_ar';

    return $columns;
}
add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'tpplt_import_default_columns' );

/**
 * Persist Arabic translation data during WooCommerce CSV import.
 *
 * @param WC_Product $product Product being imported.
 * @param array      $data    Raw CSV data for the product.
 *
 * @return WC_Product
 */
function tpplt_import_pre_insert_product_object( $product, $data ) {
    if ( isset( $data['tpplt_title_ar'] ) && '' !== $data['tpplt_title_ar'] ) {
        $product->update_meta_data( '_tpplt_title_ar', wp_kses_post( $data['tpplt_title_ar'] ) );
    }

    if ( isset( $data['tpplt_short_ar'] ) && '' !== $data['tpplt_short_ar'] ) {
        $product->update_meta_data( '_tpplt_short_ar', wp_kses_post( $data['tpplt_short_ar'] ) );
    }

    if ( isset( $data['tpplt_desc_ar'] ) && '' !== $data['tpplt_desc_ar'] ) {
        $product->update_meta_data( '_tpplt_desc_ar', wp_kses_post( $data['tpplt_desc_ar'] ) );
    }

    return $product;
}
add_filter( 'woocommerce_product_import_pre_insert_product_object', 'tpplt_import_pre_insert_product_object', 10, 2 );