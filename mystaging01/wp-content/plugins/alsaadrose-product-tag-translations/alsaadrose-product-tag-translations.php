<?php
/**
 * Plugin Name: AlSaadrose Product Tag Arabic Fields
 * Description: Adds Arabic name/description fields to product tags and shows them on the Arabic storefront (TranslatePress).
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASPTAG_NAME_AR_META_KEY        = '_asptag_name_ar';
const ASPTAG_DESCRIPTION_AR_META_KEY = '_asptag_description_ar';

/**
 * Detect the current TranslatePress language code.
 *
 * @return string
 */
function asptag_get_trp_language_code() {
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
 * Determine if the current frontend language is Arabic.
 *
 * @return bool
 */
function asptag_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    $code = asptag_get_trp_language_code();

    if ( '' === $code ) {
        return false;
    }

    $code = strtolower( $code );

    return 'ar' === $code || 0 === strpos( $code, 'ar_' ) || 0 === strpos( $code, 'ar-' );
}

/**
 * Render Arabic fields on the "Add new tag" screen.
 */
function asptag_add_tag_fields() {
    ?>
    <div class="form-field term-name-wrap">
        <label for="asptag-name-ar"><?php esc_html_e( 'Arabic Name', 'asptag' ); ?></label>
        <input type="text" name="asptag_name_ar" id="asptag-name-ar" value="" />
        <p class="description"><?php esc_html_e( 'Optional Arabic label shown on the Arabic storefront.', 'asptag' ); ?></p>
    </div>
    <div class="form-field term-description-wrap">
        <label for="asptag-description-ar"><?php esc_html_e( 'Arabic Description', 'asptag' ); ?></label>
        <textarea name="asptag_description_ar" id="asptag-description-ar" rows="4"></textarea>
        <p class="description"><?php esc_html_e( 'Used instead of the default description on Arabic pages.', 'asptag' ); ?></p>
    </div>
    <?php
}
add_action( 'product_tag_add_form_fields', 'asptag_add_tag_fields' );

/**
 * Render Arabic fields on the "Edit tag" screen.
 *
 * @param WP_Term $term Tag term being edited.
 */
function asptag_edit_tag_fields( $term ) {
    $name_ar        = get_term_meta( $term->term_id, ASPTAG_NAME_AR_META_KEY, true );
    $description_ar = get_term_meta( $term->term_id, ASPTAG_DESCRIPTION_AR_META_KEY, true );
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="asptag-name-ar"><?php esc_html_e( 'Arabic Name', 'asptag' ); ?></label></th>
        <td>
            <input type="text" name="asptag_name_ar" id="asptag-name-ar" value="<?php echo esc_attr( $name_ar ); ?>" />
            <p class="description"><?php esc_html_e( 'Optional Arabic label shown on the Arabic storefront.', 'asptag' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="asptag-description-ar"><?php esc_html_e( 'Arabic Description', 'asptag' ); ?></label></th>
        <td>
            <textarea name="asptag_description_ar" id="asptag-description-ar" rows="5"><?php echo esc_textarea( $description_ar ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Used instead of the default description on Arabic pages.', 'asptag' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'product_tag_edit_form_fields', 'asptag_edit_tag_fields', 10, 1 );

/**
 * Save Arabic tag fields on create/edit.
 *
 * @param int $term_id Term ID.
 */
function asptag_save_tag_fields( $term_id ) {
    $name_ar        = isset( $_POST['asptag_name_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['asptag_name_ar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $description_ar = isset( $_POST['asptag_description_ar'] ) ? wp_kses_post( wp_unslash( $_POST['asptag_description_ar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( '' === $name_ar ) {
        delete_term_meta( $term_id, ASPTAG_NAME_AR_META_KEY );
    } else {
        update_term_meta( $term_id, ASPTAG_NAME_AR_META_KEY, $name_ar );
    }

    if ( '' === trim( $description_ar ) ) {
        delete_term_meta( $term_id, ASPTAG_DESCRIPTION_AR_META_KEY );
    } else {
        update_term_meta( $term_id, ASPTAG_DESCRIPTION_AR_META_KEY, $description_ar );
    }
}
add_action( 'created_product_tag', 'asptag_save_tag_fields', 15, 1 );
add_action( 'edited_product_tag', 'asptag_save_tag_fields', 15, 1 );

/**
 * Apply Arabic name/description to term objects on the frontend.
 *
 * @param WP_Term $term     Term object.
 * @param string  $taxonomy Taxonomy slug.
 * @return WP_Term
 */
function asptag_filter_term_for_arabic( $term, $taxonomy ) {
    if ( ! asptag_is_arabic_language() || 'product_tag' !== $taxonomy || ! ( $term instanceof WP_Term ) ) {
        return $term;
    }

    $name_ar        = get_term_meta( $term->term_id, ASPTAG_NAME_AR_META_KEY, true );
    $description_ar = get_term_meta( $term->term_id, ASPTAG_DESCRIPTION_AR_META_KEY, true );

    if ( '' !== $name_ar ) {
        $term->name = $name_ar;
    }

    if ( '' !== trim( $description_ar ) ) {
        $term->description = $description_ar;
    }

    return $term;
}
add_filter( 'get_term', 'asptag_filter_term_for_arabic', 12, 2 );

/**
 * Replace term description output with Arabic version when available.
 *
 * @param string          $description Term description.
 * @param WP_Term|int|nil $term        Term or term ID if available.
 * @return string
 */
function asptag_filter_term_description( $description, $term ) {
    if ( ! asptag_is_arabic_language() ) {
        return $description;
    }

    $term_id = $term instanceof WP_Term ? $term->term_id : ( is_numeric( $term ) ? absint( $term ) : 0 );

    if ( ! $term_id ) {
        return $description;
    }

    $description_ar = get_term_meta( $term_id, ASPTAG_DESCRIPTION_AR_META_KEY, true );

    if ( '' !== trim( $description_ar ) ) {
        return $description_ar;
    }

    return $description;
}
add_filter( 'term_description', 'asptag_filter_term_description', 12, 2 );

/**
 * Ensure tag name translations also apply in WooCommerce breadcrumbs and similar outputs.
 *
 * @param string $name Term name.
 * @param object $term Term object.
 * @return string
 */
function asptag_filter_woocommerce_term_name( $name, $term ) {
    if ( ! ( $term instanceof WP_Term ) || 'product_tag' !== $term->taxonomy || ! asptag_is_arabic_language() ) {
        return $name;
    }

    $name_ar = get_term_meta( $term->term_id, ASPTAG_NAME_AR_META_KEY, true );

    return '' !== $name_ar ? $name_ar : $name;
}
add_filter( 'woocommerce_term_name', 'asptag_filter_woocommerce_term_name', 12, 2 );
