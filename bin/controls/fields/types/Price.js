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

            // admin format
            this.$Formatter = QUILocale.getNumberFormatter({
                //style                : 'currency',
                //currency             : 'EUR',
                minimumFractionDigits: 8
            });


            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self  = this,
                Elm   = this.getElm(),
                price = parseFloat(Elm.value);

            Elm.addClass('field-container-field');
            Elm.type        = 'text';
            Elm.placeholder = this.$Formatter.format(1000);
            Elm.value       = this.$Formatter.format(price);

            Elm.addEvent('change', function () {
                self.fireEvent('change', [self]);
            });
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.getElm().value;
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        setValue: function (value) {
            this.getElm().value = this.$Formatter.format(parseFloat(value));
        },

        /**
         * Retuen the field ID
         *
         * @return {String|Boolean|Number}
         */
        getFieldId: function () {
            var name = this.getElm().name;

            name = name.replace('field-', '');
            name = parseInt(name);

            return name || false;
        }
    });
});
