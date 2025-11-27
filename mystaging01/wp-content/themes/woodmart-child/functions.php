<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

/**
 * Remove the shipping method label text so only the price displays.
 *
 * @param string                 $label  The full shipping method label.
 * @param WC_Shipping_Rate       $method The shipping method object.
 * @return string
 */
function woodmart_child_trim_shipping_method_label( $label, $method ) {
        if ( false !== strpos( $label, ':' ) ) {
                $parts = explode( ':', $label, 2 );
                $label = trim( $parts[1] );
        }

        return $label;
}
add_filter( 'woocommerce_cart_shipping_method_full_label', 'woodmart_child_trim_shipping_method_label', 10, 2 );

/**
 * Update the shipping package title used in totals tables.
 *
 * @param string $package_name The current package name.
 * @return string
 */
function woodmart_child_update_shipping_package_name( $package_name ) {
        return __( 'Shipping price', 'woodmart-child' );
}
add_filter( 'woocommerce_shipping_package_name', 'woodmart_child_update_shipping_package_name' );

/**
 * Retrieve the formatted United Arab Emirates country option.
 *
 * @return array
 */
function woodmart_child_get_uae_country_option() {
        $label = __( 'United Arab Emirates', 'woocommerce' );

        if ( function_exists( 'WC' ) && WC()->countries ) {
                $countries = WC()->countries->get_countries();
                if ( isset( $countries['AE'] ) ) {
                        $label = $countries['AE'];
                }
        }

        return array( 'AE' => $label );
}

/**
 * Retrieve the available United Arab Emirates city options.
 *
 * @return array
 */
function woodmart_child_get_uae_city_options() {
        if ( function_exists( 'aucm_get_checkout_city_options' ) ) {
                $options = aucm_get_checkout_city_options();
                if ( ! empty( $options ) ) {
                        return apply_filters( 'woodmart_child_uae_city_options', $options );
                }
        }

        $cities = array(
                'Abu Dhabi',
                'Al Ain',
                'Madinat Zayed',
                'Ghayathi',
                'Al Ruwais',
                'Al Mirfa',
                'Sila',
                'Dalma',
                'Dubai',
                'Hatta',
                'Sharjah',
                'Al Dhaid',
                'Khor Fakkan',
                'Kalba',
                'Dibba Al-Hisn',
                'Ajman',
                'Masfout',
                'Manama',
                'Umm Al Quwain',
                'Falaj Al Mualla',
                'Ras Al Khaimah',
                'Rams',
                'Al Jazirah Al Hamra',
                'Fujairah',
                'Dibba Al-Fujairah',
                'Liwa',
        );

        $options = array();

        foreach ( $cities as $city ) {
                $options[ $city ] = __( $city, 'woodmart-child' );
        }

        return apply_filters( 'woodmart_child_uae_city_options', $options );
}

/**
 * Update the base address field settings so the country label reads "Country".
 *
 * @param array $fields Default address fields.
 * @return array
 */
function woodmart_child_override_default_address_fields( $fields ) {
        if ( isset( $fields['country'] ) ) {
                $fields['country']['label']       = __( 'Country', 'woodmart-child' );
                $fields['country']['placeholder'] = __( 'United Arab Emirates', 'woodmart-child' );
        }

        if ( isset( $fields['state'] ) ) {
                $fields['state']['required'] = false;
        }

        return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'woodmart_child_override_default_address_fields', PHP_INT_MAX );

/**
 * Lock an individual checkout country field to United Arab Emirates.
 *
 * @param array $field Single field configuration.
 * @return array
 */
function woodmart_child_prepare_locked_country_field( $field ) {
        $uae_option  = woodmart_child_get_uae_country_option();
        $uae_country = array_key_first( $uae_option );

        $field['type']     = 'hidden';
        $field['label']    = '';
        $field['required'] = true;
        $field['priority'] = isset( $field['priority'] ) ? $field['priority'] : 30;
        $field['default']  = $uae_country;
        $field['value']    = $uae_country;
        $field['options']  = $uae_option;

        $field['custom_attributes'] = isset( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ? $field['custom_attributes'] : array();
        $field['custom_attributes']['data-woodmart-child-locked-country'] = '1';

        $field['class']       = array_unique( array_merge( (array) ( $field['class'] ?? array() ), array( 'woodmart-child-fixed-country-hidden' ) ) );
        $field['input_class'] = array_unique( array_merge( (array) ( $field['input_class'] ?? array() ), array( 'woodmart-child-fixed-country-hidden__input' ) ) );

        return $field;
}

/**
 * Retrieve the configuration for the display-only country field.
 *
 * @param string $context Either billing or shipping.
 * @return array
 */
function woodmart_child_get_country_display_field( $context = 'billing' ) {
        $uae_option  = woodmart_child_get_uae_country_option();
        $uae_country = array_key_first( $uae_option );
        $uae_label   = $uae_option[ $uae_country ] ?? __( 'United Arab Emirates', 'woocommerce' );

        $classes = array( 'form-row-wide', 'woodmart-child-fixed-country-display', 'woodmart-child-fixed-country' );
        if ( 'shipping' === $context ) {
                $classes[] = 'woodmart-child-fixed-country-display--shipping';
        } else {
                $classes[] = 'woodmart-child-fixed-country-display--billing';
        }

        return array(
                'type'            => 'text',
                'label'           => __( 'Country', 'woodmart-child' ),
                'required'        => true,
                'priority'        => 31,
                'default'         => $uae_label,
                'value'           => $uae_label,
                'custom_attributes' => array(
                        'readonly'           => 'readonly',
                        'aria-readonly'      => 'true',
                        'tabindex'           => '-1',
                        'data-locked-country'=> $uae_country,
                ),
                'class'           => $classes,
                'input_class'     => array( 'woodmart-child-fixed-country-display__input' ),
        );
}

/**
 * Prepare a checkout city field to use a fixed list of UAE cities.
 *
 * @param array $field Field configuration.
 * @return array
 */
function woodmart_child_prepare_city_field( $field ) {
        $city_options = woodmart_child_get_uae_city_options();
        $default_city = array_key_first( $city_options );

        $field['type']        = 'select';
        $field['label']       = __( 'City', 'woodmart-child' );
        $field['options']     = $city_options;
        $field['required']    = true;
        $field['priority']    = 35;
        $field['placeholder'] = __( 'Select a city', 'woodmart-child' );

        if ( empty( $field['default'] ) || ! isset( $city_options[ $field['default'] ] ) ) {
                $field['default'] = $default_city;
        }

        $field['custom_attributes']                     = isset( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ? $field['custom_attributes'] : array();
        $field['custom_attributes']['data-placeholder'] = __( 'Select a city', 'woodmart-child' );

        $field['class']       = array_unique( array_merge( (array) ( $field['class'] ?? array() ), array( 'woodmart-child-city-select' ) ) );
        $field['input_class'] = array_unique( array_merge( (array) ( $field['input_class'] ?? array() ), array( 'woodmart-child-city-select__input' ) ) );

        return $field;
}

/**
 * Retrieve the helper text displayed beneath the billing phone field.
 *
 * @return string
 */
function woodmart_child_get_phone_help_text() {
        return __( 'The number must start with 5 followed by 8 digits (total 9 digits). Example: 5XXXXXXXX', 'woodmart-child' );
}

/**
 * Prepare the billing phone field to use a single validated tel input.
 *
 * @param array $field Field configuration.
 * @return array
 */
function woodmart_child_prepare_phone_field( $field ) {
        $field['type']        = 'tel';
        $field['label']       = __( 'Phone', 'woocommerce' );
        $field['required']    = true;
        $field['priority']    = 90;
        $field['placeholder'] = __( '5XXXXXXXX', 'woodmart-child' );
        $field['description'] = woodmart_child_get_phone_help_text();

        $field['custom_attributes'] = isset( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ? $field['custom_attributes'] : array();
        $field['custom_attributes']['inputmode'] = 'numeric';
        $field['custom_attributes']['pattern']   = '5[0-9]{8}';
        $field['custom_attributes']['maxlength'] = '9';
        $field['custom_attributes']['minlength'] = '9';

        $field['class']       = array_unique( array_merge( (array) ( $field['class'] ?? array() ), array( 'woodmart-child-phone-field' ) ) );
        $field['input_class'] = array_unique( array_merge( (array) ( $field['input_class'] ?? array() ), array( 'woodmart-child-phone-input' ) ) );

        return $field;
}

/**
 * Force the billing country field configuration.
 *
 * @param array $fields Billing fields configuration.
 * @return array
 */
function woodmart_child_lock_billing_country_field( $fields ) {
        if ( isset( $fields['billing_country'] ) ) {
                $fields['billing_country'] = woodmart_child_prepare_locked_country_field( $fields['billing_country'] );
        }

        if ( ! isset( $fields['billing_country_display'] ) ) {
                $fields['billing_country_display'] = woodmart_child_get_country_display_field( 'billing' );
        }

        if ( isset( $fields['billing_city'] ) ) {
                $fields['billing_city'] = woodmart_child_prepare_city_field( $fields['billing_city'] );
        }

        if ( isset( $fields['billing_phone'] ) ) {
                $fields['billing_phone'] = woodmart_child_prepare_phone_field( $fields['billing_phone'] );
        }

        if ( isset( $fields['billing_state'] ) ) {
                unset( $fields['billing_state'] );
        }

        return $fields;
}
add_filter( 'woocommerce_billing_fields', 'woodmart_child_lock_billing_country_field', PHP_INT_MAX );

/**
 * Force the shipping country field configuration.
 *
 * @param array $fields Shipping fields configuration.
 * @return array
 */
function woodmart_child_lock_shipping_country_field( $fields ) {
        if ( isset( $fields['shipping_country'] ) ) {
                $fields['shipping_country'] = woodmart_child_prepare_locked_country_field( $fields['shipping_country'] );
        }

        if ( ! isset( $fields['shipping_country_display'] ) ) {
                $fields['shipping_country_display'] = woodmart_child_get_country_display_field( 'shipping' );
        }

        if ( isset( $fields['shipping_city'] ) ) {
                $fields['shipping_city'] = woodmart_child_prepare_city_field( $fields['shipping_city'] );
        }

        if ( isset( $fields['shipping_state'] ) ) {
                unset( $fields['shipping_state'] );
        }

        return $fields;
}
add_filter( 'woocommerce_shipping_fields', 'woodmart_child_lock_shipping_country_field', PHP_INT_MAX );

/**
 * Apply the locked country configuration to the combined checkout fields.
 *
 * @param array $fields Checkout fields grouped by section.
 * @return array
 */
function woodmart_child_lock_checkout_country_fields( $fields ) {
        if ( isset( $fields['billing']['billing_country'] ) ) {
                $fields['billing']['billing_country'] = woodmart_child_prepare_locked_country_field( $fields['billing']['billing_country'] );
        }

        $fields['billing']['billing_country_display'] = woodmart_child_get_country_display_field( 'billing' );

        if ( isset( $fields['billing']['billing_city'] ) ) {
                $fields['billing']['billing_city'] = woodmart_child_prepare_city_field( $fields['billing']['billing_city'] );
        }

        if ( isset( $fields['billing']['billing_phone'] ) ) {
                $fields['billing']['billing_phone'] = woodmart_child_prepare_phone_field( $fields['billing']['billing_phone'] );
        }

        if ( isset( $fields['shipping']['shipping_country'] ) ) {
                $fields['shipping']['shipping_country'] = woodmart_child_prepare_locked_country_field( $fields['shipping']['shipping_country'] );
        }

        $fields['shipping']['shipping_country_display'] = woodmart_child_get_country_display_field( 'shipping' );

        if ( isset( $fields['shipping']['shipping_city'] ) ) {
                $fields['shipping']['shipping_city'] = woodmart_child_prepare_city_field( $fields['shipping']['shipping_city'] );
        }

        if ( isset( $fields['shipping']['shipping_state'] ) ) {
                unset( $fields['shipping']['shipping_state'] );
        }

        if ( isset( $fields['billing']['billing_state'] ) ) {
                unset( $fields['billing']['billing_state'] );
        }

        return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'woodmart_child_lock_checkout_country_fields', PHP_INT_MAX );

/**
 * Keep the WooCommerce customer session countries locked to AE.
 */
function woodmart_child_lock_customer_session_country() {
        if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
                return;
        }

        $customer = WC()->customer;

        if ( 'AE' !== $customer->get_billing_country() ) {
                $customer->set_billing_country( 'AE' );
        }

        if ( 'AE' !== $customer->get_shipping_country() ) {
                $customer->set_shipping_country( 'AE' );
        }

        if ( $customer->get_billing_state() ) {
                $customer->set_billing_state( '' );
        }

        if ( $customer->get_shipping_state() ) {
                $customer->set_shipping_state( '' );
        }
}
add_action( 'init', 'woodmart_child_lock_customer_session_country', 50 );

/**
 * Restrict the shop to the United Arab Emirates for billing purposes.
 *
 * @param array $countries Allowed billing countries.
 * @return array
 */
function woodmart_child_restrict_allowed_billing_countries( $countries ) {
        return woodmart_child_get_uae_country_option();
}
add_filter( 'woocommerce_countries_allowed_countries', 'woodmart_child_restrict_allowed_billing_countries', PHP_INT_MAX );

/**
 * Restrict the shop to the United Arab Emirates for shipping purposes.
 *
 * @param array $countries Allowed shipping countries.
 * @return array
 */
function woodmart_child_restrict_allowed_shipping_countries( $countries ) {
        return woodmart_child_get_uae_country_option();
}
add_filter( 'woocommerce_countries_shipping_countries', 'woodmart_child_restrict_allowed_shipping_countries', PHP_INT_MAX );

/**
 * Ensure default checkout countries remain locked to the United Arab Emirates.
 *
 * @return string
 */
function woodmart_child_default_checkout_country() {
        return 'AE';
}
add_filter( 'default_checkout_billing_country', 'woodmart_child_default_checkout_country' );
add_filter( 'default_checkout_shipping_country', 'woodmart_child_default_checkout_country' );

/**
 * Guarantee checkout submissions always persist AE for billing and shipping countries.
 *
 * @param array $data Posted checkout data.
 * @return array
 */
function woodmart_child_force_checkout_country_values( $data ) {
        $data['billing_country']  = 'AE';
        $data['shipping_country'] = 'AE';

        $city_options = woodmart_child_get_uae_city_options();
        $default_city = array_key_first( $city_options );

        if ( isset( $data['billing_city'] ) && ! isset( $city_options[ $data['billing_city'] ] ) ) {
                $data['billing_city'] = $default_city;
        }

        if ( isset( $data['shipping_city'] ) && ! isset( $city_options[ $data['shipping_city'] ] ) ) {
                $data['shipping_city'] = $default_city;
        }

        if ( isset( $data['billing_state'] ) ) {
                unset( $data['billing_state'] );
        }

        if ( isset( $data['shipping_state'] ) ) {
                unset( $data['shipping_state'] );
        }

        if ( isset( $data['billing_phone'] ) ) {
                $digits = preg_replace( '/\D+/', '', $data['billing_phone'] );

                if ( preg_match( '/^5\d{8}$/', $digits ) ) {
                        $data['billing_phone'] = $digits;
                }
        }

        if ( isset( $data['billing_country_display'] ) ) {
                unset( $data['billing_country_display'] );
        }

        if ( isset( $data['shipping_country_display'] ) ) {
                unset( $data['shipping_country_display'] );
        }

        return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'woodmart_child_force_checkout_country_values', PHP_INT_MAX );

/**
 * Force the checkout field values used for rendering to always be AE.
 *
 * @param mixed  $value Field value.
 * @param string $input Field key.
 * @return mixed
 */
function woodmart_child_force_checkout_country_field_value( $value, $input ) {
        if ( in_array( $input, array( 'billing_country', 'shipping_country' ), true ) ) {
                return 'AE';
        }

        if ( in_array( $input, array( 'billing_country_display', 'shipping_country_display' ), true ) ) {
                $uae_option  = woodmart_child_get_uae_country_option();
                $uae_country = array_key_first( $uae_option );

                return $uae_option[ $uae_country ] ?? __( 'United Arab Emirates', 'woocommerce' );
        }

        return $value;
}
add_filter( 'woocommerce_checkout_get_value', 'woodmart_child_force_checkout_country_field_value', PHP_INT_MAX, 2 );

/**
 * Add a custom helper class to the billing phone field description.
 *
 * @param string $field Markup for the field.
 * @param string $key   Field key.
 * @param array  $args  Field arguments.
 * @param mixed  $value Field value.
 * @return string
 */
function woodmart_child_customize_billing_phone_field_markup( $field, $key, $args, $value ) {
        unset( $args, $value );

        if ( 'billing_phone' !== $key ) {
                return $field;
        }

        if ( false !== strpos( $field, 'class="description"' ) ) {
                $field = str_replace( 'class="description"', 'class="description woodmart-child-phone-help"', $field );
        }

        return $field;
}
add_filter( 'woocommerce_form_field', 'woodmart_child_customize_billing_phone_field_markup', PHP_INT_MAX, 4 );

/**
 * Validate the billing phone number during checkout processing.
 */
function woodmart_child_validate_billing_phone_field() {
        $phone_raw = isset( $_POST['billing_phone'] ) ? wc_clean( wp_unslash( $_POST['billing_phone'] ) ) : '';
        $digits    = preg_replace( '/\D+/', '', $phone_raw );

        if ( ! preg_match( '/^5\d{8}$/', $digits ) ) {
                wc_add_notice( __( 'Please enter a valid phone number. It must start with 5 and be followed by 8 digits.', 'woodmart-child' ), 'error' );
                return;
        }

        $_POST['billing_phone'] = $digits; // phpcs:ignore WordPress.Security.NonceVerification.Missing
}
add_action( 'woocommerce_checkout_process', 'woodmart_child_validate_billing_phone_field', 20 );

/**
 * Adjust the shipping row displayed on order totals tables.
 *
 * @param array    $total_rows Order total rows.
 * @param WC_Order $order      The order object.
 * @param bool     $tax_display Whether taxes are displayed.
 * @return array
 */
function woodmart_child_customize_order_received_shipping_row( $total_rows, $order, $tax_display ) {
        unset( $order, $tax_display );

        if ( ! isset( $total_rows['shipping'] ) ) {
                return $total_rows;
        }

        $total_rows['shipping']['label'] = __( 'Shipping price:', 'woodmart-child' );

        $shipping_value = $total_rows['shipping']['value'];
        $shipping_value = preg_replace( '#<small[^>]*class=[\'\"][^\'\"]*shipped_via[^\'\"]*[\'\"][^>]*>.*?</small>#i', '', $shipping_value );
        $shipping_value = preg_replace( '#(?:\s|&nbsp;)+$#', '', $shipping_value );

        $total_rows['shipping']['value'] = $shipping_value;

        return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'woodmart_child_customize_order_received_shipping_row', 20, 3 );

add_action( 'woocommerce_single_product_summary', 'woodmart_child_display_short_description', 19 );

/**
 * Determine whether a custom single product layout built with the WoodMart builder is active.
 *
 * @return bool
 */
function woodmart_child_has_custom_single_product_layout() {
        if ( ! class_exists( '\XTS\Modules\Layouts\Main' ) ) {
                return false;
        }

        $main = \XTS\Modules\Layouts\Main::get_instance();

        return method_exists( $main, 'has_custom_layout' ) && $main->has_custom_layout( 'single_product' );
}

/**
 * Retrieve the rendered short description markup for the current product.
 *
 * @return string
 */
function woodmart_child_get_short_description_markup() {
        global $product;

        if ( ! $product instanceof WC_Product ) {
                $product = wc_get_product( get_the_ID() );
        }

        if ( ! $product instanceof WC_Product ) {
                return '';
        }

        $short_description = apply_filters( 'woocommerce_short_description', $product->get_short_description() );

        if ( '' === trim( wp_strip_all_tags( $short_description ) ) ) {
                return '';
        }

        return '<div class="product-short-description">' . wp_kses_post( $short_description ) . '</div>';
}

/**
 * Display the product short description on single product pages.
 */
function woodmart_child_display_short_description() {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
                return;
        }

        if ( woodmart_child_has_custom_single_product_layout() ) {
                return;
        }

        $markup = woodmart_child_get_short_description_markup();

        if ( '' === $markup ) {
                return;
        }

        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Display the short description immediately after the Elementor product title widget.
 *
 * @param Elementor\Widget_Base $widget The Elementor widget instance.
 */
function woodmart_child_display_short_description_after_title_widget( $widget ) {
        if ( ! class_exists( '\Elementor\Widget_Base' ) || ! $widget instanceof \Elementor\Widget_Base ) {
                return;
        }

        if ( 'wd_single_product_title' !== $widget->get_name() ) {
                return;
        }

        $markup = woodmart_child_get_short_description_markup();

        if ( '' === $markup ) {
                return;
        }

        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'elementor/frontend/widget/after_render', 'woodmart_child_display_short_description_after_title_widget', 10, 1 );
/**
 * Remove company field from all WooCommerce addresses (billing + shipping + invoices).
 *
 * This completely removes the "Company" field so it doesn't appear
 * on checkout, My Account addresses, emails, or invoices.
 *
 * @param array $fields Default address fields.
 * @return array
 */
function woodmart_child_remove_company_address_field( $fields ) {
    if ( isset( $fields['company'] ) ) {
        unset( $fields['company'] );
    }

    return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'woodmart_child_remove_company_address_field', PHP_INT_MAX );


/**
 * Ensure every My Account lost-password URL opens the Woodmart reset form.
 *
 * Woodmart expects the query string to contain both `show-reset-form=true`
 * and a bare `action` parameter in order to render the second step of the
 * password reset flow. WooCommerce does not add these parameters by default,
 * so we normalize every generated URL and redirect legacy requests.
 */
function woodmart_child_append_reset_form_query_args( $url ) {
    if ( empty( $url ) ) {
        return $url;
    }

    $fragment = '';

    if ( false !== strpos( $url, '#' ) ) {
        list( $url, $fragment ) = explode( '#', $url, 2 );
        $fragment = '#' . $fragment;
    }

    $url = remove_query_arg( 'show-reset-form', $url );
    $url = add_query_arg( 'show-reset-form', 'true', $url );

    $has_action = false;
    $parts      = wp_parse_url( $url );

    if ( isset( $parts['query'] ) ) {
        $query_args = array();
        parse_str( $parts['query'], $query_args );
        $has_action = array_key_exists( 'action', $query_args );
    }

    if ( ! $has_action ) {
        $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'action';
    }

    return $url . $fragment;
}

/**
 * Determine if a URL points to the WooCommerce My Account lost-password endpoint.
 *
 * @param string $url URL to inspect.
 * @return bool
 */
function woodmart_child_is_myaccount_lost_password_url( $url ) {
    if ( empty( $url ) || ! function_exists( 'wc_get_page_permalink' ) || ! function_exists( 'wc_get_endpoint_url' ) ) {
        return false;
    }

    $account_page = wc_get_page_permalink( 'myaccount' );

    if ( empty( $account_page ) ) {
        return false;
    }

    $endpoint_path = wp_parse_url( wc_get_endpoint_url( 'lost-password', '', $account_page ), PHP_URL_PATH );
    $url_path      = wp_parse_url( $url, PHP_URL_PATH );

    if ( empty( $endpoint_path ) || empty( $url_path ) ) {
        return false;
    }

    return untrailingslashit( $endpoint_path ) === untrailingslashit( $url_path );
}

/**
 * Force WooCommerce reset-password emails/CTAs to use the Woodmart parameters.
 *
 * @param string      $url  Base reset URL provided by WooCommerce.
 * @param WP_User|int $user Target user object or ID.
 * @return string
 */
function woodmart_child_filter_reset_password_link( $url, $user ) {
    unset( $user );

    return woodmart_child_append_reset_form_query_args( $url );
}
add_filter( 'woocommerce_reset_password_link', 'woodmart_child_filter_reset_password_link', 20, 2 );

/**
 * Make every generated My Account lost-password link open the reset form step.
 *
 * @param string $url      The generated URL.
 * @param string $redirect Optional redirect path.
 * @return string
 */
function woodmart_child_filter_lostpassword_url( $url, $redirect ) {
    unset( $redirect );

    if ( ! woodmart_child_is_myaccount_lost_password_url( $url ) ) {
        return $url;
    }

    return woodmart_child_append_reset_form_query_args( $url );
}
add_filter( 'lostpassword_url', 'woodmart_child_filter_lostpassword_url', PHP_INT_MAX, 2 );

/**
 * Redirect requests that land on the lost-password endpoint without the expected parameters.
 */
function woodmart_child_force_reset_form_view() {
    if ( ! function_exists( 'is_wc_endpoint_url' ) || ! function_exists( 'wc_get_current_url' ) ) {
        return;
    }

    if ( ! is_wc_endpoint_url( 'lost-password' ) ) {
        return;
    }

    $key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( empty( $key ) || empty( $login ) ) {
        return;
    }

    $has_show   = isset( $_GET['show-reset-form'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $has_action = isset( $_GET['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( $has_show && $has_action ) {
        return;
    }

    $redirect = woodmart_child_append_reset_form_query_args( esc_url_raw( wc_get_current_url() ) );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'template_redirect', 'woodmart_child_force_reset_form_view', 0 );