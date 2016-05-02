/**
 * @module package/quiqqer/products/bin/controls/products/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte
 */
define('package/quiqqer/products/bin/controls/products/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',

    'css!package/quiqqer/products/bin/controls/products/search/Search.css'

], function (QUI, QUIControl, Ajax) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-search'
            });


            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            Ajax.get('package_quiqqer_products_ajax_search_backend_getSearchFieldData', function (result) {

                console.log(result);

            }, {
                'package': 'quiqqer/products'
            });
        }
    });
});
