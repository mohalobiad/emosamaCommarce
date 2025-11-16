<?php
/**
 * Plain text email template for the "Ready for shipping" status.
 *
 * @package AlSaadroseCheckoutCustomizations
 */

defined( 'ABSPATH' ) || exit;

echo '= ' . $email_heading . " =\n\n";

if ( $order ) {
    printf( __( 'Hi %s,', 'alsaadrose-checkout' ) . "\n\n", $order->get_billing_first_name() );
}

echo __( 'Your order is ready for shipping and will leave our warehouse soon.', 'alsaadrose-checkout' ) . "\n\n";

if ( $order ) {
    printf( __( 'Order number: %s', 'alsaadrose-checkout' ) . "\n\n", $order->get_order_number() );
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo __( 'We appreciate your patience and support.', 'alsaadrose-checkout' ) . "\n\n";
