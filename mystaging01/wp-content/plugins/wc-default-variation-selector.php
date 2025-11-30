<?php
/**
 * Plugin Name: WC Default Variation Selector
 * Description: Choose a default variation for WooCommerce variable products to display consistently across the site.
 * Version: 1.0.0
 * Author: OpenAI
 * License: GPLv2 or later
 * Text Domain: wc-default-variation-selector
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    class WC_Default_Variation_Selector {

        const META_KEY = '_wc_default_display_variation_id';

        public function __construct() {
            if ( is_admin() ) {
                add_action( 'woocommerce_after_variable_attributes_table', [ $this, 'render_selector_field' ], 10, 2 );
                add_action( 'woocommerce_process_product_meta_variable', [ $this, 'save_selector_field' ], 10, 2 );
            }

            add_filter( 'woocommerce_product_get_default_attributes', [ $this, 'filter_default_attributes' ], 10, 2 );
            add_filter( 'woocommerce_get_price_html', [ $this, 'filter_price_html' ], 20, 2 );
            add_filter( 'woocommerce_variable_price_html', [ $this, 'filter_price_html' ], 20, 2 );
            add_filter( 'woocommerce_variable_sale_price_html', [ $this, 'filter_price_html' ], 20, 2 );
            add_filter( 'woocommerce_product_get_image_id', [ $this, 'filter_image_id' ], 10, 2 );
            add_filter( 'woocommerce_product_get_price', [ $this, 'filter_product_price' ], 10, 2 );
            add_filter( 'woocommerce_product_get_regular_price', [ $this, 'filter_product_regular_price' ], 10, 2 );
            add_filter( 'woocommerce_product_get_sale_price', [ $this, 'filter_product_sale_price' ], 10, 2 );
            add_filter( 'woocommerce_available_variation', [ $this, 'filter_available_variation' ], 10, 3 );
        }

        public function render_selector_field( $loop, $variation_data ) {
            global $post;

            $product = wc_get_product( $post ? $post->ID : 0 );
            if ( ! $product || ! $product->is_type( 'variable' ) ) {
                return;
            }

            $variations = $product->get_children();
            if ( empty( $variations ) ) {
                return;
            }

            $current_value = get_post_meta( $product->get_id(), self::META_KEY, true );
            ?>
            <div class="options_group" style="margin-top:20px;">
                <p class="form-field">
                    <label for="wc_default_display_variation_id"><strong><?php esc_html_e( 'Default front-end variation (shown everywhere)', 'wc-default-variation-selector' ); ?></strong></label>
                    <select name="wc_default_display_variation_id" id="wc_default_display_variation_id" class="wc-enhanced-select" data-placeholder="<?php esc_attr_e( 'Select a variation', 'wc-default-variation-selector' ); ?>" style="min-width: 50%;">
                        <option value=""><?php esc_html_e( '— No default variation —', 'wc-default-variation-selector' ); ?></option>
                        <?php
                        foreach ( $variations as $variation_id ) {
                            $variation = wc_get_product( $variation_id );
                            if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
                                continue;
                            }

                            $label = $this->get_variation_label( $variation, $product );
                            printf(
                                '<option value="%1$d" %2$s>%3$s</option>',
                                absint( $variation_id ),
                                selected( (string) $current_value, (string) $variation_id, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                </p>
            </div>
            <?php
        }

        protected function get_variation_label( WC_Product_Variation $variation, WC_Product_Variable $product ) {
            $attributes = $variation->get_attributes();
            $parts      = [];

            foreach ( $attributes as $key => $value ) {
                if ( '' === $value ) {
                    continue;
                }

                $attribute_name = str_replace( 'attribute_', '', $key );
                $label          = wc_attribute_label( $attribute_name, $product );
                $term_name      = $this->get_attribute_value_label( $attribute_name, $value );

                if ( $term_name ) {
                    $parts[] = sprintf( '%s: %s', $label, $term_name );
                }
            }

            $description = $parts ? implode( ', ', $parts ) : __( 'No attributes set', 'wc-default-variation-selector' );

            return sprintf( __( 'Variation #%1$s – %2$s', 'wc-default-variation-selector' ), $variation->get_id(), $description );
        }

        protected function get_attribute_value_label( $attribute_name, $value ) {
            if ( taxonomy_exists( $attribute_name ) ) {
                $term = get_term_by( 'slug', $value, $attribute_name );
                if ( $term && ! is_wp_error( $term ) ) {
                    return $term->name;
                }
            }

            return wc_clean( $value );
        }

        public function save_selector_field( $product_id, $post ) {
            $raw_variation_id = isset( $_POST['wc_default_display_variation_id'] ) ? wp_unslash( $_POST['wc_default_display_variation_id'] ) : '';
            $variation_id     = absint( $raw_variation_id );

            if ( $variation_id && $this->variation_belongs_to_product( $variation_id, $product_id ) ) {
                update_post_meta( $product_id, self::META_KEY, $variation_id );
            } else {
                delete_post_meta( $product_id, self::META_KEY );
            }
        }

        public function filter_default_attributes( $default_attributes, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $default_attributes;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $default_attributes;
            }

            $attributes = [];
            foreach ( $variation->get_attributes() as $key => $value ) {
                if ( '' === $value ) {
                    continue;
                }

                $attribute_name = str_replace( 'attribute_', '', $key );
                $attributes[ $attribute_name ] = $value;
            }

            return $attributes ? $attributes : $default_attributes;
        }

        public function filter_price_html( $price_html, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $price_html;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $price_html;
            }

            return $variation->get_price_html();
        }

        public function filter_product_price( $price, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $price;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $price;
            }

            return $variation->get_price( 'edit' );
        }

        public function filter_product_regular_price( $price, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $price;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $price;
            }

            return $variation->get_regular_price( 'edit' );
        }

        public function filter_product_sale_price( $price, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $price;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $price;
            }

            return $variation->get_sale_price( 'edit' );
        }

        public function filter_available_variation( $data, $product, $variation ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $data;
            }

            $selected_variation = $this->get_selected_variation( $product );
            if ( ! $selected_variation ) {
                return $data;
            }

            $is_selected = (int) $selected_variation->get_id() === (int) $variation->get_id();

            if ( $is_selected ) {
                $data['price_html'] = '';
            }

            return $data;
        }

        public function filter_image_id( $image_id, $product ) {
            if ( ! $product instanceof WC_Product_Variable ) {
                return $image_id;
            }

            $variation = $this->get_selected_variation( $product );
            if ( ! $variation ) {
                return $image_id;
            }

            $variation_image_id = $variation->get_image_id();

            return $variation_image_id ? $variation_image_id : $image_id;
        }

        protected function get_selected_variation( WC_Product_Variable $product ) {
            $variation_id = absint( get_post_meta( $product->get_id(), self::META_KEY, true ) );
            if ( ! $variation_id ) {
                return null;
            }

            $variation = wc_get_product( $variation_id );
            if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
                return null;
            }

            if ( $variation->get_parent_id() !== $product->get_id() ) {
                return null;
            }

            if ( 'publish' !== $variation->get_status() ) {
                return null;
            }

            return $variation;
        }

        protected function variation_belongs_to_product( $variation_id, $product_id ) {
            $variation = wc_get_product( $variation_id );

            return $variation && $variation->is_type( 'variation' ) && $variation->get_parent_id() === absint( $product_id );
        }
    }

    new WC_Default_Variation_Selector();
} );
