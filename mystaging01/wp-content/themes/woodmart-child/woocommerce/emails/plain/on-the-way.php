<?php
/**
 * Plain text email template for the "On the way" status.
 *
 * @package AlSaadroseCheckoutCustomizations
 */

defined( 'ABSPATH' ) || exit;

echo '= ' . $email_heading . " =\n\n";

if ( $order ) {
    printf( __( 'Hi %s,', 'alsaadrose-checkout' ) . "\n\n", $order->get_billing_first_name() );
}

echo __( 'We are happy to let you know that your order is on the way.', 'alsaadrose-checkout' ) . "\n\n";

if ( $order ) {
    printf( __( 'Order number: %s', 'alsaadrose-checkout' ) . "\n\n", $order->get_order_number() );
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo __( 'Thank you for shopping with us!', 'alsaadrose-checkout' ) . "\n\n";
