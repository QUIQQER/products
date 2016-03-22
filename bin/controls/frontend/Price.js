/**
 * Price display
 *
 * @module package/quiqqer/products/bin/controls/Price
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
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
         * event : on import
         */
        $onImport: function (self, Elm) {
            this.setAttribute('price', Elm.get('data-price'));

            if (Elm.get('data-currency')) {
                this.setAttribute('currency', Elm.get('data-currency'));
            }

            Currency.convertWithSign(
                this.getAttribute('price'),
                this.getAttribute('currency')
            ).then(function (result) {

                self.getElm().set('html', result);

            }, function () {

            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            console.log('on inject');
        }
    });
});
