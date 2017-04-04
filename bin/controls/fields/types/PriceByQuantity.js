/**
 * @module package/quiqqer/products/bin/controls/fields/types/PriceByQuantity
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/fields/types/PriceByQuantity.css
 *
 * new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(1000)
 */
define('package/quiqqer/products/bin/controls/fields/types/PriceByQuantity', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/types/PriceByQuantity.css'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantity',

        Binds: [
            '$onImport',
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input    = null;
            this.$Price    = null;
            this.$Currency = null;
            this.$Quantity = null;

            this.$Formatter = QUILocale.getNumberFormatter({
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
            var Elm  = this.getElm(),
                data = {
                    price   : false,
                    quantity: ''
                };

            Elm.type = 'hidden';

            this.$Input = Elm;

            try {
                data = JSON.decode(this.$Input.value);
            } catch (e) {
            }

            if (!data || !("price" in data) || !("quantity" in data)) {
                data = {
                    price   : false,
                    quantity: ''
                };
            }

            if (data.price === '') {
                data.price = 0;
            }


            this.$Elm = new Element('div', {
                'class': 'field-container-field quiqqer-products-field-priceByQuantity'
            }).wraps(Elm);

            this.$Price = new Element('input', {
                'class'    : 'quiqqer-products-field-priceByQuantity-price',
                type       : 'text',
                placeholder: this.$Formatter.format(1000),
                events     : {
                    change: this.refresh,
                    blur  : this.refresh
                }
            }).inject(this.$Elm);

            this.setPriceValue(data.price);

            this.$Currency = new Element('span', {
                'class': 'quiqqer-products-field-priceByQuantity-currency',
                html   : '€'
            }).inject(this.$Elm);

            this.$Quantity = new Element('input', {
                'class'    : 'quiqqer-products-field-priceByQuantity-quantity',
                type       : 'number',
                placeholder: 'Stückzahl',
                value      : data.quantity,
                events     : {
                    change: this.refresh,
                    blur  : this.refresh
                }
            }).inject(this.$Elm);

            this.refresh();
        },

        /**
         * refresh
         */
        refresh: function () {
            this.$Input.value = JSON.encode({
                price   : this.$Price.value,
                quantity: this.$Quantity.value
            });

            this.fireEvent('change', [this]);
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * Return the current value
         */
        setValue: function (value) {
            if (value === '') {
                return;
            }

            if (typeOf(value) === 'number') {
                this.setPriceValue(value);
            }

            if (typeOf(value) === 'object') {
                if ("price" in value) {
                    this.setPriceValue(value.price);
                }

                if ("quantity" in value) {
                    this.$Quantity.value = parseInt(value.quantity);
                }
            }

            if (typeOf(value) === 'string' && value.match('{')) {
                try {
                    value = JSON.decode(value);

                    if ("price" in value) {
                        this.setPriceValue(value.price);
                    }

                    if ("quantity" in value) {
                        this.$Quantity.value = parseInt(value.quantity);
                    }
                } catch (e) {
                }
            } else if (typeOf(value) === 'string') {
                this.setPriceValue(value);
            }

            this.refresh();
        },

        /**
         * Return the current value
         */
        setPriceValue: function (value) {
            if (value === '' || !value || value === 'false') {
                this.$Price.value = '';
                return;
            }

            var groupingSeparator = QUILocale.getGroupingSeparator();
            var decimalSeparator  = QUILocale.getDecimalSeparator();

            var foundGroupSeparator   = typeOf(value) === 'string' && value.indexOf(groupingSeparator) >= 0;
            var foundDecimalSeparator = typeOf(value) === 'string' && value.indexOf(decimalSeparator) >= 0;

            if ((foundGroupSeparator || foundDecimalSeparator) && !(foundGroupSeparator && !foundDecimalSeparator)) {
                this.$Price.value = value;
                return;
            }

            this.$Price.value = this.$Formatter.format(parseFloat(value));
        },

        /**
         * Retuen the field ID
         *
         * @return {String|Boolean|Number}
         */
        getFieldId: function () {
            var name = this.$Input.name;

            name = name.replace('field-', '');
            name = parseInt(name);

            return name || false;
        }
    });
});
