<?php
/**
 * Plugin Name: AlSaadrose Product Attribute Arabic Fields
 * Description: Adds Arabic name and value fields to product attributes and displays them when the store language is Arabic via TranslatePress.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASPAT_NAMES_META_KEY  = '_aspat_attribute_names_ar';
const ASPAT_VALUES_META_KEY = '_aspat_attribute_values_ar';

/**
 * Determine if the current TranslatePress language is Arabic on the frontend.
 *
 * @return bool
 */
function aspat_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    $language = '';

    if ( function_exists( 'trp_get_current_language' ) ) {
        $language = trp_get_current_language();
    } elseif ( isset( $GLOBALS['TRP_LANGUAGE'] ) ) {
        $language = $GLOBALS['TRP_LANGUAGE'];
    }

    if ( ! is_string( $language ) ) {
        return false;
    }

    $language = strtolower( $language );

    return 'ar' === $language || 0 === strpos( $language, 'ar_' ) || 0 === strpos( $language, 'ar-' );
}

/**
 * Get stored Arabic attribute names for a product.
 *
 * @param int $product_id Product ID.
 *
 * @return array<string, string>
 */
function aspat_get_attribute_names_map( $product_id ) {
    $names = get_post_meta( $product_id, ASPAT_NAMES_META_KEY, true );

    return is_array( $names ) ? $names : array();
}

/**
 * Get stored Arabic attribute values for a product.
 *
 * @param int $product_id Product ID.
 *
 * @return array<string, string>
 */
function aspat_get_attribute_values_map( $product_id ) {
    $values = get_post_meta( $product_id, ASPAT_VALUES_META_KEY, true );

    return is_array( $values ) ? $values : array();
}

/**
 * Normalize an attribute key for storage and lookup.
 *
 * @param WC_Product_Attribute|string $attribute Attribute object or name.
 *
 * @return string
 */
function aspat_normalize_attribute_key( $attribute ) {
    if ( $attribute instanceof WC_Product_Attribute ) {
        if ( $attribute->is_taxonomy() ) {
            return sanitize_title( $attribute->get_taxonomy() );
        }

        return sanitize_title( $attribute->get_name() );
    }

    return sanitize_title( (string) $attribute );
}

/**
 * Render Arabic fields inside each attribute block on the product edit screen.
 *
 * @param WC_Product_Attribute $attribute Attribute object.
 * @param int                  $index     Attribute index.
 */
function aspat_render_attribute_fields( $attribute, $index ) {
    global $post;

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $names_map  = aspat_get_attribute_names_map( $post->ID );
    $values_map = aspat_get_attribute_values_map( $post->ID );
    $key        = aspat_normalize_attribute_key( $attribute );

    $saved_name  = isset( $names_map[ $key ] ) ? $names_map[ $key ] : '';
    $saved_value = isset( $values_map[ $key ] ) ? $values_map[ $key ] : '';
    ?>
    <tr class="aspat-attribute-row">
        <td class="attribute_name">
            <label><?php esc_html_e( 'Arabic name', 'aspat' ); ?>:</label>
            <input type="text" name="attribute_names_ar[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $saved_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Arabic label', 'aspat' ); ?>" />
        </td>
        <td class="attribute_values">
            <label><?php esc_html_e( 'Arabic value(s)', 'aspat' ); ?>:</label>
            <textarea name="attribute_values_ar[<?php echo esc_attr( $index ); ?>]" cols="5" rows="3" placeholder="<?php esc_attr_e( 'Use "|" to separate options.', 'aspat' ); ?>"><?php echo esc_textarea( $saved_value ); ?></textarea>
            <p class="description"><?php esc_html_e( 'These values are shown when the store language is Arabic.', 'aspat' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_after_product_attribute_settings', 'aspat_render_attribute_fields', 10, 2 );

/**
 * Save Arabic attribute names and values with the product.
 *
 * @param WC_Product $product Product object being saved.
 */
function aspat_save_attribute_fields( $product ) {
    $posted_names       = isset( $_POST['attribute_names'] ) ? (array) $_POST['attribute_names'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $posted_names_ar    = isset( $_POST['attribute_names_ar'] ) ? (array) $_POST['attribute_names_ar'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $posted_values_ar   = isset( $_POST['attribute_values_ar'] ) ? (array) $_POST['attribute_values_ar'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    $names_map  = array();
    $values_map = array();

    foreach ( $posted_names as $index => $attribute_name ) {
        $key = sanitize_title( wc_clean( wp_unslash( $attribute_name ) ) );

        if ( '' === $key ) {
            continue;
        }

        $arabic_name = isset( $posted_names_ar[ $index ] ) ? wc_clean( wp_unslash( $posted_names_ar[ $index ] ) ) : '';
        $arabic_val  = isset( $posted_values_ar[ $index ] ) ? wp_kses_post( wp_unslash( $posted_values_ar[ $index ] ) ) : '';

        if ( '' !== $arabic_name ) {
            $names_map[ $key ] = $arabic_name;
        }

        if ( '' !== $arabic_val ) {
            $values_map[ $key ] = $arabic_val;
        }
    }

    if ( empty( $names_map ) ) {
        delete_post_meta( $product->get_id(), ASPAT_NAMES_META_KEY );
    } else {
        update_post_meta( $product->get_id(), ASPAT_NAMES_META_KEY, $names_map );
    }

    if ( empty( $values_map ) ) {
        delete_post_meta( $product->get_id(), ASPAT_VALUES_META_KEY );
    } else {
        update_post_meta( $product->get_id(), ASPAT_VALUES_META_KEY, $values_map );
    }
}
add_action( 'woocommerce_admin_process_product_object', 'aspat_save_attribute_fields' );

/**
 * Swap attribute labels and values to their Arabic versions on the frontend when available.
 *
 * @param array      $attributes Attributes ready for display.
 * @param WC_Product $product    Product being rendered.
 *
 * @return array
 */
function aspat_filter_display_attributes( $attributes, $product ) {
    if ( ! aspat_is_arabic_language() ) {
        return $attributes;
    }

    $names_map  = aspat_get_attribute_names_map( $product->get_id() );
    $values_map = aspat_get_attribute_values_map( $product->get_id() );

    foreach ( $attributes as $key => $data ) {
        $slug = str_replace( 'attribute_', '', $key );

        if ( isset( $names_map[ $slug ] ) && '' !== $names_map[ $slug ] ) {
            $attributes[ $key ]['label'] = $names_map[ $slug ];
        }

        if ( isset( $values_map[ $slug ] ) && '' !== $values_map[ $slug ] ) {
            $values             = wc_get_text_attributes( $values_map[ $slug ] );
            $values             = array_map( 'esc_html', $values );
            $values             = array_map( 'make_clickable', $values );
            $attributes[ $key ]['value'] = wpautop( wptexturize( implode( ', ', $values ) ) );
        }
    }

    return $attributes;
}
add_filter( 'woocommerce_display_product_attributes', 'aspat_filter_display_attributes', 10, 2 );
