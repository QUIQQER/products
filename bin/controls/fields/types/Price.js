/**
 * @module package/quiqqer/products/bin/controls/fields/types/Price
 * @author www.pcsg.de (Henning Leutz)
 *
 * new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(1000)
 */
define('package/quiqqer/products/bin/controls/fields/types/Price', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/fields/windows/PriceBrutto',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, PriceBruttoWindow, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Price',

        Binds: [
            '$onImport',
            'openBruttoInput',
            '$calcBruttoPrice'
        ],

        initialize: function (options) {
            this.parent(options);

            // admin format
            this.$Formatter = QUILocale.getNumberFormatter({
                //style                : 'currency',
                //currency             : 'EUR',
                minimumFractionDigits: 8
            });

            this.$Button       = null;
            this.$$BruttoInput = null;
            this.$calcTimer    = null;

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

            this.$Elm.addClass('field-container-field');
            this.$Elm.type        = 'text';
            this.$Elm.placeholder = this.$Formatter.format(1000);

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
            }).inject(Elm, 'after');

            this.$BruttoInput = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    borderRight: 0,
                    lineHeight : 30,
                    maxWidth   : 100
                }
            }).inject(Elm, 'after');


            this.setValue(Elm.value);

            Elm.addEvent('change', function () {
                self.$calcBruttoPrice();
                self.fireEvent('change', [self]);
            });

            this.$calcBruttoPrice();
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
                this.$calcBruttoPrice();
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
            this.$calcBruttoPrice();
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
                        self.getElm().value = value;
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

            if (this.$Elm.value === '') {
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
                    price    : this.$Elm.value,
                    formatted: 1
                });
            }).delay(500, this);
        }
    });
});
