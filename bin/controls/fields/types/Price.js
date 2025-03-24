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

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/Price',

        Binds: [
            '$onImport',
            'openBruttoInput',
            'openBruttoInputForCurrencies',
            '$calcBruttoPrice'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Button = null;
            this.$Formatter = null;
            this.$currencyList = null;

            this.$calcTimer = null;
            this.$productId = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const Elm = this.getElm();
            Elm.type = 'hidden';

            this.$Input = new Element('input', {
                type: 'text',
                'class': 'field-container-field',
                value: Elm.value
            }).inject(Elm, 'after');

            this.getFormatter().then((Formatter) => {
                this.$Input.placeholder = Formatter.format(1000);
            });

            this.$Button = new Element('button', {
                'class': 'field-container-item',
                html: '<span class="fa fa-calculator"></span>',
                title: QUILocale.get('quiqqer/products', 'fields.control.price.brutto'),
                styles: {
                    cursor: 'pointer',
                    lineHeight: 30,
                    textAlign: 'center',
                    width: 50
                },
                events: {
                    click: this.openBruttoInput
                }
            }).inject(this.$Input, 'after');

            this.$BruttoInput = new Element('span', {
                'class': 'field-container-item',
                html: '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    borderRight: 0,
                    lineHeight: 30,
                    maxWidth: 100
                }
            }).inject(this.$Input, 'after');

            if (Elm.getParent('.qui-panel')) {
                const Panel = QUI.Controls.getById(Elm.getParent('.qui-panel').get('data-quiid'));

                if (Panel.getAttribute('productId')) {
                    this.$productId = Panel.getAttribute('productId');
                }
            }


            this.setValue(Elm.value);

            this.$Input.addEvent('change', () => {
                this.$calcBruttoPrice();
                this.fireEvent('change', [this]);
            });


            // load currencies
            require(['package/quiqqer/currency/bin/Currency'], (Currencies) => {
                Promise.all([
                    Currencies.getCurrencies(),
                    this.getFormatter()
                ]).then((r) => {
                    const currencies = r[0];
                    const Formatter = r[1];

                    if (currencies.length <= 1) {
                        return;
                    }

                    // multiple currencies allowed
                    const label = this.getElm().getParent('label');

                    if (!label) {
                        return;
                    }

                    let options = {};

                    if (
                        this.$Elm.getAttribute('data-options')
                        && typeof this.$Elm.getAttribute('data-options') === 'string'
                    ) {
                        try {
                            options = JSON.parse(this.$Elm.getAttribute('data-options'));
                        } catch (e) {
                        }
                    }

                    if (typeof options.currencies === 'undefined') {
                        options.currencies = {};
                    }


                    this.$currencyList = new Element('div', {
                        'data-name': 'currency-list',
                        styles: {
                            display: 'none',
                            flexDirection: 'column',
                            width: '100%'
                        }
                    }).inject(label, 'after');

                    currencies.forEach((currency) => {
                        const container = new Element('label.field-container', {
                            html: '' +
                                '<span class="field-container-item">' + currency.code + '</span>' +
                                '<input type="text" />' +
                                '<span class="field-container-item displayNode" style="max-width: 100px; border-right: 0;"></span>' +
                                '<button class="field-container-item" style="width: 50px; cursor:pointer;">' +
                                '   <span class="fa fa-calculator"></span>' +
                                '</button>'
                        }).inject(this.$currencyList);

                        const displayNode = container.querySelector('.displayNode');
                        const input = container.querySelector('input');

                        container.querySelector('button')
                            .addEventListener('click', this.openBruttoInputForCurrencies);

                        input.placeholder = Formatter.format(1000);
                        input.setAttribute('data-currency', currency.code);
                        input.classList.add('field-container-field');

                        input.addEventListener('blur', () => {
                            this.$updateValues();
                            this.$calcBruttoPriceForCurrency(
                                input.value,
                                currency.code,
                                displayNode
                            );
                        });

                        if (typeof options.currencies[currency.code] !== 'undefined') {
                            input.value = options.currencies[currency.code];

                            this.$calcBruttoPriceForCurrency(
                                input.value,
                                currency.code,
                                displayNode
                            );
                        }
                    });

                    // opener button
                    const title = label.querySelector('.field-container-item:first-child');
                    title.style.position = 'relative';

                    const opener = new Element('span', {
                        html: '<span class="fa fa-caret-right"></span>',
                        styles: {
                            cursor: 'pointer',
                            position: 'absolute',
                            right: 0,
                            top: 0,
                            textAlign: 'center',
                            width: 20,
                            lineHeight: 20,
                        },
                        events: {
                            click: (event) => {
                                event.stop();

                                if (this.$currencyList.style.display === 'none') {
                                    this.$currencyList.style.display = 'flex';
                                    opener.innerHTML = '<span class="fa fa-caret-down"></span>';
                                } else {
                                    this.$currencyList.style.display = 'none';
                                    opener.innerHTML = '<span class="fa fa-caret-right"></span>';
                                }
                            }
                        }
                    }).inject(title);
                });
            });

            this.$calcBruttoPrice();
        },

        $updateValues: function () {
            const values = {
                value: this.$Input.value,
                currencies: {}
            };

            this.$currencyList.querySelectorAll('input').forEach((input) => {
                if (input.value !== '') {
                    values.currencies[input.getAttribute('data-currency')] = input.value;
                }
            });

            this.$Elm.value = JSON.stringify(values);
        },

        /**
         * disable this control
         */
        disable: function () {
            this.$Input.disabled = true;
            this.$Button.disabled = true;
            this.$Button.setStyle('cursor', 'not-allowed');
        },

        /**
         * enable this control
         */
        enable: function () {
            this.$Input.disabled = false;
            this.$Button.disabled = false;
            this.$Button.setStyle('cursor', 'pointer');
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
            if (typeof value !== 'number' &&
                (value === '' || !value || value === 'false')
            ) {
                this.getElm().value = '';
                this.$Input.value = '';
                this.$calcBruttoPrice();
                return;
            }

            const groupingSeparator = QUILocale.getGroupingSeparator();
            const decimalSeparator = QUILocale.getDecimalSeparator();

            const foundGroupSeparator = typeOf(value) === 'string' && value.indexOf(groupingSeparator) >= 0;
            const foundDecimalSeparator = typeOf(value) === 'string' && value.indexOf(decimalSeparator) >= 0;

            if ((foundGroupSeparator || foundDecimalSeparator) && !(foundGroupSeparator && !foundDecimalSeparator)) {
                this.getElm().value = value;
                this.$Input.value = value;
                return;
            }

            this.getFormatter().then((Formatter) => {
                this.getElm().value = Formatter.format(parseFloat(value));
                this.$Input.value = Formatter.format(parseFloat(value));
            });

            this.$calcBruttoPrice();
        },

        /**
         * Returns the field ID
         *
         * @return {String|Boolean|Number}
         */
        getFieldId: function () {
            let name = this.getElm().name;

            name = name.replace('field-', '');
            name = parseInt(name);

            return name || false;
        },

        /**
         * Opens the brutto / gross input
         */
        openBruttoInput: function (e) {
            e.stopPropagation();
            e.preventDefault();

            new PriceBruttoWindow({
                productId: this.$productId,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', '');
                    },

                    onSubmit: (Win, value) => {
                        this.setValue(value);
                        this.$calcBruttoPrice();
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

            if (this.$Input.value === '') {
                this.$BruttoInput.innerHTML = '---';
                this.$BruttoInput.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                    price: '---'
                });
                return;
            }

            this.$BruttoInput.innerHTML = '<span class="fa fa-spinner fa-spin"></span>';

            this.$calcTimer = (() => {
                if (!this.$Input) {
                    return;
                }

                QUIAjax.get('package_quiqqer_products_ajax_products_calcBruttoPrice', (price) => {
                    this.$BruttoInput.innerHTML = price;
                    this.$BruttoInput.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                        price: price
                    });
                }, {
                    'package': 'quiqqer/products',
                    price: this.$Input.value,
                    formatted: 1,
                    productId: this.$productId
                });
            }).delay(500);
        },

        /**
         * Return formatter
         *
         * @return {Promise}
         */
        getFormatter: function () {
            if (this.$Formatter !== null) {
                return Promise.resolve(this.$Formatter);
            }

            return QUILocale.getSystemLocale().then((SystemLocale) => {
                // admin format
                this.$Formatter = SystemLocale.getNumberFormatter({
                    minimumFractionDigits: 8
                });

                return this.$Formatter;
            });
        },

        // region currencies

        openBruttoInputForCurrencies: function (e) {
            e.stopPropagation();
            e.preventDefault();

            new PriceBruttoWindow({
                productId: this.$productId,
                events: {
                    onSubmit: (Win, value) => {
                        e.target.getParent('label').querySelector('input').value = value;
                    }
                }
            }).open();
        },

        $calcBruttoPriceForCurrency: function (netPrice, currency, displayNode) {
            if (typeof displayNode === 'undefined') {
                return;
            }

            if (netPrice === '') {
                displayNode.innerHTML = '---';
                displayNode.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                    price: '---'
                });
                return;
            }

            displayNode.innerHTML = '<span class="fa fa-spinner fa-spin"></span>';

            QUIAjax.get('package_quiqqer_products_ajax_products_calcBruttoPrice', (price) => {
                displayNode.innerHTML = price;
                displayNode.title = QUILocale.get(lg, 'fields.control.price.quantity.title', {
                    price: price
                });
            }, {
                'package': 'quiqqer/products',
                price: netPrice,
                currency: currency,
                formatted: 1,
                productId: this.$productId
            });
        },

        //endregion
    });
});
