<?php
/**
 * Plugin Name: AlSaadrose Product Attribute Arabic Fields
 * Description: Adds Arabic name/value fields to product attributes and displays them when viewing the store in Arabic via TranslatePress.
 * Author: AlSaadrose
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASPATN_NAMES_META_KEY  = '_asp_at_names_ar';
const ASPATN_VALUES_META_KEY = '_asp_at_values_ar';
const ASPATN_VARIATION_DESCRIPTION_META_KEY = '_asp_at_variation_desc_ar';

/**
 * Try to detect current product ID in admin / AJAX.
 *
 * @return int
 */
function aspatn_get_current_product_id() {
    // صفحة تحرير المنتج العادية: post.php?post=ID&action=edit.
    if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return absint( $_GET['post'] );
    }

    // طلبات AJAX مثل woocommerce_save_attributes ترسل post_id.
    if ( isset( $_POST['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return absint( $_POST['post_id'] );
    }

    // Fallbacks احتياطية.
    $post_id = get_the_ID();
    if ( $post_id ) {
        return absint( $post_id );
    }

    global $post;
    if ( $post instanceof WP_Post ) {
        return absint( $post->ID );
    }

    return 0;
}

/**
 * Sanitize the attribute slug consistently with WooCommerce.
 *
 * @param string $raw_slug Attribute raw slug.
 * @return string
 */
function aspatn_sanitize_attribute_slug( $raw_slug ) {
    $raw_slug = is_string( $raw_slug ) ? $raw_slug : '';

    if ( '' === $raw_slug ) {
        return '';
    }

    if ( 0 === strpos( $raw_slug, 'pa_' ) ) {
        return wc_sanitize_taxonomy_name( $raw_slug );
    }

    return sanitize_title( $raw_slug );
}

/**
 * Retrieve saved Arabic attribute names for a product.
 *
 * @param int $product_id Product ID.
 * @return array<string, string>
 */
function aspatn_get_saved_attribute_names( $product_id ) {
    $saved = get_post_meta( $product_id, ASPATN_NAMES_META_KEY, true );

    return is_array( $saved ) ? $saved : array();
}

/**
 * Retrieve saved Arabic attribute values for a product.
 *
 * @param int $product_id Product ID.
 * @return array<string, array<string, string>> Map of attribute slug => map of option => Arabic option.
 */
function aspatn_get_saved_attribute_values( $product_id ) {
    $saved = get_post_meta( $product_id, ASPATN_VALUES_META_KEY, true );

    return is_array( $saved ) ? $saved : array();
}

/**
 * Save Arabic attribute names and values when the product is saved
 * from the normal "Update" button.
 *
 * @param WC_Product $product Product instance.
 */
function aspatn_save_attribute_arabic_fields( $product ) {
    // These come from the attributes metabox form.
    if ( ! isset( $_POST['attribute_names'] ) || ! is_array( $_POST['attribute_names'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return;
    }

    $attribute_names      = wc_clean( wp_unslash( $_POST['attribute_names'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $attribute_values     = isset( $_POST['attribute_values'] ) ? wp_unslash( $_POST['attribute_values'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $attribute_names_ar   = isset( $_POST['attribute_names_ar'] ) ? wp_unslash( $_POST['attribute_names_ar'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $attribute_values_ar  = isset( $_POST['attribute_values_ar'] ) ? wp_unslash( $_POST['attribute_values_ar'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    $names_map  = array();
    $values_map = array();

    foreach ( $attribute_names as $index => $posted_name ) {
        $slug = aspatn_sanitize_attribute_slug( $posted_name );

        if ( '' === $slug ) {
            continue;
        }

        $name_ar = isset( $attribute_names_ar[ $index ] ) ? sanitize_text_field( $attribute_names_ar[ $index ] ) : '';

        if ( '' !== $name_ar ) {
            $names_map[ $slug ] = $name_ar;
        }

        // Only handle manual value translations for non-taxonomy attributes (textarea field).
        $raw_values     = isset( $attribute_values[ $index ] ) ? $attribute_values[ $index ] : '';
        $raw_values_ar  = isset( $attribute_values_ar[ $index ] ) ? $attribute_values_ar[ $index ] : '';
        $is_taxonomy    = isset( $_POST['attribute_is_taxonomy'][ $index ] ) ? (bool) intval( $_POST['attribute_is_taxonomy'][ $index ] ) : ( 0 === strpos( $slug, 'pa_' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( $is_taxonomy ) {
            continue;
        }

        $original_options = array_filter( array_map( 'trim', explode( '|', (string) $raw_values ) ), 'strlen' );
        $arabic_parts     = array_map( 'sanitize_text_field', array_map( 'trim', explode( '|', (string) $raw_values_ar ) ) );

        $option_map = array();

        foreach ( $original_options as $option_index => $option_value ) {
            if ( isset( $arabic_parts[ $option_index ] ) && '' !== $arabic_parts[ $option_index ] ) {
                $option_map[ $option_value ] = $arabic_parts[ $option_index ];
            }
        }

        if ( ! empty( $option_map ) ) {
            $values_map[ $slug ] = $option_map;
        }
    }

    if ( empty( $names_map ) ) {
        delete_post_meta( $product->get_id(), ASPATN_NAMES_META_KEY );
    } else {
        update_post_meta( $product->get_id(), ASPATN_NAMES_META_KEY, $names_map );
    }

    if ( empty( $values_map ) ) {
        delete_post_meta( $product->get_id(), ASPATN_VALUES_META_KEY );
    } else {
        update_post_meta( $product->get_id(), ASPATN_VALUES_META_KEY, $values_map );
    }
}
add_action( 'woocommerce_admin_process_product_object', 'aspatn_save_attribute_arabic_fields', 25 );

/**
 * Detect the current TranslatePress language code.
 *
 * @return string
 */
function aspatn_get_trp_language_code() {
    if ( function_exists( 'trp_get_current_language' ) ) {
        $lang = trp_get_current_language();

        if ( is_string( $lang ) ) {
            return $lang;
        }
    }

    if ( isset( $GLOBALS['TRP_LANGUAGE'] ) && is_string( $GLOBALS['TRP_LANGUAGE'] ) ) {
        return $GLOBALS['TRP_LANGUAGE'];
    }

    return '';
}

/**
 * Check if the current TranslatePress language is Arabic on the frontend.
 *
 * @return bool
 */
function aspatn_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    $code = aspatn_get_trp_language_code();

    if ( '' === $code ) {
        return false;
    }

    $code = strtolower( $code );

    return 'ar' === $code || 0 === strpos( $code, 'ar_' ) || 0 === strpos( $code, 'ar-' );
}

/**
 * FRONTEND: Filter attribute labels to use the stored Arabic name when available.
 *
 * @param string     $label   Default label.
 * @param string     $name    Attribute name.
 * @param WC_Product $product Product object.
 * @return string
 */
function aspatn_filter_attribute_label( $label, $name, $product ) {
    if ( is_admin() ) {
        return $label;
    }

    if ( ! ( $product instanceof WC_Product ) && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
        $product = $GLOBALS['product'];
    }

    if ( ! aspatn_is_arabic_language() || ! ( $product instanceof WC_Product ) ) {
        return $label;
    }

    $names_map = aspatn_get_saved_attribute_names( $product->get_id() );

    $normalized_name = 0 === strpos( $name, 'attribute_' ) ? substr( $name, strlen( 'attribute_' ) ) : $name;

    $slug          = wc_attribute_taxonomy_slug( $normalized_name );
    $fallback_slug = aspatn_sanitize_attribute_slug( $normalized_name );

    if ( isset( $names_map[ $slug ] ) && '' !== $names_map[ $slug ] ) {
        return $names_map[ $slug ];
    }

    if ( $fallback_slug !== $slug && isset( $names_map[ $fallback_slug ] ) && '' !== $names_map[ $fallback_slug ] ) {
        return $names_map[ $fallback_slug ];
    }

    return $label;
}
add_filter( 'woocommerce_attribute_label', 'aspatn_filter_attribute_label', 12, 3 );

/**
 * FRONTEND: Translate custom attribute values in the attributes table.
 *
 * @param array      $product_attributes Attributes prepared for display.
 * @param WC_Product $product            Product object.
 * @return array
 */
function aspatn_filter_display_product_attributes( $product_attributes, $product ) {
    if ( ! aspatn_is_arabic_language() ) {
        return $product_attributes;
    }

    $values_map = aspatn_get_saved_attribute_values( $product->get_id() );
    $names_map  = aspatn_get_saved_attribute_names( $product->get_id() );

    foreach ( $product_attributes as $key => &$attribute ) {
        $raw_slug      = str_replace( 'attribute_', '', $key );
        $slug          = wc_attribute_taxonomy_slug( $raw_slug );
        $fallback_slug = aspatn_sanitize_attribute_slug( $raw_slug );

        if ( isset( $names_map[ $slug ] ) && '' !== $names_map[ $slug ] ) {
            $attribute['label'] = $names_map[ $slug ];
        } elseif ( $fallback_slug !== $slug && isset( $names_map[ $fallback_slug ] ) && '' !== $names_map[ $fallback_slug ] ) {
            $attribute['label'] = $names_map[ $fallback_slug ];
        }

        if ( isset( $values_map[ $slug ] ) && ! empty( $values_map[ $slug ] ) ) {
            $translated_values = array();
            $value_map         = $values_map[ $slug ];
            $raw_values        = wp_strip_all_tags( wp_kses( $attribute['value'], array() ) );
            $pieces            = array_map( 'trim', explode( ',', $raw_values ) );

            foreach ( $pieces as $piece ) {
                $translated_values[] = isset( $value_map[ $piece ] ) && '' !== $value_map[ $piece ] ? $value_map[ $piece ] : $piece;
            }

            $attribute['value'] = wpautop( wptexturize( implode( ', ', $translated_values ) ) );
        }
    }

    return $product_attributes;
}
add_filter( 'woocommerce_display_product_attributes', 'aspatn_filter_display_product_attributes', 12, 2 );

/**
 * FRONTEND: Translate variation dropdown option labels for custom attributes.
 *
 * @param string          $term_name Option label.
 * @param WP_Term|string  $term      Term object or raw value.
 * @param string          $attribute Attribute name/slug.
 * @param WC_Product|null $product   Product object.
 * @return string
 */
function aspatn_filter_variation_option_name( $term_name, $term, $attribute, $product ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    if ( ! aspatn_is_arabic_language() || ! ( $product instanceof WC_Product ) ) {
        return $term_name;
    }

    $slug       = aspatn_sanitize_attribute_slug( $attribute );
    $values_map = aspatn_get_saved_attribute_values( $product->get_id() );

    if ( isset( $values_map[ $slug ] ) && isset( $values_map[ $slug ][ $term_name ] ) && '' !== $values_map[ $slug ][ $term_name ] ) {
        return $values_map[ $slug ][ $term_name ];
    }

    return $term_name;
}
add_filter( 'woocommerce_variation_option_name', 'aspatn_filter_variation_option_name', 12, 4 );

/**
 * FRONTEND: Filter variation descriptions to use the Arabic version when available.
 *
 * @param string               $description Variation description.
 * @param WC_Product_Variation $variation   Variation object.
 * @return string
 */
function aspatn_filter_variation_description( $description, $variation ) {
    if ( ! aspatn_is_arabic_language() || ! ( $variation instanceof WC_Product_Variation ) ) {
        return $description;
    }

    $saved = get_post_meta( $variation->get_id(), ASPATN_VARIATION_DESCRIPTION_META_KEY, true );

    if ( is_string( $saved ) && '' !== trim( $saved ) ) {
        return $saved;
    }

    // إذا ما في وصف للـ variation وما في ترجمة عربية، لا نستعير أي نص ثاني.
    if ( '' === trim( (string) $description ) ) {
        return '';
    }

    return $description;
}
add_filter( 'woocommerce_product_variation_get_description', 'aspatn_filter_variation_description', 12, 2 );

/**
 * ADMIN: Render Arabic fields inside each attribute row
 * (works for existing attributes and for the JS template used when adding new ones).
 *
 * @param WC_Product_Attribute $attribute Attribute object.
 * @param int                  $i         Attribute index.
 */
function aspatn_render_attribute_arabic_fields( $attribute, $i ) {
    // نسمح بالتنفيذ في شاشة الأدمن وكذلك في طلبات AJAX الخاصة بحفظ الـ attributes.
    if ( ! is_admin() && ! wp_doing_ajax() ) {
        return;
    }

    $product_id = aspatn_get_current_product_id();

    if ( ! $product_id ) {
        return;
    }

    $names_map  = aspatn_get_saved_attribute_names( $product_id );
    $values_map = aspatn_get_saved_attribute_values( $product_id );

    $raw_name = $attribute->get_name(); // e.g. 'citya' or 'pa_color'.
    $slug     = aspatn_sanitize_attribute_slug( $raw_name );

    $current_name_ar = isset( $names_map[ $slug ] ) ? $names_map[ $slug ] : '';

    $arabic_values_string = '';
    $options              = array();

    // For custom (non-taxonomy) attributes, we allow manual “|” separated Arabic values.
    if ( ! $attribute->is_taxonomy() ) {
        $options = $attribute->get_options(); // raw options like [ 'aleppo', 'Dara' ].

        if ( ! empty( $options ) && isset( $values_map[ $slug ] ) && is_array( $values_map[ $slug ] ) ) {
            $map         = $values_map[ $slug ];
            $pieces_ar   = array();

            foreach ( $options as $opt ) {
                $opt_str     = (string) $opt;
                $pieces_ar[] = isset( $map[ $opt_str ] ) ? $map[ $opt_str ] : '';
            }

            $arabic_values_string = implode( ' | ', $pieces_ar );
        }
    }

    ?>
    <tr class="aspatn-attribute-extra">
        <td colspan="2">
            <div class="aspatn-field aspatn-name">
                <label><?php esc_html_e( 'Arabic name (optional):', 'aspatn' ); ?></label>
                <input type="text"
                       name="attribute_names_ar[<?php echo esc_attr( $i ); ?>]"
                       value="<?php echo esc_attr( $current_name_ar ); ?>" />
            </div>

            <?php if ( ! empty( $options ) ) : ?>
                <div class="aspatn-field aspatn-values">
                    <label><?php esc_html_e( 'Arabic value(s) (use | to separate options):', 'aspatn' ); ?></label>
                    <textarea name="attribute_values_ar[<?php echo esc_attr( $i ); ?>]"
                              rows="3"><?php echo esc_textarea( $arabic_values_string ); ?></textarea>
                </div>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_after_product_attribute_settings', 'aspatn_render_attribute_arabic_fields', 10, 2 );

/**
 * ADMIN: Add Arabic description input to each variation in the product editor.
 *
 * @param int                  $loop           Variation index.
 * @param array<string, mixed> $variation_data Variation data.
 * @param WP_Post              $variation      Variation post object.
 */
function aspatn_add_variation_arabic_description_field( $loop, $variation_data, $variation ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $value = get_post_meta( $variation->ID, ASPATN_VARIATION_DESCRIPTION_META_KEY, true );

    woocommerce_wp_textarea_input(
        array(
            'id'            => 'aspatn_variation_description_ar[' . $variation->ID . ']',
            'label'         => __( 'Arabic description', 'aspatn' ),
            'value'         => $value,
            'desc_tip'      => true,
            'description'   => __( 'Optional Arabic description for this variation (shown on Arabic storefront).', 'aspatn' ),
            'wrapper_class' => 'form-row form-row-full',
        )
    );
}
add_action( 'woocommerce_product_after_variable_attributes', 'aspatn_add_variation_arabic_description_field', 20, 3 );

/**
 * ADMIN: Save Arabic description for each variation.
 *
 * @param int $variation_id Variation ID.
 * @param int $i            Variation index.
 */
function aspatn_save_variation_arabic_description( $variation_id, $i ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $posted = isset( $_POST['aspatn_variation_description_ar'][ $variation_id ] ) ? wp_unslash( $_POST['aspatn_variation_description_ar'][ $variation_id ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $value  = is_string( $posted ) ? wp_kses_post( $posted ) : '';

    if ( '' === trim( $value ) ) {
        delete_post_meta( $variation_id, ASPATN_VARIATION_DESCRIPTION_META_KEY );
    } else {
        update_post_meta( $variation_id, ASPATN_VARIATION_DESCRIPTION_META_KEY, $value );
    }
}
add_action( 'woocommerce_save_product_variation', 'aspatn_save_variation_arabic_description', 20, 2 );

/**
 * ADMIN (AJAX): Make the "Save attributes" button also save Arabic
 * name/value meta, using the same logic as on full product save.
 */
function aspatn_ajax_save_attribute_arabic_fields() {
    if ( empty( $_POST['post_id'] ) || empty( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return;
    }

    $product_id = absint( $_POST['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    // WooCommerce sends the attributes form in 'data' as a serialized query string.
    $parsed = array();
    parse_str( wp_unslash( $_POST['data'] ), $parsed ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( empty( $parsed['attribute_names'] ) || ! is_array( $parsed['attribute_names'] ) ) {
        return;
    }

    // Temporarily inject parsed data into $_POST so aspatn_save_attribute_arabic_fields()
    // sees the same structure it expects.
    $backup_post = $_POST;
    foreach ( $parsed as $key => $value ) {
        $_POST[ $key ] = $value;
    }

    $product = wc_get_product( $product_id );
    if ( $product instanceof WC_Product ) {
        aspatn_save_attribute_arabic_fields( $product );
    }

    // Restore original $_POST so we don't affect WooCommerce's own handler.
    $_POST = $backup_post;
}
// Run before WooCommerce's own ajax handler (which is normally priority 10).
add_action( 'wp_ajax_woocommerce_save_attributes', 'aspatn_ajax_save_attribute_arabic_fields', 5 );

/**
 * ADMIN (AJAX): Return saved Arabic attribute names/values for a product.
 * Used by admin JS after "Save attributes" to repopulate fields.
 */
function aspatn_ajax_get_attribute_translations() {
    if ( empty( $_POST['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        wp_send_json_error( array( 'message' => 'Missing product_id' ) );
    }

    $product_id = absint( $_POST['product_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( ! $product_id ) {
        wp_send_json_error( array( 'message' => 'Invalid product_id' ) );
    }

    $names  = aspatn_get_saved_attribute_names( $product_id );
    $values = aspatn_get_saved_attribute_values( $product_id );

    wp_send_json_success(
        array(
            'names'  => $names,
            'values' => $values,
        )
    );
}
add_action( 'wp_ajax_aspatn_get_attribute_translations', 'aspatn_ajax_get_attribute_translations' );
