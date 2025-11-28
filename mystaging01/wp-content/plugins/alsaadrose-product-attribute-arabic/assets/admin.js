(function( $ ) {
    'use strict';

    function getAttributeIndex( $attribute ) {
        const $position = $attribute.find( 'input.attribute_position' ).first();

        if ( $position.length ) {
            const indexFromValue = parseInt( $position.val(), 10 );
            if ( ! Number.isNaN( indexFromValue ) ) {
                return indexFromValue;
            }

            const nameAttr = $position.attr( 'name' );
            if ( nameAttr ) {
                const match = nameAttr.match( /attribute_position\[(\d+)\]/ );
                if ( match && match[1] ) {
                    return parseInt( match[1], 10 );
                }
            }
        }

        return Date.now();
    }

    function buildArabicRows( index ) {
        const nameId = 'attribute_names_ar_' + index;
        const valueId = 'attribute_values_ar_' + index;

        return [
            '<tr class="asarab-row">\n' +
                '    <td colspan="2">\n' +
                '        <label for="' + nameId + '">Arabic name (optional):</label>\n' +
                '        <input type="text" class="widefat" id="' + nameId + '" name="attribute_names_ar[' + index + ']" value="" />\n' +
                '    </td>\n' +
                '</tr>',
            '<tr class="asarab-row">\n' +
                '    <td colspan="2">\n' +
                '        <label for="' + valueId + '">Arabic value(s) (use | to separate options):</label>\n' +
                '        <textarea class="widefat" rows="3" id="' + valueId + '" name="attribute_values_ar[' + index + ']" placeholder="Enter options for customers to choose from, f.e. “Blue” or “Large”. Use “|” to separate different options."></textarea>\n' +
                '    </td>\n' +
                '</tr>',
        ];
    }

    function ensureArabicFields( $context ) {
        $context.find( '.woocommerce_attribute' ).each( function() {
            const $attribute = $( this );
            const $tableBody = $attribute.find( '.woocommerce_attribute_data table tbody' );

            if ( ! $tableBody.length || $tableBody.find( '.asarab-row' ).length ) {
                return;
            }

            const index = getAttributeIndex( $attribute );
            const rows = buildArabicRows( index );

            $tableBody.append( rows.join( '\n' ) );
        } );
    }

    $( function() {
        const $attributesWrapper = $( '#product_attributes' );

        ensureArabicFields( $attributesWrapper );

        $( document.body ).on( 'woocommerce_added_attribute', function() {
            ensureArabicFields( $attributesWrapper );
        } );
    } );
})( jQuery );
