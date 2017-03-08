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

    var hidePrice = false;

    if (typeof window.QUIQQER_PRODUCTS_HIDE_PRICE !== 'undefined' &&
        window.QUIQQER_PRODUCTS_HIDE_PRICE) {
        hidePrice = true;
    }

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

            this.$Price = null;
            this.$Vat   = null;

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

            this.$Price = this.$Elm;

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            if (hidePrice) {
                return;
            }

            Currency.convertWithSign(
                this.getAttribute('price'),
                this.getAttribute('currency')
            ).then(function (result) {
                    this.$Price.set('html', result);
                    this.$Price.set('title', result);
                }.bind(this),
                function () {
                }
            );
        },

        /**
         * event : on import
         */
        $onImport: function (self, Elm) {
            if (hidePrice) {
                return;
            }

            Currency.addEvent('onChange', this.refresh);

            if (Elm.get('data-qui-options-currency')) {
                this.setAttribute('currency', Elm.get('data-qui-options-currency'));
            }

            if (Elm.getElement('.qui-products-price-display-vat')) {
                this.$Price = Elm.getElement('.qui-products-price-display-value');
                this.$Vat   = Elm.getElement('.qui-products-price-display-vat');
            } else {
                this.$Price = Elm;
            }

            this.$Price.addClass('quiqqer-price');

            this.setPrice(Elm.get('data-qui-options-price'));
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            if (hidePrice) {
                return;
            }

            this.setPrice(this.getAttribute('price'));
        },

        /**
         * Set the price for the display
         *
         * @param {Number} price
         * @param {String} [CurrencyCode]
         */
        setPrice: function (price, CurrencyCode) {
            if (hidePrice) {
                return;
            }

            CurrencyCode = CurrencyCode || false;

            if (this.getAttribute('currency')) {
                CurrencyCode = this.getAttribute('currency');
            }

            this.setAttribute('price', price);
            this.setAttribute('currency', CurrencyCode);
            this.refresh();
        },

        /**
         * Set the currency for the display
         *
         * @param {String} CurrencyCode
         */
        setCurrency: function (CurrencyCode) {
            if (hidePrice) {
                return;
            }

            this.setAttrbute('currency', CurrencyCode);
            this.refresh();
        }
    });
});
