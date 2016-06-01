/**
 * @module package/quiqqer/products/bin/controls/products/permissions/Permissions
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require controls/Control
 */
define('package/quiqqer/products/bin/controls/products/permissions/Permissions', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/controls/products/permissions/Permission'

], function (QUI, QUIControl, Products, Permission) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : '',

        Binds: [
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode
         *
         * @returns {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-permissions'
            });

            return this.$Elm;
        },

        /**
         * events : on inject
         */
        $onInject: function () {
            Products.getChild(this.getAttribute('productId')).then(function (productData) {

                new Permission({
                    value     : '',
                    permission: 'product.permission.visible'
                }).inject(this.getElm());

                new Permission({
                    value     : '',
                    permission: 'product.permission.buyable'
                }).inject(this.getElm());

                console.log(productData);
            }.bind(this));
        }
    });
});
