/**
 * @module package/quiqqer/products/bin/controls/invoice/Product
 *
 * Control / Dispplay für Product Item in Invoice / Rechnungen
 *
 * @require package/quiqqer/invoice/bin/backend/controls/InvoiceItemsProduct
 * @require Locale
 * @require Ajax
 * @require package/quiqqer/products/bin/Products
 */
define('package/quiqqer/products/bin/controls/invoice/Product', [

    'package/quiqqer/invoice/bin/backend/controls/InvoiceItemsProduct',
    'Locale',
    'Ajax',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/classes/Product'

], function (InvoiceProduct, QUILocale, QUIAjax, Products, Product) {
    "use strict";

    return new Class({

        Extends: InvoiceProduct,
        Type: 'package/quiqqer/products/bin/controls/invoice/Product',

        Binds: [
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('type', 'QUI\\ERP\\Products\\Product\\Product');

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.showLoader();

            var self = this,
                P = new Product({
                    id: this.getAttribute('productId')
                });

            P.getTitle().then(function (title) {
                self.setTitle(title);
                return P.getDescription();

            }).then(function (description) {
                self.setDescription(description);
                self.hideLoader();
            });
        }
    });
});
