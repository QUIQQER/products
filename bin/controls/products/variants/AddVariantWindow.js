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
            '$onSubmit',
            '$onOpen'
        ],

        options: {
            productId: false,
            maxWidth : 600,
            maxHeight: 800,
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
                onSubmit: this.$onSubmit,
                onOpen  : this.$onOpen
            });
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            var self = this;

            this.Loader.show();

            var onInputChange = function (event) {
                var Target = event.target;
                var Group  = Target.getParent('table');
                var inputs = Group.getElements('input');

                for (var i = 0, len = inputs.length; i < len; i++) {
                    if (Target !== inputs[i]) {
                        inputs[i].checked = false;
                    }
                }
            };

            QUIAjax.get('package_quiqqer_products_ajax_products_variant_getVariantFields', function (fields) {
                require(['package/quiqqer/products/bin/utils/Fields'], function (FieldUtils) {

                    FieldUtils.renderVariantFieldSelect(fields).then(function (Node) {
                        Node.setStyle('display', 'inline-block');
                        Node.setStyle('margin-top', 20);
                        Node.inject(self.getContent());
                        Node.getElements('input').addEvent('change', onInputChange);

                        self.Loader.hide();
                    });

                });
            }, {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId')
            });
        },

        /**
         * event: on submit
         */
        $onSubmit: function () {
            var self = this;

            this.Loader.show();

            var inputs = this.getElm().getElements('input:checked');
            var fields = {};

            for (var i = 0, len = inputs.length; i < len; i++) {
                fields[inputs[i].get('name')] = inputs[i].get('value');
            }

            QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_create', function (variantId) {
                self.fireEvent('variantCreation', [variantId]);
                self.close();
            }, {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId'),
                fields   : JSON.encode(fields)
            });
        }
    });
});