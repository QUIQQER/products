/**
 * Price display
 *
 * @module package/quiqqer/products/bin/controls/Price
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/Price', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/currency/bin/Currency',
    'Locale'

], function(QUI, QUIControl, Currency, QUILocale) {

    'use strict';

    let hidePrice = false;

    if (typeof window.QUIQQER_PRODUCTS_HIDE_PRICE !== 'undefined' &&
        window.QUIQQER_PRODUCTS_HIDE_PRICE) {
        hidePrice = true;
    }

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/frontend/Price',

        Binds: [
            'refresh',
            '$onImport',
            '$onInject'
        ],

        options: {
            price: 0, // float
            currency: false
        },

        initialize: function(options) {
            this.parent(options);

            this.$Price = null;
            this.$Vat = null;
            this.$Prefix = null;

            this.addEvents({
                onImport: this.$onImport,
                onReplace: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Refresh the display
         */
        create: function() {
            this.$Elm = new Element('span', {
                'data-qui': 'package/quiqqer/products/bin/controls/frontend/Price',
                'data-quiid': this.getId(),
                'class': 'quiqqer-price'
            });

            this.$Price = this.$Elm;

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function() {
            if (hidePrice) {
                return;
            }

            // same currency
            if (this.getAttribute('currency') === this.getElm().get('data-qui-options-currency')) {
                // this.$Price.set('html', result);
                // this.$Price.set('title', result);
                return;
            }

            Currency.convertWithSign(
                this.getAttribute('price'),
                this.getElm().get('data-qui-options-currency'),
                this.getAttribute('currency')
            ).then((result) => {
                if (typeOf(result) === 'object') {
                    this.$Price.set('title', result.converted);
                    this.$Price.set('html', result.convertedRound);
                } else {
                    this.$Price.set('title', result);
                    this.$Price.set('html', result);
                }
            }, function() {
            });
        },

        /**
         * event : on import
         */
        $onImport: function(self, Elm) {
            if (hidePrice) {
                return;
            }

            QUI.addEvent('onQuiqqerCurrencyChange', (Instance, currency) => {
                this.setAttribute('currency', currency);
                this.refresh();
            });

            if (Elm.get('data-qui-options-currency')) {
                this.setAttribute('currency', Elm.get('data-qui-options-currency'));
            }

            this.$Price = Elm.getElement('.qui-products-price-display-value');

            if (!this.$Price) {
                return;
            }

            this.$Price.addClass('quiqqer-price');

            this.$Vat = Elm.getElement('.qui-products-price-display-vat');
            this.$Prefix = Elm.getElement('.qui-products-price-display-prefix');

            Currency.getCurrency().then((currency) => {
                if (this.getAttribute('currency') !== currency) {
                    this.setAttribute('price', Elm.get('data-qui-options-price'));
                    this.setAttribute('currency', currency);
                    this.refresh();
                    return;
                }

                this.setPrice(Elm.get('data-qui-options-price'));
            });

        },

        /**
         * event : on inject
         */
        $onInject: function() {
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
        setPrice: function(price, CurrencyCode) {
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
         * Only usable if the price is formated
         *
         * @param priceDisplay
         */
        setPriceDisplay: function(priceDisplay) {
            this.$Price.set('html', priceDisplay);
            this.$Price.set('title', priceDisplay);
        },

        /**
         * Set the currency for the display
         *
         * @param {String} CurrencyCode
         */
        setCurrency: function(CurrencyCode) {
            if (hidePrice) {
                return;
            }

            this.setAttrbute('currency', CurrencyCode);
            this.refresh();
        },

        /**
         * Return if the the price is minimal price and higher prices exists
         *
         * @return bool
         */
        isMinimalPrice: function() {
            return this.$isMinimalPrice;
        },

        /**
         * enables the minimal price
         * -> price from
         * -> ab
         */
        enableMinimalPrice: function() {
            this.$isMinimalPrice = true;

            if (this.$Prefix) {
                this.$Prefix.set('html', QUILocale.get('quiqqer/erp', 'price.starting.from'));
            }
        },

        /**
         * enables the minimal price
         * -> price from
         * -> ab
         */
        disableMinimalPrice: function() {
            this.isMinimalPrice = false;

            if (this.$Prefix) {
                this.$Prefix.set('html', '');
            }
        }
    });
});
