jQuery( function( $ ) {
    // Trigger checkout recalculation when payment method changes so COD fee is updated in the UI.
    $( document.body ).on( 'change', 'input[name="payment_method"]', function() {
        $( document.body ).trigger( 'update_checkout' );
    } );
} );