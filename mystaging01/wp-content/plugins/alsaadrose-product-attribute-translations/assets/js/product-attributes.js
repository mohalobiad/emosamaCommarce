(function ( $ ) {
    function sanitizeSlug( slug ) {
        if ( ! slug ) {
            return '';
        }

        slug = slug.toString().trim().toLowerCase();
        return slug.replace( /[^a-z0-9_-]+/g, '-' );
    }

    function getAttributeIndex( $attribute ) {
        var index = null;
        var $nameInput = $attribute.find( 'input[name^="attribute_names["]' );

        if ( $nameInput.length ) {
            var match = $nameInput.first().attr( 'name' ).match( /attribute_names\[(\d+)\]/ );
            if ( match && match[1] ) {
                index = match[1];
            }
        }

        if ( null === index ) {
            index = $attribute.index();
        }

        return index;
    }

    function getAttributeSlug( $attribute ) {
        var slug = $attribute.data( 'taxonomy' ) || '';
        var $nameInput = $attribute.find( 'input.attribute_name' );

        if ( ! slug && $nameInput.length ) {
            slug = $nameInput.first().val();
        }

        if ( ! slug ) {
            var $strong = $attribute.find( 'strong.attribute_name' );
            if ( $strong.length ) {
                slug = $strong.text();
            }
        }

        return sanitizeSlug( slug );
    }

    function ensureArabicFields( $attribute ) {
        if ( $attribute.data( 'aspatn-ready' ) ) {
            return;
        }

        var index = getAttributeIndex( $attribute );
        var slug = getAttributeSlug( $attribute );
        var existingName = slug && aspatnProductAttributes.names[ slug ] ? aspatnProductAttributes.names[ slug ] : '';
        var existingValues = slug && aspatnProductAttributes.values[ slug ] ? aspatnProductAttributes.values[ slug ] : {};
        var existingValuesString = '';

        if ( existingValues && 'object' === typeof existingValues ) {
            existingValuesString = Object.values( existingValues ).join( ' | ' );
        }

        var $nameCell = $attribute.find( 'td.attribute_name' );
        var $valuesCell = $attribute.find( 'td[rowspan="3"]' );

        if ( ! $nameCell.length || ! $valuesCell.length ) {
            return;
        }

        var $nameWrapper = $( '<div class="aspatn-field aspatn-name" />' );
        $nameWrapper.append( '<label>' + aspatnProductAttributes.labelPrompt + ':</label>' );
        $nameWrapper.append( '<input type="text" name="attribute_names_ar[' + index + ']" value="' + existingName + '" />' );
        $nameCell.append( $nameWrapper );

        var $valuesWrapper = $( '<div class="aspatn-field aspatn-values" />' );
        $valuesWrapper.append( '<label>' + aspatnProductAttributes.valuesPrompt + ':</label>' );
        $valuesWrapper.append( '<textarea name="attribute_values_ar[' + index + ']" rows="3">' + existingValuesString + '</textarea>' );
        $valuesCell.append( $valuesWrapper );

        $attribute.data( 'aspatn-ready', true );
    }

    function refreshArabicFields() {
        $( '.product_attributes .woocommerce_attribute' ).each( function () {
            ensureArabicFields( $( this ) );
        } );
    }

    $( function () {
        if ( ! $( '.product_attributes' ).length ) {
            return;
        }

        refreshArabicFields();

        var observerTarget = document.querySelector( '.product_attributes' );

        if ( observerTarget ) {
            var observer = new MutationObserver( function () {
                refreshArabicFields();
            } );

            observer.observe( observerTarget, { childList: true, subtree: true } );
        }

        $( document.body ).on( 'woocommerce_attribute_added wc-backbone-modal-after-open', function () {
            setTimeout( refreshArabicFields, 50 );
        } );
    } );
})( jQuery );
