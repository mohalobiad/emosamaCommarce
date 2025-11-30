<?php
/**
 * Plugin Name: WC Custom Variation Price Display
 * Description: Allow choosing which variation price is shown for WooCommerce variable products.
 * Version: 1.0.0
 * Author: You
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( class_exists( 'WooCommerce' ) ) {
  class WC_Custom_Variation_Price_Display {
    const META_KEY = '_wc_price_display_variation_id';

    public static function init() : void {
      add_action( 'woocommerce_variable_product_before_variations', array( __CLASS__, 'render_default_variation_field' ) );
      add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_default_variation_field' ) );

      add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'override_price_html' ), 10, 2 );
      add_filter( 'woocommerce_variable_price_html', array( __CLASS__, 'override_price_html' ), 10, 2 );
      add_filter( 'woocommerce_variable_sale_price_html', array( __CLASS__, 'override_price_html' ), 10, 2 );
    }

    public static function render_default_variation_field() : void {
      global $product_object;

      if ( ! $product_object || ! $product_object->is_type( 'variable' ) ) {
        return;
      }

      $variation_ids = $product_object->get_children();

      if ( empty( $variation_ids ) ) {
        return;
      }

      $current_value = $product_object->get_meta( self::META_KEY, true );
      $options       = array();

      foreach ( $variation_ids as $variation_id ) {
        $variation = wc_get_product( $variation_id );

        if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
          continue;
        }

        $options[ $variation_id ] = self::get_variation_label( $variation, $product_object );
      }

      if ( empty( $options ) ) {
        return;
      }
      ?>
      <div class="options_group">
        <p class="form-field">
          <label for="wc_price_display_variation_id"><?php esc_html_e( 'Default price variation (shown everywhere)', 'wc-custom-variation-price-display' ); ?></label>
          <select name="wc_price_display_variation_id" id="wc_price_display_variation_id" class="wc-enhanced-select" style="width: 50%;">
            <option value=""><?php esc_html_e( 'Select a variation', 'wc-custom-variation-price-display' ); ?></option>
            <?php foreach ( $options as $id => $label ) : ?>
              <option value="<?php echo esc_attr( $id ); ?>" <?php selected( (int) $current_value, (int) $id ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
          </select>
        </p>
      </div>
      <script type="text/javascript">
        jQuery( function( $ ) {
          var fieldGroup = $( '#wc_price_display_variation_id' ).closest( '.options_group' );
          var variationsWrapper = $( '#variable_product_options_inner .woocommerce_variations' );
          if ( fieldGroup.length && variationsWrapper.length ) {
            fieldGroup.insertAfter( variationsWrapper.closest( '.woocommerce_variations_wrapper' ) );
          }
        } );
      </script>
      <?php
    }

    public static function save_default_variation_field( WC_Product $product ) : void {
      if ( ! $product->is_type( 'variable' ) ) {
        return;
      }

      $variation_id = isset( $_POST['wc_price_display_variation_id'] ) ? absint( wp_unslash( $_POST['wc_price_display_variation_id'] ) ) : 0;

      if ( ! $variation_id ) {
        $product->delete_meta_data( self::META_KEY );
        return;
      }

      $variation = wc_get_product( $variation_id );

      if ( $variation && $variation->is_type( 'variation' ) && (int) $variation->get_parent_id() === (int) $product->get_id() ) {
        $product->update_meta_data( self::META_KEY, $variation_id );
      } else {
        $product->delete_meta_data( self::META_KEY );
      }
    }

    public static function override_price_html( string $price_html, WC_Product $product ) : string {
      if ( ! $product->is_type( 'variable' ) ) {
        return $price_html;
      }

      $variation_id = (int) $product->get_meta( self::META_KEY );

      if ( ! $variation_id ) {
        return $price_html;
      }

      $variation = wc_get_product( $variation_id );

      if ( $variation && $variation->is_type( 'variation' ) && (int) $variation->get_parent_id() === (int) $product->get_id() && 'publish' === $variation->get_status() ) {
        return $variation->get_price_html();
      }

      return $price_html;
    }

    private static function get_variation_label( WC_Product_Variation $variation, WC_Product $product ) : string {
      $attributes = $variation->get_attributes();
      $parts      = array();

      foreach ( $attributes as $key => $value ) {
        $taxonomy = str_replace( 'attribute_', '', $key );
        $label    = wc_attribute_label( $taxonomy, $product );
        $term     = $value;

        if ( $value && taxonomy_exists( $taxonomy ) ) {
          $term_obj = get_term_by( 'slug', $value, $taxonomy );
          $term     = $term_obj ? $term_obj->name : $value;
        }

        if ( '' === $term ) {
          $term = __( 'Any', 'wc-custom-variation-price-display' );
        }

        $parts[] = sprintf( '%s: %s', $label, $term );
      }

      $details = empty( $parts ) ? '' : ' – ' . implode( ', ', $parts );

      return sprintf( /* translators: %d variation ID. */ __( 'Variation #%d', 'wc-custom-variation-price-display' ), $variation->get_id() ) . $details;
    }
  }

  WC_Custom_Variation_Price_Display::init();
}
