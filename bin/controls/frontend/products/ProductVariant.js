/**
 * Product Variant view
 * Display a product variant in the content
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 *
 * @todo refresh details events
 */
define('package/quiqqer/products/bin/controls/frontend/products/ProductVariant', [

    'qui/QUI',
    'qui/controls/loader/Loader',
    'Ajax',
    'package/quiqqer/products/bin/controls/frontend/products/Product'

], function (QUI, QUILoader, QUIAjax, Product) {
    "use strict";

    // history popstate for mootools
    Element.NativeEvents.popstate = 2;

    return new Class({

        Extends: Product,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant',

        Binds: [
            '$onInject',
            '$onImport',
            '$init',
            '$onPopstateChange'
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

            // react for url change
            window.addEvent('popstate', this.$onPopstateChange);
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
         * event: on popstate change
         */
        $onPopstateChange: function () {
            if (this.$startInit === false) {
                return;
            }

            var self       = this,
                url        = QUIQQER_SITE.url,
                path       = window.location.pathname,

                variantUrl = path.substring(
                    path.lastIndexOf(url) + url.length
                );

            this.Loader.show();

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVariantByUrl', function (result) {
                if (!result) {
                    self.Loader.hide();
                }

                var Field;
                var Elm    = self.getElm();
                var fields = result.fields;

                for (var fieldId in fields) {
                    if (!fields.hasOwnProperty(fieldId)) {
                        continue;
                    }

                    Field = Elm.getElement('[name="field-' + fieldId + '"]');

                    if (Field) {
                        Field.value = fields[fieldId];
                    }
                }

                self.Loader.hide();
            }, {
                'package' : 'quiqqer/products',
                variantUrl: variantUrl,
                productId : this.getAttribute('productId')
            });
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
                window.history.pushState({}, "", result.url.toString());

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
