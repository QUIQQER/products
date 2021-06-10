/**
 * Frontend control for fields of type "UserInput"
 *
 * @module package/quiqqer/products/bin/controls/frontend/fields/UserInputWatcher
 * @author www.pcsg.de (Patrick Müller)
 *
 * @event onChange [{Object} self, {Number} fieldId]
 */
define('package/quiqqer/products/bin/controls/frontend/fields/UserInputWatcher', [

    'qui/controls/Control',
    'qui/controls/windows/Confirm',

    'package/quiqqer/order/bin/frontend/Basket',

    'Locale',
    'Ajax',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/frontend/fields/UserInputWatcher.Input.html'

], function (QUIControl, QUIConfirm, Basket, QUILocale, QUIAjax, Mustache, templateInput) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/UserInputWatcher',

        Binds: [
            '$init',
            '$onBasketAddProduct',
            '$checkProduct'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$UserInput = null;
            this.$init();
        },

        /**
         * event : on import
         */
        $init: function () {
            if (Basket) {
                Basket.addEvent('onAdd', this.$onBasketAddProduct);
            }
        },

        /**
         * If a product is added to the basket
         *
         * @param {Object} BasketControl - package/quiqqer/order/bin/frontend/classes/Basket
         * @param {Object} Product - package/quiqqer/order/bin/frontend/classes/Product
         */
        $onBasketAddProduct: function (BasketControl, Product) {
            this.$checkProduct(Product.getAttribute('id')).then(async (fieldData) => {
                if (!fieldData.length) {
                    return;
                }

                const FieldTexts = {};

                for (let i = 0, len = fieldData.length; i < len; i++) {
                    const FieldDataEntry = fieldData[i];

                    FieldTexts[FieldDataEntry.id] = await this.$getText(FieldDataEntry);
                }

                this.$setText(FieldTexts, Product.getAttribute('id'));
            });
        },

        /**
         * Open text prompt for
         *
         * @param {Object} FieldData
         * @return {Promise}
         */
        $getText: function (FieldData) {
            return new Promise((resolve, reject) => {
                new QUIConfirm({
                    maxHeight: 500,
                    maxWidth : 500,

                    autoclose         : false,
                    backgroundClosable: false,
                    titleCloseButton  : false,

                    title: QUILocale.get(lg, 'controls.UserInputWatcher.getText.title', {
                        productTitle: FieldData.productTitle
                    }),
                    icon : 'fa fa-edit',

                    cancel_button: {
                        text     : false,
                        textimage: 'icon-remove fa fa-remove'
                    },
                    ok_button    : {
                        text     : QUILocale.get(lg, 'controls.UserInputWatcher.getText.btn.submit'),
                        textimage: 'icon-ok fa fa-check'
                    },
                    events       : {
                        onOpen  : function (Win) {
                            Win.setContent(Mustache.render(templateInput, {
                                productTitle: FieldData.productTitle,
                                infoText    : QUILocale.get(lg, 'controls.UserInputWatcher.getText.tpl.infoText', {
                                    fieldTitle: FieldData.fieldTitle
                                }),
                                labelInput  : QUILocale.get(lg, 'controls.UserInputWatcher.getText.tpl.labelInput', {
                                    fieldTitle   : FieldData.fieldTitle,
                                    maxCharacters: FieldData.options.maxCharacters
                                }),
                                isInput     : FieldData.options.inputType === 'input',
                                maxLength   : FieldData.options.maxCharacters
                            }));
                        },
                        onSubmit: function (Win) {
                            resolve(Win.getContent().getElement('[name="userInput"]').value);
                            Win.close();
                        }
                    }
                }).open();
            });
        },

        /**
         * Set field text(s) to product
         *
         * @param {Array} FieldTexts
         * @param {Number} productId
         * @return {Promise}
         */
        $setText: function (FieldTexts, productId) {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_products_ajax_fields_userInput_setText', resolve, {
                    'package': 'quiqqer/products',
                    basketId : Basket.getAttribute('id'),
                    productId: productId,
                    text     : JSON.encode(FieldTexts),
                    onError  : reject
                });
            });
        },

        /**
         * Check if product is eligible for user input
         *
         * @param {Number} productId
         * @return {Promise}
         */
        $checkProduct: function (productId) {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_products_ajax_fields_userInput_checkProduct', resolve, {
                    'package': 'quiqqer/products',
                    productId: productId,
                    onError  : reject
                });
            });
        }
    });
});
