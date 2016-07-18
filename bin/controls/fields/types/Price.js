/**
 * @module package/quiqqer/products/bin/controls/fields/types/Price
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Locale
 *
 * new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(1000)
 */
define('package/quiqqer/products/bin/controls/fields/types/Price', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Price',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm   = this.getElm(),
                price = Elm.value;

            // admin format
            var NumberFormatter = QUILocale.getNumberFormatter({
                //style                : 'currency',
                //currency             : 'EUR',
                minimumFractionDigits: 8
            });

            Elm.addClass('field-container-field');
            Elm.type        = 'text';
            Elm.placeholder = NumberFormatter.format(1000);
            Elm.value       = NumberFormatter.format(price);
        }
    });
});
