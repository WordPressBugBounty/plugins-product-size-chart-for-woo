jQuery( document ).ready( function( $ ) {
    "use strict";
    /*Setting Field Rules*/
    const label = $('.woo_sc_sc_label'),
          scPosition = $('#woo_cs_select_position');

    $('.dropdown').dropdown();

    const handleShowFieldsBasedOnSizeChartType = () => {
        const scMulti = $('.woo_sc_multi'),
              scBtnPopupPosition = $('.woo_sc_btn_popup_position'),
              scBtnColor = $('.woo_sc_btn_color'),
              scBtnType = $('.woo_sc_btn_type');

        switch ( scPosition.val() ) {
            case 'before_add_to_cart':
            case 'after_add_to_cart':
                scMulti.hide();
                scBtnPopupPosition.hide();
                scBtnColor.show();
                scBtnType.show();
                break;
            case 'pop-up':
                scMulti.hide();
                scBtnPopupPosition.show();
                scBtnColor.show();
                scBtnType.show();
                break;
            case 'product_tabs':
                scMulti.show();
                scBtnPopupPosition.hide();
                scBtnType.hide();
                scBtnColor.hide();
                label.show();
                break;
            case 'none':
                scMulti.hide();
                scBtnPopupPosition.hide();
                scBtnType.hide();
                scBtnColor.hide();
                label.hide();
                break;
        }
    };

    handleShowFieldsBasedOnSizeChartType();

    scPosition.on('change', function () {
        handleShowFieldsBasedOnSizeChartType();
    });


    /*Color picker — WordPress core wp-color-picker (includes Iris)*/
    const colorPicker = $('.color-picker');
    if (colorPicker.length !== 0 && typeof $.fn.wpColorPicker === 'function') {
        colorPicker.wpColorPicker({
            change: function (event, ui) {
                $(this).css({backgroundColor: ui.color.toString()});
            },
            clear: function () {
                $(this).css({backgroundColor: ''});
            }
        });
    }


});
