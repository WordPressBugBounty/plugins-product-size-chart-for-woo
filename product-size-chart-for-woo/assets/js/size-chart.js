if( ViPscw === undefined ) {
    var ViPscw = {};
}
jQuery( document ).ready( function ( $ ) {
    "use strict";

    const initPscwValue = ( element, type ) => {
        const termOptions = ( placeholder, taxonomy ) => {
            return {
                width: '100%',
                minimumInputLength: 1,
                placeholder: placeholder,
                allowClear: true,
                ajax: {
                    type: 'post',
                    url: VicPscwParams.ajaxUrl,
                    data: function (params) {
                        let query = {
                            taxonomy: taxonomy,
                            key_search: params.term,
                            action: 'pscw_search_term',
                            nonce: VicPscwParams.nonce,
                        };
                        return query;
                    },
                    processResults: function (data) {
                        return data ? data : {results: []};
                    }
                }
            };
        };

        switch ( type ) {
            case 'products':
                $( element ).select2( {
                    width: '100%',
                    minimumInputLength: 3,
                    placeholder: 'Product name...',
                    allowClear: true,
                    ajax: {
                        type: 'post',
                        url: VicPscwParams.ajaxUrl,
                        data: function (params) {
                            let query = {
                                key_search: params.term,
                                action: 'pscw_search_product',
                                nonce: VicPscwParams.nonce,
                            };
                            return query;
                        },
                        processResults: function (data) {
                            return data ? data : {results: []};
                        }
                    }
                } );
                break;
            case 'product_type':
                $( element ).select2( termOptions( 'Product type...', 'product_type' ) );
                break;
            case 'product_visibility':
                $( element ).select2( termOptions( 'Product visibility...', 'product_visibility' ) );
                break;
            case 'product_cat':
                $( element ).select2( termOptions( 'Product categories...', 'product_cat' ) );
                break;
            case 'product_tag':
                $( element ).select2( termOptions( 'Product tags...', 'product_tag' ) );
                break;
            case 'shipping_class':
                $( element ).select2( termOptions( 'Product shipping class...', 'product_shipping_class' ) );
                break;
        }
    };

    ViPscw.sizeChart = {
        init() {
            this.count = 0;
            const container = $( "#pscw_configure" );

            container.on( "change", this.change.bind( this ) );
            this.load();
        },

        load(){

            initPscwValue( "#pscw_assign_products", "products" );
            initPscwValue( "#pscw_assign_product_cat", "product_cat" );
            $( "#pscw_assign" ).select2( {
                width: '100%',
                minimumResultsForSearch: 'Infinity'
            } );
        },

        change(e) {
            let selectedElement = e.target;
            switch ( selectedElement.classList[0] ) {
                case "pscw_assign":
                    let val = $(selectedElement).val();
                    if ( val !== 'none' || val !== 'all' ) {
                        $( ".pscw_assign_pane.active" ).removeClass( "active" );
                        $( `.pscw_assign_pane[data-option="${val}"]` ).addClass( "active" );

                    }
                    break;
            }
        },

    }

    ViPscw.sizeChart.init();
} );