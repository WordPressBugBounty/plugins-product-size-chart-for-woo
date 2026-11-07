(function ($) {
    'use strict';
    $(document).ready(function () {
        $('.pscw-option-select2:not(.pscw-option-select2-init)').each(function () {
            let select = $(this);
            let close_on_select = false, min_input = 2, placeholder = select.data('placeholder_select2'), action = '', type_select2 = select.data('type_select2');
            switch (type_select2) {
                case 'products':
                    action = 'pscw_search_product';
                    break;
            }
            select.addClass('pscw-option-select2-init').select2(select2_params(placeholder, action, close_on_select, min_input));
        });
        $('.vi-ui.checkbox:not(.pscw-checkbox-init)').addClass('pscw-checkbox-init').off().checkbox();

        $('#assign-all-product').on('change', function () {
            if ($(this).prop('checked')){
                $(this).parent().find('#'+$(this).attr('id')+'-val').val('1');
                $('.pscw-'+$(this).attr('id')+'-class').hide();
            }else {
                $(this).parent().find('#'+$(this).attr('id')+'-val').val('');
                $('.pscw-'+$(this).attr('id')+'-class').show() ;
            }
        }).trigger('change');
    });
    function select2_params(placeholder, action, close_on_select, min_input) {
        let result = {
            closeOnSelect: close_on_select,
            placeholder: placeholder,
            cache: true
        };
        if (action) {
            result['minimumInputLength'] = min_input;
            result['escapeMarkup'] = function (markup) {
                return markup;
            };
            result['ajax'] = {
                url: (typeof VicPscwParams !== 'undefined' && VicPscwParams.ajaxUrl) ? VicPscwParams.ajaxUrl : ajaxurl,
                dataType: 'json',
                type: 'GET',
                quietMillis: 50,
                delay: 250,
                data: function (params) {
                    return {
                        action: action,
                        key_search: params.term,
                        nonce: $('#woo_sc_nonce').val() || (typeof VicPscwParams !== 'undefined' ? VicPscwParams.nonce : ''),
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: false
            };
        }
        return result;
    }
}(jQuery));
