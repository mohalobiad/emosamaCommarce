<?php
/**
 * Plugin Name: AlSaadrose Attribute Translations
 * Description: Adds Arabic attribute name fields and shows them when viewing the store in Arabic via TranslatePress.
 * Author: AlSaadrose
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ASATTR_OPTION_KEY = 'asattr_attribute_names_ar';

/**
 * Get the saved Arabic attribute names.
 *
 * @return array<string, string>
 */
function asattr_get_arabic_attribute_names() {
    $stored = get_option( ASATTR_OPTION_KEY, array() );

    if ( ! is_array( $stored ) ) {
        return array();
    }

    return $stored;
}

/**
 * Save or remove an Arabic attribute name by slug.
 *
 * @param string $slug Attribute slug.
 * @param string $value Arabic attribute name.
 */
function asattr_save_attribute_name( $slug, $value ) {
    $slug = wc_sanitize_taxonomy_name( $slug );

    if ( '' === $slug ) {
        return;
    }

    $names = asattr_get_arabic_attribute_names();

    if ( '' === $value ) {
        unset( $names[ $slug ] );
    } else {
        $names[ $slug ] = $value;
    }

    update_option( ASATTR_OPTION_KEY, $names );
}

/**
 * Remove an Arabic attribute name by slug.
 *
 * @param string $slug Attribute slug.
 */
function asattr_remove_attribute_name( $slug ) {
    asattr_save_attribute_name( $slug, '' );
}

/**
 * Render the Arabic name field when adding a new attribute.
 */
function asattr_render_add_field() {
    ?>
    <div class="form-field">
        <label for="name_AR"><?php esc_html_e( 'Arabic name', 'asattr' ); ?></label>
        <input name="name_AR" id="name_AR" type="text" value="" />
        <p class="description"><?php esc_html_e( 'Optional Arabic label used when viewing the store in Arabic.', 'asattr' ); ?></p>
    </div>
    <?php
}
add_action( 'woocommerce_after_add_attribute_fields', 'asattr_render_add_field' );

/**
 * Retrieve the attribute slug for the attribute currently being edited.
 *
 * @return string
 */
function asattr_get_current_attribute_slug() {
    $attribute_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( ! $attribute_id ) {
        return '';
    }

    foreach ( wc_get_attribute_taxonomies() as $attribute ) {
        if ( (int) $attribute->attribute_id === $attribute_id ) {
            return $attribute->attribute_name;
        }
    }

    return '';
}

/**
 * Render the Arabic name field when editing an attribute.
 */
function asattr_render_edit_field() {
    $slug       = asattr_get_current_attribute_slug();
    $arabic     = '';
    $saved      = asattr_get_arabic_attribute_names();
    $has_saved  = isset( $saved[ $slug ] );

    if ( $has_saved ) {
        $arabic = $saved[ $slug ];
    }
    ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="name_AR"><?php esc_html_e( 'Arabic name', 'asattr' ); ?></label>
        </th>
        <td>
            <input name="name_AR" id="name_AR" type="text" value="<?php echo esc_attr( $arabic ); ?>" />
            <p class="description"><?php esc_html_e( 'Optional Arabic label used when viewing the store in Arabic.', 'asattr' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_after_edit_attribute_fields', 'asattr_render_edit_field' );

/**
 * Persist the Arabic name when a new attribute is created.
 *
 * @param int   $id   Attribute ID.
 * @param array $data Attribute data.
 */
function asattr_handle_attribute_added( $id, $data ) {
    $arabic_name = isset( $_POST['name_AR'] ) ? sanitize_text_field( wp_unslash( $_POST['name_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( '' === $arabic_name ) {
        return;
    }

    if ( empty( $data['attribute_name'] ) ) {
        return;
    }

    asattr_save_attribute_name( $data['attribute_name'], $arabic_name );
}
add_action( 'woocommerce_attribute_added', 'asattr_handle_attribute_added', 10, 2 );

/**
 * Persist the Arabic name when an attribute is updated.
 *
 * @param int    $id       Attribute ID.
 * @param array  $data     Attribute data.
 * @param string $old_slug Old attribute slug.
 */
function asattr_handle_attribute_updated( $id, $data, $old_slug ) {
    $arabic_name = isset( $_POST['name_AR'] ) ? sanitize_text_field( wp_unslash( $_POST['name_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( empty( $data['attribute_name'] ) ) {
        return;
    }

    $slug = $data['attribute_name'];

    asattr_save_attribute_name( $slug, $arabic_name );

    if ( $old_slug && $old_slug !== $slug ) {
        asattr_remove_attribute_name( $old_slug );
    }
}
add_action( 'woocommerce_attribute_updated', 'asattr_handle_attribute_updated', 10, 3 );

/**
 * Clean up saved Arabic names when an attribute is deleted.
 *
 * @param int    $id       Attribute ID.
 * @param string $name     Attribute name.
 * @param string $taxonomy Attribute taxonomy name.
 */
function asattr_handle_attribute_deleted( $id, $name, $taxonomy ) {
    $slug = $taxonomy ? wc_attribute_taxonomy_slug( $taxonomy ) : wc_sanitize_taxonomy_name( $name );
    asattr_remove_attribute_name( $slug );
}
add_action( 'woocommerce_attribute_deleted', 'asattr_handle_attribute_deleted', 10, 3 );

/**
 * Detect the current TranslatePress language code.
 *
 * @return string
 */
function asattr_get_trp_language_code() {
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
 * Check if the current TranslatePress language is Arabic.
 *
 * @return bool
 */
function asattr_is_arabic_language() {
    if ( is_admin() ) {
        return false;
    }

    $code = asattr_get_trp_language_code();

    if ( '' === $code ) {
        return false;
    }

    $code = strtolower( $code );

    return 'ar' === $code || 0 === strpos( $code, 'ar_' ) || 0 === strpos( $code, 'ar-' );
}

/**
 * Replace attribute labels with their Arabic equivalents on the frontend.
 *
 * @param string     $label   Default label.
 * @param string     $name    Attribute name.
 * @param WC_Product $product Product object.
 *
 * @return string
 */
function asattr_filter_attribute_label( $label, $name, $product ) {
    if ( is_admin() ) {
        return $label;
    }

    if ( ! asattr_is_arabic_language() ) {
        return $label;
    }

    $slug       = wc_attribute_taxonomy_slug( $name );
    $arabic_map = asattr_get_arabic_attribute_names();

    if ( isset( $arabic_map[ $slug ] ) && '' !== $arabic_map[ $slug ] ) {
        return $arabic_map[ $slug ];
    }

    return $label;
}
add_filter( 'woocommerce_attribute_label', 'asattr_filter_attribute_label', 10, 3 );
