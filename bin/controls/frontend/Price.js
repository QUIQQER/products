/**
 * Price display
 *
 * @module package/quiqqer/products/bin/controls/Price
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/currency/bin/Currency
 */
define('package/quiqqer/products/bin/controls/frontend/Price', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/currency/bin/Currency'

], function (QUI, QUIControl, Currency) {

    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/Price',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        options: {
            price   : 0, // float
            currency: 'EUR'
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport : this.$onImport,
                onReplace: this.$onImport,
                onInject : this.$onInject
            });
        },

        /**
         * Refresh the display
         */
        create: function () {
            this.$Elm = new Element('span', {
                'data-qui'  : 'package/quiqqer/products/bin/controls/frontend/Price',
                'data-quiid': this.getId()
            });

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onImport: function (self, Elm) {
            if (Elm.get('data-currency')) {
                this.setAttribute('currency', Elm.get('data-currency'));
            }

            this.setPrice(Elm.get('data-price'));
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.setPrice(this.getAttribute('price'));
        },

        /**
         * Set the price for the display
         *
         * @param {Number} price
         */
        setPrice: function (price) {
            this.setAttribute('price', price);

            if (!this.getAttribute('price')) {
                return;
            }

            Currency.convertWithSign(
                this.getAttribute('price'),
                this.getAttribute('currency')
            ).then(function (result) {
                this.getElm().set('html', result);
            }.bind(this), function () {

            });
        }
    });
});
