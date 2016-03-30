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
            'refresh',
            '$onImport',
            '$onInject'
        ],

        options: {
            price   : 0, // float
            currency: false
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
                'data-quiid': this.getId(),
                'class'     : 'quiqqer-price'
            });

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            Currency.convertWithSign(
                this.getAttribute('price'),
                this.getAttribute('currency')
            ).then(function (result) {
                this.getElm().set('html', result);
            }.bind(this), function () {
            });
        },

        /**
         * event : on import
         */
        $onImport: function (self, Elm) {

            Currency.addEvent('onChange', this.refresh);

            if (Elm.get('data-qui-options-currency')) {
                this.setAttribute('currency', Elm.get('data-qui-options-currency'));
            }

            Elm.addClass('quiqqer-price');

            this.setPrice(Elm.get('data-qui-options-price'));
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
            this.refresh();
        }
    });
});
