/**
 * Product Variant view
 * Display a product variant in the content
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/products/ProductVariant', [

    'qui/QUI',
    'qui/controls/loader/Loader',
    'Ajax',
    'package/quiqqer/products/bin/controls/frontend/products/Product'

], function (QUI, QUILoader, QUIAjax, Product) {
    "use strict";

    return new Class({

        Extends: Product,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant',

        Binds: [
            '$onInject',
            '$onImport',
            '$init'
        ],

        options: {
            closeable    : false,
            productId    : false,
            galleryLoader: true
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader     = new QUILoader();
            this.$startInit = false;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.parent().then(this.$init);
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.parent().then(this.$init);
        },

        /**
         * init the variant stuff
         */
        $init: function () {
            if (this.$startInit) {
                return;
            }

            var self = this;

            this.$startInit = true;
            this.Loader.inject(this.getElm());

            var fieldLists = this.getElm().getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            fieldLists.removeEvents('change');

            fieldLists.addEvent('change', function () {
                self.$refreshVariant();
            });
        },

        /**
         * refresh the variant control
         */
        $refreshVariant: function () {
            this.Loader.show();

            var self       = this;
            var fieldLists = this.getElm().getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            fieldLists = fieldLists.map(function (Elm) {
                var r = {};

                r[Elm.get('data-field')] = Elm.value;

                return r;
            });

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVariant', function (result) {
                var Ghost = new Element('div', {
                    html: result.control
                });

                document.title = result.title;

                var Control = Ghost.getElement(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/products/ProductVariant"]'
                );

                self.$startInit = false;

                if (Control) {
                    self.getElm().set('html', Control.get('html'));
                }

                QUI.parse(self.getElm()).then(function () {
                    self.$init();
                    self.Loader.hide();
                });
            }, {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId'),
                fields   : JSON.encode(fieldLists)
            });
        }
    });
});
