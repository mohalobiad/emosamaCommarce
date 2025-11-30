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
    if ( is_admin() && ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() ) ) {
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

    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        $action  = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        $wc_ajax = isset( $_REQUEST['wc-ajax'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wc-ajax'] ) ) : '';

        if ( in_array( $action, array( 'woocommerce_get_variation' ), true ) || in_array( $wc_ajax, array( 'get_variation', 'get_variations' ), true ) ) {
            return $content;
        }
    }

    if ( function_exists( 'is_product' ) && ! is_product() ) {
        return $content;
    }

    global $post;

    if ( ! $post || 'product' !== $post->post_type ) {
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
 * Filter variation data to use Arabic descriptions when available and prevent
 * empty variations from inheriting the parent description.
 *
 * @param array               $variation_data Variation data array.
 * @param WC_Product          $product        Parent product object.
 * @param WC_Product_Variation $variation     Variation product object.
 *
 * @return array
 */
/**
 * ضبط بيانات الـ variation حتى لا ترث الـ short description،
 * وتستخدم الوصف العربي للـ variation (من أي بلغن ثاني مثل aspatn).
 *
 * @param array                $variation_data Variation data array.
 * @param WC_Product           $product        Parent product object.
 * @param WC_Product_Variation $variation      Variation product object.
 *
 * @return array
 */
function tpplt_filter_available_variation( $variation_data, $product, $variation ) {
    if ( ! $variation instanceof WC_Product_Variation ) {
        return $variation_data;
    }

    // نشتغل بس لما اللغة عربي
    if ( ! tpplt_is_arabic_language() ) {
        return $variation_data;
    }

    // هذا يرجّع وصف الـ variation (والبلغن aspatn يعدله للعربي لو موجود)
    $arabic_description = $variation->get_description();

    $fields_to_filter = array( 'variation_description', 'variation_description_raw' );

    // لو ما في وصف للـ variation، نتأكد إنه ما يرث الـ short description
    if ( '' === trim( $arabic_description ) ) {
        foreach ( $fields_to_filter as $field_key ) {
            if ( array_key_exists( $field_key, $variation_data ) ) {
                $variation_data[ $field_key ] = '';
            }
        }

        return $variation_data;
    }

    // هنا بدنا نفرمت الوصف بدون ما فلتر الـ short description تبع البلغن يتدخل
    remove_filter( 'woocommerce_short_description', 'tpplt_filter_short_description', 999 );

    $formatted = wc_format_content( $arabic_description );

    add_filter( 'woocommerce_short_description', 'tpplt_filter_short_description', 999 );

    // نحقن القيم الصحيحة في بيانات الـ variation اللي تروح للـ JS
    $variation_data['variation_description_raw'] = $arabic_description;
    $variation_data['variation_description']     = $formatted;

    return $variation_data;
}
add_filter( 'woocommerce_available_variation', 'tpplt_filter_available_variation', 999, 3 );


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

/**
 * Render an export option to choose between CSV and XLSX formats.
 */
function tpplt_export_format_selector() {
    ?>
    <tr>
        <th scope="row">
            <label for="tpplt-export-format"><?php echo esc_html__( 'Which file format should be exported?', 'tpplt' ); ?></label>
        </th>
        <td>
            <select id="tpplt-export-format" name="tpplt_export_format" class="wc-enhanced-select" style="width:100%;">
                <option value="csv" selected><?php echo esc_html__( 'CSV', 'tpplt' ); ?></option>
                <option value="xlsx"><?php echo esc_html__( 'XLSX', 'tpplt' ); ?></option>
            </select>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_product_export_row', 'tpplt_export_format_selector' );

/**
 * Parse the requested export format from the serialized form string.
 *
 * @return string
 */
function tpplt_get_requested_export_format() {
    if ( empty( $_POST['form'] ) ) {
        return '';
    }

    $form_data = array();
    parse_str( wp_unslash( $_POST['form'] ), $form_data );

    if ( empty( $form_data['tpplt_export_format'] ) ) {
        return '';
    }

    $format = strtolower( sanitize_key( $form_data['tpplt_export_format'] ) );

    return in_array( $format, array( 'csv', 'xlsx' ), true ) ? $format : '';
}

/**
 * Normalize an export filename to ensure it uses the desired extension.
 *
 * @param string $filename  Original filename from the request.
 * @param string $extension Target extension with leading dot.
 *
 * @return string
 */
function tpplt_normalize_export_filename( $filename, $extension ) {
    $filename = sanitize_file_name( $filename );

    if ( '' === $filename ) {
        $filename = 'wc-product-export' . $extension;
    }

    return preg_replace( '/\.[^.]+$/', $extension, $filename );
}

/**
 * Handle XLSX generation when the product exporter runs.
 */
function tpplt_handle_xlsx_product_export() {
    $format = tpplt_get_requested_export_format();

    if ( 'xlsx' !== $format ) {
        return;
    }

    check_ajax_referer( 'wc-product-export', 'security' );

    if ( ! current_user_can( 'edit_products' ) || ! current_user_can( 'export' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient privileges to export products.', 'tpplt' ) ) );
    }

    include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php';
    $xlsx_helper = plugin_dir_path( __FILE__ ) . 'includes/class-tpplt-xlsx-exporter.php';

    if ( file_exists( $xlsx_helper ) ) {
        require_once $xlsx_helper;
    }

    if ( ! class_exists( 'TPPLT_XLSX_Exporter', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Unable to generate XLSX exports because the helper class is missing.', 'tpplt' ) ) );
    }

    $step       = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1; // WPCS: input var ok, sanitization ok.
    $exporter   = new WC_Product_CSV_Exporter();
    $upload_dir = wp_upload_dir();

    $xlsx_name    = ! empty( $_POST['filename'] ) ? wp_unslash( $_POST['filename'] ) : 'wc-product-export.xlsx'; // WPCS: input var ok.
    $xlsx_name    = tpplt_normalize_export_filename( $xlsx_name, '.xlsx' );
    $xlsx_name    = wp_unique_filename( $upload_dir['basedir'], $xlsx_name );
    $csv_filename = tpplt_normalize_export_filename( $xlsx_name, '.csv' );

    if ( ! empty( $_POST['columns'] ) ) { // WPCS: input var ok.
        $exporter->set_column_names( wp_unslash( $_POST['columns'] ) ); // WPCS: input var ok, sanitization ok.
    }

    if ( ! empty( $_POST['selected_columns'] ) ) { // WPCS: input var ok.
        $exporter->set_columns_to_export( wp_unslash( $_POST['selected_columns'] ) ); // WPCS: input var ok, sanitization ok.
    }

    if ( ! empty( $_POST['export_meta'] ) ) { // WPCS: input var ok.
        $exporter->enable_meta_export( true );
    }

    if ( ! empty( $_POST['export_types'] ) ) { // WPCS: input var ok.
        $exporter->set_product_types_to_export( wp_unslash( $_POST['export_types'] ) ); // WPCS: input var ok, sanitization ok.
    }

    if ( ! empty( $_POST['export_category'] ) && is_array( $_POST['export_category'] ) ) { // WPCS: input var ok.
        $exporter->set_product_category_to_export( wp_unslash( array_values( $_POST['export_category'] ) ) ); // WPCS: input var ok, sanitization ok.
    }

    if ( ! empty( $_POST['export_product_ids'] ) ) { // WPCS: input var ok.
        $ids_raw = explode( ',', sanitize_text_field( wp_unslash( $_POST['export_product_ids'] ) ) ); // WPCS: input var ok, sanitization ok.

        if ( ! empty( $ids_raw ) ) {
            $exporter->set_product_ids_to_export( $ids_raw );
        }
    }

    $exporter->set_filename( $csv_filename );
    $exporter->set_page( $step );
    $exporter->generate_file();

    if ( 100 === $exporter->get_percent_complete() ) {
        $csv_filename = $exporter->get_filename();
        $csv_path     = trailingslashit( $upload_dir['basedir'] ) . $csv_filename;

        $xlsx_filename = $xlsx_name;
        $xlsx_path     = trailingslashit( $upload_dir['basedir'] ) . $xlsx_filename;

        try {
            TPPLT_XLSX_Exporter::convert_csv_to_xlsx( $csv_path, $xlsx_path );
            @unlink( $csv_path );
            @unlink( $csv_path . '.headers' );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }

        $query_args = apply_filters(
            'woocommerce_export_get_ajax_query_args',
            array(
                'nonce'    => wp_create_nonce( 'product-csv' ),
                'action'   => 'tpplt_download_product_export',
                'filename' => $xlsx_filename,
            )
        );

        wp_send_json_success(
            array(
                'step'       => 'done',
                'percentage' => 100,
                'url'        => add_query_arg( $query_args, admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
            )
        );
    }

    wp_send_json_success(
        array(
            'step'       => ++$step,
            'percentage' => $exporter->get_percent_complete(),
            'columns'    => $exporter->get_column_names(),
        )
    );
}
add_action( 'wp_ajax_woocommerce_do_ajax_product_export', 'tpplt_handle_xlsx_product_export', 0 );

/**
 * Serve the generated XLSX file using a custom download action.
 */
function tpplt_maybe_download_xlsx_export() {
    if ( empty( $_GET['action'] ) || 'tpplt_download_product_export' !== $_GET['action'] ) { // WPCS: input var ok, sanitization ok.
        return;
    }

    if ( empty( $_GET['filename'] ) || ! wp_verify_nonce( wp_unslash( $_GET['nonce'] ?? '' ), 'product-csv' ) ) { // WPCS: input var ok.
        wp_die( esc_html__( 'Invalid export download request.', 'tpplt' ) );
    }

    $filename   = basename( sanitize_text_field( wp_unslash( $_GET['filename'] ) ) );
    $upload_dir = wp_upload_dir();
    $file_path  = trailingslashit( $upload_dir['basedir'] ) . $filename;

    if ( ! file_exists( $file_path ) ) {
        wp_die( esc_html__( 'The requested export file is not available.', 'tpplt' ) );
    }

    header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Transfer-Encoding: binary' );

    readfile( $file_path );

    @unlink( $file_path );
    @unlink( $file_path . '.headers' );

    exit;
}
add_action( 'admin_init', 'tpplt_maybe_download_xlsx_export', 1 );
