(function($){
        $(function(){
                var $form = $('.aucm-form');
                var $index = $('#aucm_city_index');
                var $en = $('#aucm_city_en');
                var $ar = $('#aucm_city_ar');
                var $chips = $('.aucm-chip');
                var $reset = $('#aucm_reset_form');

                function clearEditing(){
                        $chips.removeClass('is-editing');
                        $index.val('');
                        if ( $form.length ) {
                                $form.find('.notice.aucm-editing').remove();
                        }
                }

                $chips.on('click', '.aucm-chip__label', function(){
                        var $chip = $(this).closest('.aucm-chip');
                        $chips.removeClass('is-editing');
                        $chip.addClass('is-editing');
                        $en.val($chip.data('en')).focus();
                        $ar.val($chip.data('ar'));
                        $index.val($chip.data('index'));
                });

                if ( $reset.length ) {
                        $reset.on('click', function(){
                                $en.val('');
                                $ar.val('');
                                clearEditing();
                        });
                }
        });
})(jQuery);
