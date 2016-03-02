/**
 * Product view
 * Display a product
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/Products
 */
define('package/quiqqer/products/bin/controls/frontend/products/Product', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Products'

], function (QUI, QUIControl, Products) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/Product',

        Binds: [
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Product = Products.get(this.getAttribute('productId'));
        },

        $onImport: function () {

        }
    });
});
