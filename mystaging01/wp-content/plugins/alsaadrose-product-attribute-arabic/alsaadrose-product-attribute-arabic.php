<?php
/**
 * Plugin Name: AlSaadrose Product Attribute Arabic Fields
 * Description: Adds Arabic name and value fields when managing product attributes.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASARAB_META_KEY = '_asarab_attribute_translations';

/**
 * Get stored Arabic attribute map for a product.
 *
 * @param int $post_id Product ID.
 *
 * @return array<string, array<string, mixed>>
 */
function asarab_get_attribute_map( $post_id ) {
    $saved = get_post_meta( $post_id, ASARAB_META_KEY, true );

    if ( ! is_array( $saved ) ) {
        return array();
    }

    return $saved;
}

/**
 * Normalize an attribute key for storage and lookup.
 *
 * @param string $raw_name Attribute name.
 *
 * @return string
 */
function asarab_normalize_attribute_key( $raw_name ) {
    return sanitize_key( $raw_name );
}

/**
 * Render Arabic fields below the standard attribute settings.
 *
 * @param WC_Product_Attribute $attribute Attribute object.
 * @param int                  $index     Attribute index.
 */
function asarab_render_attribute_fields( $attribute, $index ) {
    global $post;

    $post_id = $post instanceof WP_Post ? $post->ID : 0;
    $saved   = $post_id ? asarab_get_attribute_map( $post_id ) : array();
    $key     = asarab_normalize_attribute_key( $attribute->get_name() );

    $arabic_name   = isset( $saved[ $key ]['name'] ) ? $saved[ $key ]['name'] : '';
    $arabic_values = isset( $saved[ $key ]['values'] ) ? $saved[ $key ]['values'] : array();

    if ( is_array( $arabic_values ) ) {
        $arabic_values = implode( ' | ', $arabic_values );
    }
    ?>
    <tr class="asarab-row">
        <td colspan="2">
            <label for="attribute_names_ar_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Arabic name (optional)', 'asarab' ); ?>:</label>
            <input
                type="text"
                class="widefat"
                id="attribute_names_ar_<?php echo esc_attr( $index ); ?>"
                name="attribute_names_ar[<?php echo esc_attr( $index ); ?>]"
                value="<?php echo esc_attr( $arabic_name ); ?>"
            />
        </td>
    </tr>
    <tr class="asarab-row">
        <td colspan="2">
            <label for="attribute_values_ar_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Arabic value(s) (use | to separate options)', 'asarab' ); ?>:</label>
            <textarea
                class="widefat"
                rows="3"
                id="attribute_values_ar_<?php echo esc_attr( $index ); ?>"
                name="attribute_values_ar[<?php echo esc_attr( $index ); ?>]"
            ><?php echo esc_textarea( $arabic_values ); ?></textarea>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_after_product_attribute_settings', 'asarab_render_attribute_fields', 10, 2 );

/**
 * Convert a posted Arabic values string into an array.
 *
 * @param string $raw Raw posted value string.
 *
 * @return string[]
 */
function asarab_parse_value_list( $raw ) {
    $parts = array_map( 'trim', explode( '|', $raw ) );
    $parts = array_filter( $parts, 'strlen' );

    return array_map( 'sanitize_text_field', $parts );
}

/**
 * Save Arabic attribute fields alongside the product.
 *
 * @param WC_Product $product Product object.
 */
function asarab_save_attribute_fields( $product ) {
    if ( ! isset( $_POST['attribute_names'], $_POST['attribute_names_ar'], $_POST['attribute_values_ar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        delete_post_meta( $product->get_id(), ASARAB_META_KEY );
        return;
    }

    $attribute_names    = (array) wp_unslash( $_POST['attribute_names'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $arabic_names       = (array) wp_unslash( $_POST['attribute_names_ar'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $arabic_values      = (array) wp_unslash( $_POST['attribute_values_ar'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $attribute_names_max = empty( $attribute_names ) ? -1 : max( array_keys( $attribute_names ) );
    $map                 = array();

    for ( $i = 0; $i <= $attribute_names_max; $i++ ) {
        if ( empty( $attribute_names[ $i ] ) ) {
            continue;
        }

        $key         = asarab_normalize_attribute_key( wc_clean( $attribute_names[ $i ] ) );
        $name_ar     = isset( $arabic_names[ $i ] ) ? sanitize_text_field( $arabic_names[ $i ] ) : '';
        $values_ar   = isset( $arabic_values[ $i ] ) ? asarab_parse_value_list( $arabic_values[ $i ] ) : array();
        $has_content = ( '' !== $name_ar ) || ! empty( $values_ar );

        if ( $has_content && '' !== $key ) {
            $map[ $key ] = array(
                'name'   => $name_ar,
                'values' => $values_ar,
            );
        }
    }

    if ( empty( $map ) ) {
        delete_post_meta( $product->get_id(), ASARAB_META_KEY );
    } else {
        update_post_meta( $product->get_id(), ASARAB_META_KEY, $map );
    }
}
add_action( 'woocommerce_admin_process_product_object', 'asarab_save_attribute_fields' );
