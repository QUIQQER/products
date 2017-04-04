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
            var self = this,
                Elm  = this.getElm();

            Elm.addClass('field-container-field');
            Elm.type        = 'text';
            Elm.placeholder = this.$Formatter.format(1000);

            this.setValue(Elm.value);

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
         */
        setValue: function (value) {
            if (value === '' || !value || value === 'false') {
                this.getElm().value = '';
                return;
            }

            var groupingSeparator = QUILocale.getGroupingSeparator();
            var decimalSeparator  = QUILocale.getDecimalSeparator();

            var foundGroupSeparator   = typeOf(value) === 'string' && value.indexOf(groupingSeparator) >= 0;
            var foundDecimalSeparator = typeOf(value) === 'string' && value.indexOf(decimalSeparator) >= 0;

            if ((foundGroupSeparator || foundDecimalSeparator) && !(foundGroupSeparator && !foundDecimalSeparator)) {
                this.getElm().value = value;
                return;
            }

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
