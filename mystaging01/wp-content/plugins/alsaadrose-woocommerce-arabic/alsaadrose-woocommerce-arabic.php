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
function tpplt_get_trp_current_language() {
    static $lang = null;

    if ( null !== $lang ) {
        return $lang;
    }

    $lang = '';

    if ( function_exists( 'trp_get_current_language' ) ) {
        $l = trp_get_current_language();
        if ( is_string( $l ) && '' !== $l ) {
            $lang = trim( $l );
        }
    }

    if ( '' === $lang && isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ) {
        $lang = trim( $GLOBALS['TRP_LANGUAGE'] );
    }

    return $lang;
}

/**
 * Check if current language is Arabic (any ar*, like ar, ar_AR, ar-sa).
 *
 * @return bool
 */
function tpplt_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    if ( ! function_exists( 'trp_get_current_language' ) && ! isset( $GLOBALS['TRP_LANGUAGE'] ) ) {
        return false;
    }

    $code = tpplt_get_trp_current_language();

    if ( '' === $code ) {
        return false;
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

    if ( ! tpplt_is_arabic_language() ) {
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

    global $post, $product;

    if ( ! $post || 'product' !== $post->post_type ) {
        return $content;
    }

    if ( $product instanceof WC_Product && 'product_variation' === $product->get_type() ) {
        return $content;
    }

    if ( tpplt_is_variation_description_context() ) {
        return $content;
    }

    if ( ! tpplt_is_arabic_language() ) {
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
 * Detect if the short description filter is running while formatting a variation description.
 *
 * WooCommerce passes each variation's description through `woocommerce_short_description`
 * inside `wc_get_formatted_variation()`. In that context the global $product is the parent
 * variable product, so the regular product type check cannot tell we are handling variation
 * text. This helper inspects a small portion of the backtrace to spot variation formatting
 * calls and prevent the Arabic short description from overwriting variation descriptions.
 *
 * @return bool
 */
function tpplt_is_variation_description_context() {
    $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 50 );

    foreach ( $trace as $frame ) {
        if ( isset( $frame['class'], $frame['function'] ) && 'WC_Product_Variation' === $frame['class'] && 'get_available_variation' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['class'], $frame['function'] ) && 'WC_Product_Variable' === $frame['class'] && 'get_available_variations' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['function'] ) && 'wc_get_formatted_variation' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['class'], $frame['function'] ) && 'WC_Product_Variation' === $frame['class'] && 'get_description' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['class'], $frame['function'] ) && 'WC_Product_Variable' === $frame['class'] && 'get_available_variation' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['class'], $frame['function'] ) && 'WC_AJAX' === $frame['class'] && 'get_variation' === $frame['function'] ) {
            return true;
        }

        if ( isset( $frame['class'], $frame['function'] ) && 'WC_Product_Data_Store_CPT' === $frame['class'] && 'get_product_type' === $frame['function'] ) {
            return true;
        }
    }

    return false;
}

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

    if ( ! tpplt_is_arabic_language() ) {
        return $content;
    }

    $post_id = get_queried_object_id();

    if ( ! $post_id ) {
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

    if ( ! tpplt_is_arabic_language() ) {
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

    if ( ! tpplt_is_arabic_language() ) {
        return $short;
    }

    if ( ! $product instanceof WC_Product ) {
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

    if ( ! tpplt_is_arabic_language() ) {
        return $desc;
    }

    if ( ! $product instanceof WC_Product ) {
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