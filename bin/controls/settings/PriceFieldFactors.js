/**
 * Manage individual multipliers for price fields
 *
 * @module package/quiqqer/products/bin/controls/settings/PriceFieldFactors
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/settings/PriceFieldFactors', [

    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',

    'Locale',
    'Mustache',
    'Ajax',

    'text!package/quiqqer/products/bin/controls/settings/PriceFieldFactors.html',
    'css!package/quiqqer/products/bin/controls/settings/PriceFieldFactors.css'

], function (QUIControl, QUIConfirm, QUILoader, QUIButton, QUILocale, Mustache, QUIAjax, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/settings/PriceFieldFactors',

        Binds: [
            '$onImport',
            '$getPriceFields',
            '$onEntryActivateClick',
            '$updateValue',
            '$onUpdatePricesClick'
        ],

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();
            this.$Input = null;
            this.$Elm   = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var self = this;

            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-settings-pricefieldfactors'
            }).inject(this.$Input, 'after');

            this.Loader.inject(this.$Elm);
            this.Loader.show();

            var Value = {};

            if (this.$Input.value !== '') {
                Value = JSON.decode(this.$Input.value);
            }

            this.$getPriceFields().then(function (priceFields) {
                self.$Elm.set('html', Mustache.render(template, {
                    priceFields    : priceFields,
                    labelMultiplier: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelMultiplier')
                }));

                new QUIButton({
                    text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.update'),
                    textimage: 'fa fa-asterisk',
                    events   : {
                        onClick: self.$onUpdatePricesClick
                    }
                }).inject(self.$Elm.getElement('.quiqqer-products-settings-pricefieldfactors-buttons'));

                var entries = self.$Elm.getElements('.quiqqer-products-settings-pricefieldfactors-entry');

                entries.forEach(function (Entry) {
                    Entry.getElement('input[name="active"]').addEvent('change', self.$onEntryActivateClick);

                    var fieldId = Entry.get('data-id');

                    var ActiveCheckbox    = Entry.getElement('input[name="active"]'),
                        MultiplierInput   = Entry.getElement('input[name="multiplier"]'),
                        SourceFieldSelect = Entry.getElement('select[name="sourceFieldId"]');

                    MultiplierInput.addEvent('change', self.$updateValue);
                    SourceFieldSelect.addEvent('change', self.$updateValue);

                    if (fieldId in Value) {
                        ActiveCheckbox.checked = true;

                        MultiplierInput.disabled = false;
                        MultiplierInput.value    = Value[fieldId].multiplier;

                        SourceFieldSelect.disabled = false;
                        SourceFieldSelect.value    = Value[fieldId].sourceFieldId;
                    }
                });
            });
        },

        /**
         * If user clicks the "active entry" checkbox
         *
         * @param {DocumentEvent} event
         */
        $onEntryActivateClick: function (event) {
            var MultiplierInput   = event.target.getParent().getElement('input[name="multiplier"]'),
                SourceFieldSelect = event.target.getParent().getElement('select[name="sourceFieldId"]');

            MultiplierInput.disabled   = !event.target.checked;
            SourceFieldSelect.disabled = !event.target.checked;

            if (event.target.checked) {
                MultiplierInput.focus();
            }

            this.$updateValue();
        },

        /**
         * If user clicks "update prices now" button
         */
        $onUpdatePricesClick: function () {
            var self = this;

            new QUIConfirm({
                maxHeight: 300,
                autoclose: false,

                information: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.updatePrices.information'),
                title      : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.updatePrices.title'),
                texticon   : 'fa fa-asterisk',
                text       : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.updatePrices.text'),
                icon       : 'fa fa-asterisk',

                cancel_button: {
                    text     : false,
                    textimage: 'icon-remove fa fa-remove'
                },
                ok_button    : false,
                events       : {
                    onOpen: function (Win) {
                        Win.addButton(new QUIButton({
                            text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.confirm_all'),
                            textimage: 'fa fa-asterisk',
                            events   : {
                                onClick: function () {
                                    Win.Loader.show();

                                    self.$updatePrices().then(function () {
                                        Win.close();
                                    });
                                }
                            }
                        }));

                        Win.addButton(new QUIButton({
                            text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.confirm_active'),
                            textimage: 'fa fa-asterisk',
                            events   : {
                                onClick: function () {
                                    Win.Loader.show();

                                    self.$updatePrices(true).then(function () {
                                        Win.close();
                                    });
                                }
                            }
                        }));
                    }
                }
            }).open();
        },

        /**
         * Update current price factors
         */
        $updateValue: function () {
            var entries = this.$Elm.getElements('.quiqqer-products-settings-pricefieldfactors-entry');
            var Value   = {};

            entries.forEach(function (Entry) {
                var ActiveCheckbox    = Entry.getElement('input[name="active"]'),
                    MultiplierInput   = Entry.getElement('input[name="multiplier"]'),
                    SourceFieldSelect = Entry.getElement('select[name="sourceFieldId"]');

                if (!ActiveCheckbox.checked) {
                    return;
                }

                Value[Entry.get('data-id')] = {
                    multiplier   : parseFloat(MultiplierInput.value),
                    sourceFieldId: parseInt(SourceFieldSelect.value)
                };
            });

            this.$Input.value = JSON.encode(Value);
        },

        /**
         * Update product prices
         *
         * @param {Boolean} [activeOnly]
         * @return {Promise}
         */
        $updatePrices: function (activeOnly) {
            activeOnly = activeOnly || false;

            return new Promise(function (resolve, reject) {
                QUIAjax.post('package_quiqqer_products_ajax_settings_updatePrices', resolve, {
                    'package' : 'quiqqer/products',
                    activeOnly: activeOnly ? 1 : 0,
                    onError   : reject
                });
            });
        },

        /**
         * Get product price fields
         *
         * @return {Promise}
         */
        $getPriceFields: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_products_ajax_settings_getPriceFields', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        }
    });
});