<?php
/**
 * Email template for the "Ready for shipping" order status.
 *
 * @package AlSaadroseCheckoutCustomizations
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

$order_first_name = $order ? $order->get_billing_first_name() : '';
?>
<p><?php printf( esc_html__( 'Hi %s,', 'alsaadrose-checkout' ), esc_html( $order_first_name ) ); ?></p>
<p><?php esc_html_e( 'Your order is ready for shipping and will leave our warehouse soon.', 'alsaadrose-checkout' ); ?></p>
<p><?php printf( esc_html__( 'Order number: %s', 'alsaadrose-checkout' ), esc_html( $order ? $order->get_order_number() : '' ) ); ?></p>

<?php
if ( $order ) {
    do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
    do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}
?>

<p><?php esc_html_e( 'We appreciate your patience and support.', 'alsaadrose-checkout' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
