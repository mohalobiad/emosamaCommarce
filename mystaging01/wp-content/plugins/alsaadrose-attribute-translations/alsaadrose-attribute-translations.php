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

const ASATTR_OPTION_KEY               = 'asattr_attribute_names_ar';
const ASATTR_TERM_OPTION_KEY          = 'asattr_attribute_term_names_ar';
const ASATTR_HINT_OPTION_KEY          = 'asattr_attribute_hints_ar';
const ASATTR_TERM_HINT_OPTION_KEY     = 'asattr_attribute_term_hints_ar';

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
 * Get the saved Arabic attribute term names.
 *
 * @return array<string, array<string, string>>
 */
function asattr_get_arabic_attribute_term_names() {
    $stored = get_option( ASATTR_TERM_OPTION_KEY, array() );

    if ( ! is_array( $stored ) ) {
        return array();
    }

    return $stored;
}

/**
 * Get the saved Arabic attribute hints.
 *
 * @return array<string, string>
 */
function asattr_get_arabic_attribute_hints() {
    $stored = get_option( ASATTR_HINT_OPTION_KEY, array() );

    if ( ! is_array( $stored ) ) {
        return array();
    }

    return $stored;
}

/**
 * Get the saved Arabic attribute term hints.
 *
 * @return array<string, array<string, string>>
 */
function asattr_get_arabic_attribute_term_hints() {
    $stored = get_option( ASATTR_TERM_HINT_OPTION_KEY, array() );

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
 * Save or remove an Arabic attribute hint by slug.
 *
 * @param string $slug  Attribute slug.
 * @param string $value Arabic attribute hint content.
 */
function asattr_save_attribute_hint( $slug, $value ) {
    $slug = wc_sanitize_taxonomy_name( $slug );

    if ( '' === $slug ) {
        return;
    }

    $hints = asattr_get_arabic_attribute_hints();

    if ( '' === $value ) {
        unset( $hints[ $slug ] );
    } else {
        $hints[ $slug ] = $value;
    }

    update_option( ASATTR_HINT_OPTION_KEY, $hints );
}

/**
 * Remove an Arabic attribute hint by slug.
 *
 * @param string $slug Attribute slug.
 */
function asattr_remove_attribute_hint( $slug ) {
    asattr_save_attribute_hint( $slug, '' );
}

/**
 * Save or remove an Arabic term name for a given taxonomy/slug pair.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 * @param string $value    Arabic term name.
 */
function asattr_save_attribute_term_name( $taxonomy, $slug, $value ) {
    $taxonomy = sanitize_key( $taxonomy );
    $slug     = sanitize_title( $slug );

    if ( '' === $taxonomy || '' === $slug ) {
        return;
    }

    $names = asattr_get_arabic_attribute_term_names();

    if ( ! isset( $names[ $taxonomy ] ) || ! is_array( $names[ $taxonomy ] ) ) {
        $names[ $taxonomy ] = array();
    }

    if ( '' === $value ) {
        unset( $names[ $taxonomy ][ $slug ] );
        if ( empty( $names[ $taxonomy ] ) ) {
            unset( $names[ $taxonomy ] );
        }
    } else {
        $names[ $taxonomy ][ $slug ] = $value;
    }

    update_option( ASATTR_TERM_OPTION_KEY, $names );
}

/**
 * Remove stored Arabic term name.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 */
function asattr_remove_attribute_term_name( $taxonomy, $slug ) {
    asattr_save_attribute_term_name( $taxonomy, $slug, '' );
}

/**
 * Save or remove an Arabic term hint for a given taxonomy/slug pair.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 * @param string $value    Arabic term hint content.
 */
function asattr_save_attribute_term_hint( $taxonomy, $slug, $value ) {
    $taxonomy = sanitize_key( $taxonomy );
    $slug     = sanitize_title( $slug );

    if ( '' === $taxonomy || '' === $slug ) {
        return;
    }

    $hints = asattr_get_arabic_attribute_term_hints();

    if ( ! isset( $hints[ $taxonomy ] ) || ! is_array( $hints[ $taxonomy ] ) ) {
        $hints[ $taxonomy ] = array();
    }

    if ( '' === $value ) {
        unset( $hints[ $taxonomy ][ $slug ] );
        if ( empty( $hints[ $taxonomy ] ) ) {
            unset( $hints[ $taxonomy ] );
        }
    } else {
        $hints[ $taxonomy ][ $slug ] = $value;
    }

    update_option( ASATTR_TERM_HINT_OPTION_KEY, $hints );
}

/**
 * Remove stored Arabic term hint.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 */
function asattr_remove_attribute_term_hint( $taxonomy, $slug ) {
    asattr_save_attribute_term_hint( $taxonomy, $slug, '' );
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
    <div class="form-field">
        <label for="hint_AR"><?php esc_html_e( 'Arabic hint content', 'asattr' ); ?></label>
        <textarea name="hint_AR" id="hint_AR" rows="5"></textarea>
        <p class="description"><?php esc_html_e( 'Optional Arabic hint used when viewing the store in Arabic.', 'asattr' ); ?></p>
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
    $hint_saved = asattr_get_arabic_attribute_hints();
    $hint_value = isset( $hint_saved[ $slug ] ) ? $hint_saved[ $slug ] : '';

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
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="hint_AR"><?php esc_html_e( 'Arabic hint content', 'asattr' ); ?></label>
        </th>
        <td>
            <textarea name="hint_AR" id="hint_AR" rows="5"><?php echo esc_textarea( $hint_value ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Optional Arabic hint used when viewing the store in Arabic.', 'asattr' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'woocommerce_after_edit_attribute_fields', 'asattr_render_edit_field' );

/**
 * Check if a taxonomy is an attribute taxonomy.
 *
 * @param string $taxonomy Taxonomy name.
 *
 * @return bool
 */
function asattr_is_attribute_taxonomy( $taxonomy ) {
    return 0 === strpos( $taxonomy, 'pa_' );
}

/**
 * Render Arabic name input on add term form for attribute taxonomies.
 *
 * @param string $taxonomy Taxonomy name.
 */
function asattr_render_term_add_field( $taxonomy ) {
    if ( ! asattr_is_attribute_taxonomy( $taxonomy ) ) {
        return;
    }
    ?>
    <div class="form-field">
        <label for="name_AR"><?php esc_html_e( 'Arabic name', 'asattr' ); ?></label>
        <input name="name_AR" id="name_AR" type="text" value="" />
        <p class="description"><?php esc_html_e( 'Optional Arabic label used when viewing the store in Arabic.', 'asattr' ); ?></p>
    </div>
    <div class="form-field">
        <label for="hint_AR"><?php esc_html_e( 'Arabic hint content', 'asattr' ); ?></label>
        <textarea name="hint_AR" id="hint_AR" rows="5"></textarea>
        <p class="description"><?php esc_html_e( 'Optional Arabic hint used when viewing the store in Arabic.', 'asattr' ); ?></p>
    </div>
    <?php
}

/**
 * Render Arabic name input on edit term form for attribute taxonomies.
 *
 * @param WP_Term $term     Current term.
 * @param string  $taxonomy Taxonomy name.
 */
function asattr_render_term_edit_field( $term, $taxonomy ) {
    if ( ! asattr_is_attribute_taxonomy( $taxonomy ) ) {
        return;
    }

    $saved = asattr_get_arabic_attribute_term_names();
    $value = '';
    $hints = asattr_get_arabic_attribute_term_hints();
    $hint  = '';

    if ( isset( $saved[ $taxonomy ][ $term->slug ] ) ) {
        $value = $saved[ $taxonomy ][ $term->slug ];
    }

    if ( isset( $hints[ $taxonomy ][ $term->slug ] ) ) {
        $hint = $hints[ $taxonomy ][ $term->slug ];
    }
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="name_AR"><?php esc_html_e( 'Arabic name', 'asattr' ); ?></label></th>
        <td>
            <input name="name_AR" id="name_AR" type="text" value="<?php echo esc_attr( $value ); ?>" />
            <p class="description"><?php esc_html_e( 'Optional Arabic label used when viewing the store in Arabic.', 'asattr' ); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="hint_AR"><?php esc_html_e( 'Arabic hint content', 'asattr' ); ?></label></th>
        <td>
            <textarea name="hint_AR" id="hint_AR" rows="5"><?php echo esc_textarea( $hint ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Optional Arabic hint used when viewing the store in Arabic.', 'asattr' ); ?></p>
        </td>
    </tr>
    <?php
}

/**
 * Register add/edit term field hooks for all attribute taxonomies.
 */
function asattr_register_term_field_hooks() {
    if ( ! function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
        return;
    }

    foreach ( wc_get_attribute_taxonomy_names() as $taxonomy ) {
        add_action( "{$taxonomy}_add_form_fields", 'asattr_render_term_add_field' );
        add_action( "{$taxonomy}_edit_form_fields", 'asattr_render_term_edit_field', 10, 2 );
    }
}
add_action( 'init', 'asattr_register_term_field_hooks', 20 );

/**
 * Enqueue admin assets for the product edit screen.
 *
 * @param string $hook Current admin page.
 */
function asattr_enqueue_product_admin_assets( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }

    $screen = get_current_screen();

    if ( empty( $screen ) || 'product' !== $screen->post_type ) {
        return;
    }

    $script_handle = 'asattr-product-attributes';

    wp_register_script(
        $script_handle,
        plugin_dir_url( __FILE__ ) . 'assets/js/product-attributes.js',
        array( 'jquery', 'wc-admin-meta-boxes' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        $script_handle,
        'asattrProductAttributes',
        array(
            'arabicPrompt' => __( 'Enter Arabic value (optional):', 'asattr' ),
        )
    );

    wp_enqueue_script( $script_handle );
}
add_action( 'admin_enqueue_scripts', 'asattr_enqueue_product_admin_assets' );

/**
 * Persist the Arabic name when a new attribute is created.
 *
 * @param int   $id   Attribute ID.
 * @param array $data Attribute data.
 */
function asattr_handle_attribute_added( $id, $data ) {
    $arabic_name = isset( $_POST['name_AR'] ) ? sanitize_text_field( wp_unslash( $_POST['name_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $arabic_hint = isset( $_POST['hint_AR'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hint_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( '' === $arabic_name ) {
        $arabic_name = '';
    }

    if ( empty( $data['attribute_name'] ) ) {
        return;
    }

    asattr_save_attribute_name( $data['attribute_name'], $arabic_name );
    asattr_save_attribute_hint( $data['attribute_name'], $arabic_hint );
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
    $arabic_hint = isset( $_POST['hint_AR'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hint_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( empty( $data['attribute_name'] ) ) {
        return;
    }

    $slug = $data['attribute_name'];

    asattr_save_attribute_name( $slug, $arabic_name );
    asattr_save_attribute_hint( $slug, $arabic_hint );

    if ( $old_slug && $old_slug !== $slug ) {
        asattr_remove_attribute_name( $old_slug );
        asattr_remove_attribute_hint( $old_slug );
    }
}
add_action( 'woocommerce_attribute_updated', 'asattr_handle_attribute_updated', 10, 3 );

/**
 * Persist Arabic term name on create/edit actions for attribute taxonomies.
 *
 * @param int    $term_id Term ID.
 * @param int    $tt_id   Term taxonomy ID.
 * @param string $taxonomy Taxonomy name.
 */
function asattr_handle_term_saved( $term_id, $tt_id, $taxonomy ) {
    if ( ! asattr_is_attribute_taxonomy( $taxonomy ) ) {
        return;
    }

    $arabic_name = isset( $_POST['name_AR'] ) ? sanitize_text_field( wp_unslash( $_POST['name_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $arabic_hint = isset( $_POST['hint_AR'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hint_AR'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

    $term = get_term( $term_id, $taxonomy );

    if ( $term instanceof WP_Term ) {
        asattr_save_attribute_term_name( $taxonomy, $term->slug, $arabic_name );
        asattr_save_attribute_term_hint( $taxonomy, $term->slug, $arabic_hint );
    }
}
add_action( 'created_term', 'asattr_handle_term_saved', 10, 3 );
add_action( 'edited_term', 'asattr_handle_term_saved', 10, 3 );

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
    asattr_remove_attribute_hint( $slug );

    $terms = asattr_get_arabic_attribute_term_names();
    $hints = asattr_get_arabic_attribute_term_hints();

    if ( isset( $terms[ $taxonomy ] ) ) {
        unset( $terms[ $taxonomy ] );
        update_option( ASATTR_TERM_OPTION_KEY, $terms );
    }

    if ( isset( $hints[ $taxonomy ] ) ) {
        unset( $hints[ $taxonomy ] );
        update_option( ASATTR_TERM_HINT_OPTION_KEY, $hints );
    }
}
add_action( 'woocommerce_attribute_deleted', 'asattr_handle_attribute_deleted', 10, 3 );

/**
 * Remove stored Arabic term name when an attribute term is deleted.
 *
 * @param int         $term       Term ID.
 * @param int         $tt_id      Term taxonomy ID.
 * @param string      $taxonomy   Taxonomy name.
 * @param WP_Term|int $deleted    Deleted term object or ID depending on WP version.
 * @param array       $object_ids Object IDs affected.
 */
function asattr_handle_term_deleted( $term, $tt_id, $taxonomy, $deleted, $object_ids ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    if ( ! asattr_is_attribute_taxonomy( $taxonomy ) ) {
        return;
    }

    $term_obj = $deleted instanceof WP_Term ? $deleted : get_term( $term, $taxonomy );

    if ( $term_obj instanceof WP_Term ) {
        asattr_remove_attribute_term_name( $taxonomy, $term_obj->slug );
        asattr_remove_attribute_term_hint( $taxonomy, $term_obj->slug );
    }
}
add_action( 'delete_term', 'asattr_handle_term_deleted', 10, 5 );

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
 * Lookup stored Arabic term name for taxonomy/slug pair.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 *
 * @return string
 */
function asattr_get_arabic_term_name( $taxonomy, $slug ) {
    $map = asattr_get_arabic_attribute_term_names();

    if ( isset( $map[ $taxonomy ][ $slug ] ) ) {
        return $map[ $taxonomy ][ $slug ];
    }

    return '';
}

/**
 * Lookup stored Arabic term hint for taxonomy/slug pair.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $slug     Term slug.
 *
 * @return string
 */
function asattr_get_arabic_term_hint( $taxonomy, $slug ) {
    $map = asattr_get_arabic_attribute_term_hints();

    if ( isset( $map[ $taxonomy ][ $slug ] ) ) {
        return $map[ $taxonomy ][ $slug ];
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
 * Override Woodmart attribute hint options with Arabic values when available.
 */
function asattr_register_attribute_hint_filters() {
    if ( is_admin() ) {
        return;
    }

    if ( ! asattr_is_arabic_language() ) {
        return;
    }

    foreach ( asattr_get_arabic_attribute_hints() as $slug => $hint ) {
        if ( '' === $hint ) {
            continue;
        }

        $option = 'woodmart_pa_' . $slug . '_hint';

        add_filter(
            "option_{$option}",
            static function( $value ) use ( $hint ) {
                return '' !== $hint ? $hint : $value;
            },
            10,
            1
        );
    }
}
add_action( 'init', 'asattr_register_attribute_hint_filters', 25 );

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

/**
 * Replace the name property on a term object with its Arabic equivalent if available.
 *
 * @param WP_Term $term Term object.
 *
 * @return WP_Term
 */
function asattr_maybe_apply_term_translation( $term ) {
    if ( ! ( $term instanceof WP_Term ) ) {
        return $term;
    }

    if ( ! asattr_is_attribute_taxonomy( $term->taxonomy ) ) {
        return $term;
    }

    if ( ! asattr_is_arabic_language() ) {
        return $term;
    }

    $arabic = asattr_get_arabic_term_name( $term->taxonomy, $term->slug );

    if ( '' !== $arabic ) {
        $term->name = $arabic;
    }

    return $term;
}

/**
 * Filter single term retrieval for attribute taxonomies.
 *
 * @param WP_Term|WP_Error $term     Term object or error.
 * @param string           $taxonomy Taxonomy name.
 *
 * @return WP_Term|WP_Error
 */
function asattr_filter_get_term( $term, $taxonomy ) {
    if ( is_wp_error( $term ) ) {
        return $term;
    }

    return asattr_maybe_apply_term_translation( $term );
}
add_filter( 'get_term', 'asattr_filter_get_term', 10, 2 );

/**
 * Swap Woodmart term hint meta with Arabic translation when available.
 *
 * @param mixed  $value    Meta value.
 * @param int    $term_id  Term ID.
 * @param string $meta_key Meta key.
 * @param bool   $single   Whether a single value is requested.
 * @param string $meta_type Meta type.
 *
 * @return mixed
 */
function asattr_filter_term_hint_meta( $value, $term_id, $meta_key, $single, $meta_type ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    if ( 'pa_term_hint' !== $meta_key ) {
        return $value;
    }

    if ( ! asattr_is_arabic_language() ) {
        return $value;
    }

    $term = get_term( $term_id );

    if ( ! ( $term instanceof WP_Term ) || ! asattr_is_attribute_taxonomy( $term->taxonomy ) ) {
        return $value;
    }

    $arabic_hint = asattr_get_arabic_term_hint( $term->taxonomy, $term->slug );

    if ( '' === $arabic_hint ) {
        return $value;
    }

    return $single ? $arabic_hint : array( $arabic_hint );
}
add_filter( 'get_term_metadata', 'asattr_filter_term_hint_meta', 10, 5 );

/**
 * Filter multiple term retrieval for attribute taxonomies.
 *
 * @param array       $terms      Array of terms or WP_Error.
 * @param string[]    $taxonomies Requested taxonomies.
 *
 * @return array|WP_Error
 */
function asattr_filter_get_terms( $terms, $taxonomies ) {
    if ( is_wp_error( $terms ) ) {
        return $terms;
    }

    if ( empty( $terms ) || ! is_array( $terms ) ) {
        return $terms;
    }

    foreach ( $terms as $index => $term ) {
        $terms[ $index ] = asattr_maybe_apply_term_translation( $term );
    }

    return $terms;
}
add_filter( 'get_terms', 'asattr_filter_get_terms', 10, 2 );

/**
 * Swap variation option labels to Arabic when available.
 *
 * @param string          $term_name Display term name.
 * @param WP_Term         $term      Term object.
 * @param string          $attribute Attribute name.
 * @param WC_Product|null $product   Product object.
 *
 * @return string
 */
function asattr_filter_variation_option_name( $term_name, $term, $attribute, $product ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    if ( ! ( $term instanceof WP_Term ) ) {
        return $term_name;
    }

    $translated = asattr_maybe_apply_term_translation( $term );

    return $translated instanceof WP_Term ? $translated->name : $term_name;
}
add_filter( 'woocommerce_variation_option_name', 'asattr_filter_variation_option_name', 10, 4 );
