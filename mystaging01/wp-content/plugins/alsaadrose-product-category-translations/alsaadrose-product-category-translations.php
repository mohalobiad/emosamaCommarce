<?php
/**
 * Plugin Name: AlSaadrose Product Category Arabic Fields
 * Description: Adds Arabic name/description/extra description fields to product categories and shows them on the Arabic storefront (TranslatePress).
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASPCAT_NAME_AR_META_KEY             = '_aspcat_name_ar';
const ASPCAT_DESCRIPTION_AR_META_KEY      = '_aspcat_description_ar';
const ASPCAT_EXTRA_DESCRIPTION_AR_META_KEY = '_aspcat_extra_description_ar';

/**
 * Detect the current TranslatePress language code.
 *
 * @return string
 */
function aspcat_get_trp_language_code() {
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
function aspcat_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    $code = aspcat_get_trp_language_code();

    if ( '' === $code ) {
        return false;
    }

    $code = strtolower( $code );

    return 'ar' === $code || 0 === strpos( $code, 'ar_' ) || 0 === strpos( $code, 'ar-' );
}

/**
 * Render Arabic fields on the "Add new category" screen.
 */
function aspcat_add_category_fields() {
    ?>
    <div class="form-field term-name-wrap">
        <label for="aspcat-name-ar"><?php esc_html_e( 'Arabic Name', 'aspcat' ); ?></label>
        <input type="text" name="aspcat_name_ar" id="aspcat-name-ar" value="" />
        <p class="description"><?php esc_html_e( 'Optional Arabic label shown on the Arabic storefront.', 'aspcat' ); ?></p>
    </div>
    <div class="form-field term-description-wrap">
        <label for="aspcat-description-ar"><?php esc_html_e( 'Arabic Description', 'aspcat' ); ?></label>
        <textarea name="aspcat_description_ar" id="aspcat-description-ar" rows="4"></textarea>
        <p class="description"><?php esc_html_e( 'Used instead of the default description on Arabic pages.', 'aspcat' ); ?></p>
    </div>
    <div class="form-field">
        <label for="aspcat-extra-description-ar"><?php esc_html_e( 'Arabic Extra Description', 'aspcat' ); ?></label>
        <textarea name="aspcat_extra_description_ar" id="aspcat-extra-description-ar" rows="4"></textarea>
        <p class="description"><?php esc_html_e( 'Replaces the Woodmart "Extra description" field on Arabic pages.', 'aspcat' ); ?></p>
    </div>
    <?php
}
add_action( 'product_cat_add_form_fields', 'aspcat_add_category_fields' );

/**
 * Render Arabic fields on the "Edit category" screen.
 *
 * @param WP_Term $term Category term being edited.
 */
function aspcat_edit_category_fields( $term ) {
    $name_ar        = get_term_meta( $term->term_id, ASPCAT_NAME_AR_META_KEY, true );
    $description_ar = get_term_meta( $term->term_id, ASPCAT_DESCRIPTION_AR_META_KEY, true );
    $extra_desc_ar  = get_term_meta( $term->term_id, ASPCAT_EXTRA_DESCRIPTION_AR_META_KEY, true );
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="aspcat-name-ar"><?php esc_html_e( 'Arabic Name', 'aspcat' ); ?></label></th>
        <td>
            <input type="text" name="aspcat_name_ar" id="aspcat-name-ar" value="<?php echo esc_attr( $name_ar ); ?>" />
            <p class="description"><?php esc_html_e( 'Optional Arabic label shown on the Arabic storefront.', 'aspcat' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="aspcat-description-ar"><?php esc_html_e( 'Arabic Description', 'aspcat' ); ?></label></th>
        <td>
            <textarea name="aspcat_description_ar" id="aspcat-description-ar" rows="5"><?php echo esc_textarea( $description_ar ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Used instead of the default description on Arabic pages.', 'aspcat' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="aspcat-extra-description-ar"><?php esc_html_e( 'Arabic Extra Description', 'aspcat' ); ?></label></th>
        <td>
            <textarea name="aspcat_extra_description_ar" id="aspcat-extra-description-ar" rows="5"><?php echo esc_textarea( $extra_desc_ar ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Replaces the Woodmart "Extra description" field on Arabic pages.', 'aspcat' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'product_cat_edit_form_fields', 'aspcat_edit_category_fields', 10, 1 );

/**
 * Save Arabic category fields on create/edit.
 *
 * @param int $term_id Term ID.
 */
function aspcat_save_category_fields( $term_id ) {
    $name_ar        = isset( $_POST['aspcat_name_ar'] ) ? sanitize_text_field( wp_unslash( $_POST['aspcat_name_ar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $description_ar = isset( $_POST['aspcat_description_ar'] ) ? wp_kses_post( wp_unslash( $_POST['aspcat_description_ar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $extra_desc_ar  = isset( $_POST['aspcat_extra_description_ar'] ) ? wp_kses_post( wp_unslash( $_POST['aspcat_extra_description_ar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( '' === $name_ar ) {
        delete_term_meta( $term_id, ASPCAT_NAME_AR_META_KEY );
    } else {
        update_term_meta( $term_id, ASPCAT_NAME_AR_META_KEY, $name_ar );
    }

    if ( '' === trim( $description_ar ) ) {
        delete_term_meta( $term_id, ASPCAT_DESCRIPTION_AR_META_KEY );
    } else {
        update_term_meta( $term_id, ASPCAT_DESCRIPTION_AR_META_KEY, $description_ar );
    }

    if ( '' === trim( $extra_desc_ar ) ) {
        delete_term_meta( $term_id, ASPCAT_EXTRA_DESCRIPTION_AR_META_KEY );
    } else {
        update_term_meta( $term_id, ASPCAT_EXTRA_DESCRIPTION_AR_META_KEY, $extra_desc_ar );
    }
}
add_action( 'created_product_cat', 'aspcat_save_category_fields', 15, 1 );
add_action( 'edited_product_cat', 'aspcat_save_category_fields', 15, 1 );

/**
 * Apply Arabic name/description to term objects on the frontend.
 *
 * @param WP_Term $term     Term object.
 * @param string  $taxonomy Taxonomy slug.
 * @return WP_Term
 */
function aspcat_filter_term_for_arabic( $term, $taxonomy ) {
    if ( ! aspcat_is_arabic_language() || 'product_cat' !== $taxonomy || ! ( $term instanceof WP_Term ) ) {
        return $term;
    }

    $name_ar        = get_term_meta( $term->term_id, ASPCAT_NAME_AR_META_KEY, true );
    $description_ar = get_term_meta( $term->term_id, ASPCAT_DESCRIPTION_AR_META_KEY, true );

    if ( '' !== $name_ar ) {
        $term->name = $name_ar;
    }

    if ( '' !== trim( $description_ar ) ) {
        $term->description = $description_ar;
    }

    return $term;
}
add_filter( 'get_term', 'aspcat_filter_term_for_arabic', 12, 2 );

/**
 * Replace term description output with Arabic version when available.
 *
 * @param string          $description Term description.
 * @param WP_Term|int|nil $term        Term or term ID if available.
 * @return string
 */
function aspcat_filter_term_description( $description, $term ) {
    if ( ! aspcat_is_arabic_language() ) {
        return $description;
    }

    $term_id = $term instanceof WP_Term ? $term->term_id : ( is_numeric( $term ) ? absint( $term ) : 0 );

    if ( ! $term_id ) {
        return $description;
    }

    $description_ar = get_term_meta( $term_id, ASPCAT_DESCRIPTION_AR_META_KEY, true );

    if ( '' !== trim( $description_ar ) ) {
        return $description_ar;
    }

    return $description;
}
add_filter( 'term_description', 'aspcat_filter_term_description', 12, 2 );

/**
 * Swap Woodmart extra description with Arabic value when available.
 *
 * @param mixed  $value    Meta value.
 * @param int    $object_id Term ID.
 * @param string $meta_key Meta key.
 * @param bool   $single   Whether a single value was requested.
 * @return mixed
 */
function aspcat_filter_term_meta_for_extra_description( $value, $object_id, $meta_key, $single ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    if ( ! aspcat_is_arabic_language() ) {
        return $value;
    }

    if ( 'category_extra_description_text' !== $meta_key ) {
        return $value;
    }

    $term = get_term( $object_id, 'product_cat' );

    if ( ! ( $term instanceof WP_Term ) ) {
        return $value;
    }

    $extra_desc_ar = get_term_meta( $object_id, ASPCAT_EXTRA_DESCRIPTION_AR_META_KEY, true );

    if ( '' === trim( $extra_desc_ar ) ) {
        return $value;
    }

    return $single ? $extra_desc_ar : array( $extra_desc_ar );
}
add_filter( 'get_term_metadata', 'aspcat_filter_term_meta_for_extra_description', 12, 4 );

/**
 * Ensure category name translations also apply in WooCommerce breadcrumbs and similar outputs.
 *
 * @param string $name   Term name.
 * @param object $term   Term object.
 * @return string
 */
function aspcat_filter_woocommerce_term_name( $name, $term ) {
    if ( ! ( $term instanceof WP_Term ) || 'product_cat' !== $term->taxonomy || ! aspcat_is_arabic_language() ) {
        return $name;
    }

    $name_ar = get_term_meta( $term->term_id, ASPCAT_NAME_AR_META_KEY, true );

    return '' !== $name_ar ? $name_ar : $name;
}
add_filter( 'woocommerce_term_name', 'aspcat_filter_woocommerce_term_name', 12, 2 );
