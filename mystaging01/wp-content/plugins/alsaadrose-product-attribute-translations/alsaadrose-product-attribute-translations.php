<?php
/**
 * Plugin Name: AlSaadrose Product Attribute Arabic Fields
 * Description: Adds Arabic name/value fields to product attributes and displays them when viewing the store in Arabic via TranslatePress.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASPATN_NAMES_META_KEY  = '_asp_at_names_ar';
const ASPATN_VALUES_META_KEY = '_asp_at_values_ar';
const ASPATN_VARIATION_DESCRIPTION_META_KEY = '_asp_at_variation_desc_ar';

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
 * Save Arabic attribute names and values when the product is saved.
 *
 * @param WC_Product $product Product instance.
 */
function aspatn_save_attribute_arabic_fields( $product ) {
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
 * Filter attribute labels on the frontend to use the stored Arabic name when available.
 *
 * @param string     $label   Default label.
 * @param string     $name    Attribute name.
 * @param WC_Product $product Product object.
 * @return string
 */
function aspatn_filter_attribute_label( $label, $name, $product ) {
    // ما نعدل شيء في لوحة التحكم
    if ( is_admin() ) {
        return $label;
    }

    // لو ما وصلنا منتج من الفلتر، جرّب نأخذه من الـ global $product
    if ( ! ( $product instanceof WC_Product ) && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
        $product = $GLOBALS['product'];
    }

    // لو لسه ما في منتج أو اللغة مش عربية، نرجع الاسم الأصلي
    if ( ! aspatn_is_arabic_language() || ! ( $product instanceof WC_Product ) ) {
        return $label;
    }

    $names_map = aspatn_get_saved_attribute_names( $product->get_id() );

    // في بعض الأماكن الاسم يكون "attribute_pa_xxx" فننظفه
    $normalized_name = 0 === strpos( $name, 'attribute_' ) ? substr( $name, strlen( 'attribute_' ) ) : $name;

    // slug بدون pa_ (مثلاً pa_color → color)
    $slug          = wc_attribute_taxonomy_slug( $normalized_name );
    // slug مثل ما خزّنّاه وقت الحفظ (pa_color أو material …)
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
 * Translate custom attribute values in the attributes table on the frontend.
 *
 * @param array       $product_attributes Attributes prepared for display.
 * @param WC_Product  $product            Product object.
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

        // ترجمة الاسم
        if ( isset( $names_map[ $slug ] ) && '' !== $names_map[ $slug ] ) {
            $attribute['label'] = $names_map[ $slug ];
        } elseif ( $fallback_slug !== $slug && isset( $names_map[ $fallback_slug ] ) && '' !== $names_map[ $fallback_slug ] ) {
            $attribute['label'] = $names_map[ $fallback_slug ];
        }

        // ترجمة القيم (نفس الكود القديم)
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
 * Translate variation dropdown option labels for custom attributes.
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

    $slug        = aspatn_sanitize_attribute_slug( $attribute );
    $values_map  = aspatn_get_saved_attribute_values( $product->get_id() );

    if ( isset( $values_map[ $slug ] ) && isset( $values_map[ $slug ][ $term_name ] ) && '' !== $values_map[ $slug ][ $term_name ] ) {
        return $values_map[ $slug ][ $term_name ];
    }

    return $term_name;
}
add_filter( 'woocommerce_variation_option_name', 'aspatn_filter_variation_option_name', 12, 4 );

/**
 * Filter variation descriptions on the frontend to use the Arabic version when available.
 *
 * @param string                $description Variation description.
 * @param WC_Product_Variation  $variation   Variation object.
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

    return $description;
}
add_filter( 'woocommerce_product_variation_get_description', 'aspatn_filter_variation_description', 12, 2 );

/**
 * Enqueue admin assets on the product editor.
 *
 * @param string $hook Current admin page hook.
 */
function aspatn_enqueue_product_admin_assets( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    $screen = get_current_screen();

    if ( empty( $screen ) || 'product' !== $screen->post_type ) {
        return;
    }

    $product_id  = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $names_map   = $product_id ? aspatn_get_saved_attribute_names( $product_id ) : array();
    $values_map  = $product_id ? aspatn_get_saved_attribute_values( $product_id ) : array();

    wp_register_script(
        'aspatn-product-attributes',
        plugin_dir_url( __FILE__ ) . 'assets/js/product-attributes.js',
        array( 'jquery', 'underscore', 'wc-admin-meta-boxes' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        'aspatn-product-attributes',
        'aspatnProductAttributes',
        array(
            'names'        => $names_map,
            'values'       => $values_map,
            'labelPrompt'  => __( 'Arabic name (optional)', 'aspatn' ),
            'valuesPrompt' => __( 'Arabic value(s) (use | to separate options)', 'aspatn' ),
        )
    );

    wp_enqueue_script( 'aspatn-product-attributes' );
}
add_action( 'admin_enqueue_scripts', 'aspatn_enqueue_product_admin_assets' );

/**
 * Add Arabic description input to each variation in the product editor.
 *
 * @param int                   $loop            Variation index.
 * @param array<string, mixed>  $variation_data  Variation data.
 * @param WP_Post               $variation       Variation post object.
 */
function aspatn_add_variation_arabic_description_field( $loop, $variation_data, $variation ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $value = get_post_meta( $variation->ID, ASPATN_VARIATION_DESCRIPTION_META_KEY, true );

    woocommerce_wp_textarea_input(
        array(
            'id'          => 'aspatn_variation_description_ar[' . $variation->ID . ']',
            'label'       => __( 'Arabic description', 'aspatn' ),
            'value'       => $value,
            'desc_tip'    => true,
            'description' => __( 'Optional Arabic description for this variation (shown on Arabic storefront).', 'aspatn' ),
            'wrapper_class' => 'form-row form-row-full',
        )
    );
}
add_action( 'woocommerce_product_after_variable_attributes', 'aspatn_add_variation_arabic_description_field', 20, 3 );

/**
 * Save Arabic description for each variation.
 *
 * @param int   $variation_id Variation ID.
 * @param int   $i            Variation index.
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
