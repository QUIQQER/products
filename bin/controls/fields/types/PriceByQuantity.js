/**
 * @module package/quiqqer/products/bin/controls/fields/types/PriceByQuantity
 * @author www.pcsg.de (Henning Leutz)
 *
 * new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(1000)
 */
define('package/quiqqer/products/bin/controls/fields/types/PriceByQuantity', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/fields/windows/PriceBrutto',
    'package/quiqqer/erp/bin/backend/utils/Money',
    'Locale',
    'Ajax',

    'css!package/quiqqer/products/bin/controls/fields/types/PriceByQuantity.css'

], function (QUI, QUIControl, PriceBruttoWindow, MoneyUtils, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantity',

        Binds: [
            '$onImport',
            'refresh',
            'openBruttoInput',
            '$calcBruttoPrice'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input       = null;
            this.$Price       = null;
            this.$Currency    = null;
            this.$BruttoInput = null;

            this.$Quantity  = null;
            this.$calcTimer = null;

            this.$Formatter = QUILocale.getNumberFormatter({
                minimumFractionDigits: 8
            });

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Disable the control
         */
        disable: function () {
            this.$Price.set('disabled', true);
            this.$Quantity.set('disabled', true);
        },

        /**
         * Enable the control
         */
        enable: function () {
            this.$Price.set('disabled', false);
            this.$Quantity.set('disabled', false);
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
                    change: function () {
                        this.refresh();
                        this.$calcBruttoPrice();
                    }.bind(this),

                    blur: function () {
                        this.refresh();
                        this.$calcBruttoPrice();
                    }.bind(this)
                }
            }).inject(this.$Elm);

            this.setPriceValue(data.price);

            this.$Currency = new Element('span', {
                'class': 'quiqqer-products-field-priceByQuantity-currency'
            }).inject(this.$Elm);

            MoneyUtils.getCurrency().then(function (result) {
                this.$Currency.set('html', result.sign);
            }.bind(this));

            this.$Quantity = new Element('input', {
                'class'    : 'quiqqer-products-field-priceByQuantity-quantity',
                type       : 'number',
                placeholder: QUILocale.get('quiqqer/products', 'fields.control.price.quantity'),
                value      : data.quantity,
                events     : {
                    change: this.refresh,
                    blur  : this.refresh
                }
            }).inject(this.$Elm);

            new Element('span', {
                'class': 'quiqqer-products-field-priceByQuantity-quantity-apr',
                html   : QUILocale.get('quiqqer/products', 'fields.control.price.quantity.short')
            }).inject(this.$Elm);

            this.$Button = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-calculator"></span>',
                title  : QUILocale.get('quiqqer/products', 'fields.control.price.brutto'),
                styles : {
                    cursor    : 'pointer',
                    lineHeight: 30,
                    textAlign : 'center',
                    width     : 50
                },
                events : {
                    click: this.openBruttoInput
                }
            }).inject(this.$Elm, 'after');

            this.$BruttoInput = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    borderRight: 0,
                    lineHeight : 30,
                    maxWidth   : 100
                }
            }).inject(this.$Elm, 'after');

            this.refresh();
            this.$calcBruttoPrice();
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
                this.$calcBruttoPrice();
                return;
            }

            var groupingSeparator = QUILocale.getGroupingSeparator();
            var decimalSeparator  = QUILocale.getDecimalSeparator();

            var foundGroupSeparator   = typeOf(value) === 'string' && value.indexOf(groupingSeparator) >= 0;
            var foundDecimalSeparator = typeOf(value) === 'string' && value.indexOf(decimalSeparator) >= 0;

            if ((foundGroupSeparator || foundDecimalSeparator) && !(foundGroupSeparator && !foundDecimalSeparator)) {
                this.$Price.value = value;
                this.$calcBruttoPrice();
                return;
            }

            this.$Price.value = this.$Formatter.format(parseFloat(value));
            this.$calcBruttoPrice();
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
        },

        /**
         * Opens the brutto / gross input
         */
        openBruttoInput: function () {
            var self = this;

            new PriceBruttoWindow({
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', '');
                    },

                    onSubmit: function (Win, value) {
                        self.$Price.value = value;
                        self.$calcBruttoPrice();
                    }
                }
            }).open();
        },

        /**
         * calculate the brutto price
         */
        $calcBruttoPrice: function () {
            if (!this.$BruttoInput) {
                return;
            }

            if (this.$calcTimer) {
                clearTimeout(this.$calcTimer);
            }

            if (this.$Price.value === '') {
                this.$BruttoInput.innerHTML = '---';
                this.$BruttoInput.title     = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                    price: '---'
                });
                return;
            }

            this.$BruttoInput.innerHTML = '<span class="fa fa-spinner fa-spin"></span>';

            this.$calcTimer = (function () {
                var self = this;

                QUIAjax.get('package_quiqqer_products_ajax_products_calcBruttoPrice', function (price) {
                    self.$BruttoInput.innerHTML = price;
                    self.$BruttoInput.title     = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                        price: price
                    });
                }, {
                    'package': 'quiqqer/products',
                    price    : this.$Price.value,
                    formatted: 1
                });
            }).delay(500, this);
        }
    });
});
