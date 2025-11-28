( function ( $ ) {
    $( function () {
        var $attributesBox = $( '.product_attributes' );

        if ( ! $attributesBox.length ) {
            return;
        }

        // Remove WooCommerce's default click handler so we can inject the Arabic prompt and payload.
        $attributesBox.off( 'click', 'button.add_new_attribute' );

        $attributesBox.on( 'click', 'button.add_new_attribute', function ( event ) {
            event.preventDefault();

            $attributesBox.block( {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            } );

            var $wrapper = $( this ).closest( '.woocommerce_attribute' );
            var attribute = $wrapper.data( 'taxonomy' );
            var newAttributeName = window.prompt( woocommerce_admin_meta_boxes.new_attribute_prompt );

            if ( newAttributeName ) {
                var arabicName = window.prompt( asattrProductAttributes.arabicPrompt ) || '';
                var data = {
                    action: 'woocommerce_add_new_attribute',
                    taxonomy: attribute,
                    term: newAttributeName,
                    security: woocommerce_admin_meta_boxes.add_attribute_nonce,
                    name_AR: arabicName,
                };

                $.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {
                    if ( response.error ) {
                        window.alert( response.error );
                    } else if ( response.slug ) {
                        $wrapper
                            .find( 'select.attribute_values' )
                            .append( '<option value="' + response.term_id + '" selected="selected">' + response.name + '</option>' )
                            .trigger( 'change' );
                    }

                    $attributesBox.unblock();
                } );
            } else {
                $attributesBox.unblock();
            }
        } );
    } );
} )( jQuery );
