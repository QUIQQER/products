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

            var NumberFormatter = QUILocale.getNumberFormatter({
                minimumFractionDigits: 8
            });

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


            this.$Elm = new Element('div', {
                'class': 'field-container-field quiqqer-products-field-priceByQuantity'
            }).wraps(Elm);

            this.$Price = new Element('input', {
                'class'    : 'quiqqer-products-field-priceByQuantity-price',
                type       : 'text',
                placeholder: NumberFormatter.format(1000),
                value      : data.price ? NumberFormatter.format(data.price) : '',
                events     : {
                    change: this.refresh,
                    blur  : this.refresh
                }
            }).inject(this.$Elm);

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
        },

        /**
         * refresh
         */
        refresh: function () {
            this.$Input.value = JSON.encode({
                price   : this.$Price.value,
                quantity: this.$Quantity.value
            });


            console.log(this.$Input.value);
        }
    });
});
