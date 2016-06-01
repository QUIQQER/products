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

            this.$Viewable = null;
            this.$Buyable  = null;

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
                'class': 'quiqqer-products-permissions',
                styles : {
                    padding: 20
                }
            });

            return this.$Elm;
        },

        /**
         * events : on inject
         */
        $onInject: function () {
            Products.getChild(this.getAttribute('productId')).then(function (productData) {

                if (typeof productData.permissions === 'undefined') {
                    productData.permissions = {};
                }

                this.$Viewable = new Permission({
                    value     : productData.permissions['permission.viewable'] || false,
                    permission: 'permission.viewable',
                    title     : 'Darf Produkt sehen'
                }).inject(this.getElm());


                this.$Buyable = new Permission({
                    value     : productData.permissions['permission.buyable'] || false,
                    permission: 'permission.buyable',
                    title     : 'Darf Produkt kaufen'
                }).inject(this.getElm());

            }.bind(this));
        },

        /**
         * Return the value
         *
         * @returns {{[permission.viewable]: *, [permission.buyable]: *}}
         */
        getValue: function () {
            return {
                'permission.viewable': this.$Viewable.getValue(),
                'permission.buyable' : this.$Buyable.getValue()
            };
        },

        /**
         * save the permissions
         *
         * @return {Promise}
         */
        save: function () {
            return new Promise(function (resolve, reject) {
                var Product = Products.get(this.getAttribute('productId'));

                Product.setPermissions(this.getValue()).then(resolve, reject);

            }.bind(this));
        }
    });
});
