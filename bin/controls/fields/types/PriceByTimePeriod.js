/**
 * new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(1000)
 */
define('package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/fields/windows/PriceBrutto',
    'package/quiqqer/erp/bin/backend/utils/Money',
    'Locale',
    'Ajax',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod.html',
    'css!package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod.css'

], function (QUI, QUIControl, PriceBruttoWindow, MoneyUtils, QUILocale, QUIAjax, Mustache, template) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriod',

        Binds: [
            '$onImport',
            'refresh',
            'openBruttoInput',
            '$calcBruttoPrice'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Price = null;
            this.$Currency = null;
            this.$BruttoInput = null;
            this.$productId = null;

            this.$From = null;
            this.$FromTime = null;
            this.$To = null;
            this.$ToTime = null;
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
            this.$From.set('disabled', true);
            this.$To.set('disabled', true);
        },

        /**
         * Enable the control
         */
        enable: function () {
            this.$Price.set('disabled', false);
            this.$From.set('disabled', false);
            this.$To.set('disabled', false);
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const self = this;
            const Elm = this.getElm();
            let data = {
                price: false,
                from: false,
                to: false
            };

            Elm.type = 'hidden';

            this.$Input = Elm;

            try {
                data = JSON.decode(this.$Input.value);
            } catch (e) {
            }

            if (!data || !("price" in data) || !("from" in data) || !("to" in data)) {
                data = {
                    price: false,
                    from: false,
                    to: false
                };
            }

            if (data.price === '') {
                data.price = 0;
            }

            const lgPrefix = 'controls.fields.types.PriceByTimePeriod.template.';

            this.$Elm = new Element('div', {
                'class': 'field-container-field quiqqer-products-field-priceByTimePeriod',
                html: Mustache.render(template, {
                    placeholderPrice: self.$Formatter.format(1000),
                    labelTo: QUILocale.get(lg, lgPrefix + 'labelTo'),
                    labelFrom: QUILocale.get(lg, lgPrefix + 'labelFrom'),
                    labelToTime: QUILocale.get(lg, lgPrefix + 'labelToTime'),
                    labelFromTime: QUILocale.get(lg, lgPrefix + 'labelFromTime'),
                    titleBruttoCalcBtn: QUILocale.get(lg, 'fields.control.price.brutto')
                })
            }).inject(this.$Input, 'after');

            this.$Price = this.$Elm.getElement('input.quiqqer-products-field-priceByTimePeriod-price');

            this.$Price.addEvents({
                change: function () {
                    this.refresh();
                    this.$calcBruttoPrice();
                }.bind(this),

                blur: function () {
                    this.refresh();
                    this.$calcBruttoPrice();
                }.bind(this)
            });

            this.setPriceValue(data.price);

            this.$Currency = this.$Elm.getElement('span.quiqqer-products-field-priceByTimePeriod-currency');

            MoneyUtils.getCurrency().then(function (result) {
                self.$Currency.set('html', result.sign);
            }.bind(this));

            // Date inputs
            this.$To = this.$Elm.getElement('input.quiqqer-products-field-priceByTimePeriod-date[name="to"]');
            this.$To.addEvents({
                change: this.refresh,
                blur: this.refresh
            });

            this.$ToTime = this.$Elm.getElement('input.quiqqer-products-field-priceByTimePeriod-date[name="to_time"]');
            this.$ToTime.addEvents({
                change: this.refresh,
                blur: this.refresh
            });

            this.$From = this.$Elm.getElement('input.quiqqer-products-field-priceByTimePeriod-date[name="from"]');
            this.$From.addEvents({
                change: this.refresh,
                blur: this.refresh
            });

            this.$FromTime = this.$Elm.getElement('input.quiqqer-products-field-priceByTimePeriod-date[name="from_time"]');
            this.$FromTime.addEvents({
                change: this.refresh,
                blur: this.refresh
            });

            // Total (brutto) calculator
            this.$Brutto = this.$Elm.getElement('span.quiqqer-products-field-priceByTimePeriod-btn-bruttocalc');
            this.$Brutto.addEvent('click', this.openBruttoInput);

            this.$BruttoInput = this.$Elm.getElement('span.quiqqer-products-field-priceByTimePeriod-bruttoinput');

            if (this.$Elm.getParent('.qui-panel')) {
                const Panel = QUI.Controls.getById(this.$Elm.getParent('.qui-panel').get('data-quiid'));

                if (Panel.getAttribute('productId')) {
                    this.$productId = Panel.getAttribute('productId');
                }
            }

            this.setValue(data);
            this.refresh();
            this.$calcBruttoPrice();
        },

        /**
         * refresh
         */
        refresh: function () {
            let from = this.$From.value,
                to = this.$To.value;

            if (from && this.$FromTime.value) {
                from += ' ' + this.$FromTime.value;
            }

            if (to && this.$ToTime.value) {
                to += ' ' + this.$ToTime.value;
            }

            this.$Input.value = JSON.encode({
                price: this.$Price.value,
                from: from,
                to: to
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

            if (typeOf(value) === 'string' && value.match('{')) {
                try {
                    value = JSON.decode(value);
                } catch (e) {
                }
            }

            if (typeOf(value) === 'object') {
                if ("price" in value) {
                    this.setPriceValue(value.price);
                }

                if ("from" in value && value.from) {
                    let from = value.from;

                    if (from.indexOf(' ') !== -1) {
                        from = from.split(' ');

                        this.$From.value = from[0];
                        this.$FromTime.value = from[1];
                    } else {
                        this.$From.value = from;
                    }
                }

                if ("to" in value && value.to) {
                    let to = value.to;

                    if (to.indexOf(' ') !== -1) {
                        to = to.split(' ');

                        this.$To.value = to[0];
                        this.$ToTime.value = to[1];
                    } else {
                        this.$To.value = to;
                    }
                }
            }

            if (typeOf(value) === 'string') {
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

            const groupingSeparator = QUILocale.getGroupingSeparator();
            const decimalSeparator = QUILocale.getDecimalSeparator();

            const foundGroupSeparator = typeOf(value) === 'string' && value.indexOf(groupingSeparator) >= 0;
            const foundDecimalSeparator = typeOf(value) === 'string' && value.indexOf(decimalSeparator) >= 0;

            if ((foundGroupSeparator || foundDecimalSeparator) && !(foundGroupSeparator && !foundDecimalSeparator)) {
                this.$Price.value = value;
                this.$calcBruttoPrice();
                return;
            }

            this.$Price.value = this.$Formatter.format(parseFloat(value));
            this.$calcBruttoPrice();
        },

        /**
         * Returns the field ID
         *
         * @return {String|Boolean|Number}
         */
        getFieldId: function () {
            let name = this.$Input.name;

            name = name.replace('field-', '');
            name = parseInt(name);

            return name || false;
        },

        /**
         * Opens the brutto / gross input
         */
        openBruttoInput: function () {
            const self = this;

            new PriceBruttoWindow({
                productId: this.$productId,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', '');
                    },

                    onSubmit: function (Win, value) {
                        self.setPriceValue(value);
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
                this.$BruttoInput.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                    price: '---'
                });
                return;
            }

            this.$BruttoInput.innerHTML = '<span class="fa fa-spinner fa-spin"></span>';

            this.$calcTimer = (function () {
                const self = this;

                QUIAjax.get('package_quiqqer_products_ajax_products_calcBruttoPrice', function (price) {
                    self.$BruttoInput.innerHTML = price;
                    self.$BruttoInput.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                        price: price
                    });
                }, {
                    'package': 'quiqqer/products',
                    price: self.$Price.value,
                    formatted: 1,
                    productId: self.$productId
                });
            }).delay(500, this);
        }
    });
});
