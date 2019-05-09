/**
 * @module package/quiqqer/products/bin/controls/products/AddVariantWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/AddVariantWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale'

], function (QUI, QUIConfirm, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/AddVariantWindow',

        Binds: [
            '$onSubmit'
        ],

        options: {
            productId: false,
            maxWidth : 600,
            maxHeight: 400,
            autoclose: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('icon', 'fa fa-shopping-bag');
            this.setAttribute('texticon', 'fa fa-shopping-bag');

            this.setAttribute(
                'title',
                QUILocale.get('quiqqer/products', 'variants.addVariant.window.title')
            );

            this.setAttribute(
                'text',
                QUILocale.get('quiqqer/products', 'variants.addVariant.window.text')
            );

            this.setAttribute(
                'information',
                QUILocale.get('quiqqer/products', 'variants.addVariant.window.information')
            );

            this.setAttribute('ok_button', {
                text     : QUILocale.get('quiqqer/products', 'variants.addVariant.window.button'),
                textimage: 'icon-ok fa fa-check'
            });

            this.addEvents({
                onSubmit: this.$onSubmit
            });
        },

        /**
         * event: on submit
         */
        $onSubmit: function () {
            var self = this;

            this.Loader.show();

            QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_create', function (variantId) {
                self.fireEvent('variantCreation', [variantId]);
                self.close();
            }, {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId')
            });
        }
    });
});