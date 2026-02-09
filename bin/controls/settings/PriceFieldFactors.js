/**
 * Manage individual multipliers for price fields
 */
define('package/quiqqer/products/bin/controls/settings/PriceFieldFactors', [

    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/utils/Elements',

    'package/quiqqer/tax/bin/controls/taxList/AvailableTaxListWindow',

    'Locale',
    'Mustache',
    'Ajax',

    'text!package/quiqqer/products/bin/controls/settings/PriceFieldFactors.html',
    'css!package/quiqqer/products/bin/controls/settings/PriceFieldFactors.css'

], function (QUIControl, QUIConfirm, QUILoader, QUIButton, QUIElements, TaxListWindow, QUILocale, Mustache, QUIAjax,
    template) {
    "use strict";

    const lg             = 'quiqqer/products';
    const cssClassHidden = 'quiqqer-products-settings-pricefieldfactors__hidden';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/settings/PriceFieldFactors',

        Binds: [
            '$onImport',
            '$getPriceFields',
            '$onEntryActivateClick',
            '$updateValue',
            '$onUpdatePricesClick',
            '$onClickRoundingVatSelect',
            '$onChangeRoundingType',
            '$getVatEntries',
            '$onEntryTypeChange'
        ],

        options: {
            category_id: null
        },

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
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-settings-pricefieldfactors'
            }).inject(this.$Input, 'after');

            this.Loader.inject(this.$Elm);
            this.Loader.show();

            let Value = {};

            if (this.$Input.value !== '') {
                Value = JSON.decode(this.$Input.value);
            }

            Promise.all([
                this.$getPriceFields(),
                this.$getVatEntries()
            ]).then((result) => {
                const priceFields = result[0];
                const vatEntries  = result[1];
                const isCategory  = !!this.getAttribute('category_id');

                this.$Elm.set('html', Mustache.render(template, {
                    priceFields: priceFields,
                    vatEntries : vatEntries,

                    labelMultiplier      : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelMultiplier'),
                    labelUpdateOnSave    : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelUpdateOnSave'),
                    labelCategoryPriority: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelCategoryPriority'),
                    descCategoryPriority : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.descCategoryPriority'),
                    isCategory           : isCategory,

                    labelRoundingVat          : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingVat'),
                    labelRoundingVatOptionNone: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingVatOptionNone'),

                    headerRounding                           : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.headerRounding'),
                    labelRoundingType                        : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingType'),
                    labelRoundingTypeOptionNone              : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeOptionNone'),
                    labelRoundingTypeCommercial              : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeCommercial'),
                    labelRoundingTypeCommercial9             : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeCommercial9'),
                    labelRoundingTypeOptionUp                : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeOptionUp'),
                    labelRoundingTypeOptionUp9               : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeOptionUp9'),
                    labelRoundingTypeOptionDown              : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeOptionDown'),
                    labelRoundingTypeOptionDown9             : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeOptionDown9'),
                    labelRoundingTypeDecimalCustomValue      : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeDecimalCustomValue'),
                    labelRoundingTypeCommercialDecimals      : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeCommercialDecimals'),
                    labelRoundingTypeCommercialDecimalsSingle: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingTypeCommercialDecimalsSingle'),
                    descRoundingVat                          : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.descRoundingVat'),

                    labelType                : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelType'),
                    labelTypeOptionMultiplier: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelTypeOptionMultiplier'),
                    labelTypeOptionFixed     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelTypeOptionFixed'),
                    labelFixedSurchargeAmount: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelFixedSurchargeAmount'),

                    labelFixedSurchargePriorityBeforeRounding: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelFixedSurchargePriorityBeforeRounding'),
                    labelFixedSurchargePriorityAfterRounding : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelFixedSurchargePriorityAfterRounding'),
                }));

                new QUIButton({
                    text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.update'),
                    textimage: 'fa fa-asterisk',
                    events   : {
                        onClick: this.$onUpdatePricesClick
                    }
                }).inject(this.$Elm.getElement('.quiqqer-products-settings-pricefieldfactors-buttons'));

                const CategoryPriorityInput = this.$Elm.getElement('input[name="categoryPriority"]');

                if (CategoryPriorityInput) {
                    if ('categoryPriority' in Value) {
                        CategoryPriorityInput.value = Value.categoryPriority;
                    }

                    CategoryPriorityInput.addEvent('change', this.$updateValue);
                    CategoryPriorityInput.addEvent('keyup', this.$updateValue);
                }

                const entries = this.$Elm.getElements('.quiqqer-products-settings-pricefieldfactors-entry');

                entries.forEach((Entry) => {
                    Entry.getElement('input[name="active"]').addEvent('change', this.$onEntryActivateClick);

                    const fieldId                  = Entry.get('data-id');
                    const ActiveCheckbox           = Entry.getElement('input[name="active"]');
                    const MultiplierInput          = Entry.getElement('input[name="multiplier"]');
                    const SourceFieldSelect        = Entry.getElement('select[name="sourceFieldId"]');
                    const UpdateOnSaveCheckbox     = Entry.getElement('input[name="update_on_save"]');
                    const RoundingVatSelect        = Entry.getElement('select[name="rounding_vat"]');
                    const RoundingTypeSelect       = Entry.getElement('select[name="rounding_type"]');
                    const RoundingCustomValueInput = Entry.getElement('input[name="decimal_custom_value"]');
                    const SurchargeAmountInput     = Entry.getElement('input[name="fixedSurchargeAmount"]');
                    const SurchargePriorityInput   = Entry.getElement('select[name="fixedSurchargePriority"]');

                    MultiplierInput.addEvent('change', this.$updateValue);
                    MultiplierInput.addEvent('keyup', this.$updateValue);
                    SourceFieldSelect.addEvent('change', this.$updateValue);
                    UpdateOnSaveCheckbox.addEvent('change', this.$updateValue);
                    SurchargeAmountInput.addEvent('change', this.$updateValue);
                    SurchargePriorityInput.addEvent('change', this.$updateValue);

                    RoundingTypeSelect.addEvent('change', this.$updateValue);
                    RoundingVatSelect.addEvent('change', this.$updateValue);
                    RoundingCustomValueInput.addEvent('change', this.$updateValue);
                    //RoundingVatSelect.addEventListener('click', this.$onClickRoundingVatSelect);

                    if (fieldId in Value) {
                        const ConfigContainer = Entry.getElement('.quiqqer-products-settings-pricefieldfactors-entry-multiplier');
                        const FieldSettings   = Value[fieldId];

                        if ('fixedSurchargeAmount' in FieldSettings) {
                            SurchargeAmountInput.value = FieldSettings.fixedSurchargeAmount;
                        }

                        if ('fixedSurchargePriority' in FieldSettings) {
                            SurchargePriorityInput.value = FieldSettings.fixedSurchargePriority;
                        }

                        ActiveCheckbox.checked = true;

                        MultiplierInput.disabled = false;
                        MultiplierInput.value    = FieldSettings.multiplier;

                        SourceFieldSelect.disabled = false;
                        SourceFieldSelect.value    = FieldSettings.sourceFieldId;

                        UpdateOnSaveCheckbox.disabled = false;
                        UpdateOnSaveCheckbox.checked  = FieldSettings.updateOnSave;

                        RoundingTypeSelect.value       = FieldSettings.rounding.type;
                        RoundingVatSelect.value        = FieldSettings.rounding.vat;
                        RoundingCustomValueInput.value = FieldSettings.rounding.custom;

                        ConfigContainer.removeClass(cssClassHidden);
                    }
                });
            });
        },

        /**
         * Click on "select rounding vat"
         *
         * @param {DocumentEvent} event
         */
        $onClickRoundingVatSelect: function (event) {
            const RoundingVatSelect = event.target;

            new TaxListWindow({
                events: {
                    onSubmit: (TaxListControl, value) => {
                        RoundingVatSelect.getElements('option').destroy();

                        new Element('option', {
                            value: value,
                            html : value + '%'
                        }).inject(RoundingVatSelect);

                        RoundingVatSelect.value = value;

                        this.$updateValue();
                    },
                    onCancel: () => {
                        RoundingVatSelect.getElements('option').destroy();

                        new Element('option', {
                            value: 'none',
                            html : QUILocale.get(
                                lg, 'controls.settings.PriceFieldFactors.tpl.labelRoundingVatOptionNone'
                            )
                        }).inject(RoundingVatSelect);

                        RoundingVatSelect.value = 'none';

                        this.$updateValue();
                    }
                }
            }).open();
        },

        /**
         * If user clicks the "active entry" checkbox
         *
         * @param {DocumentEvent} event
         */
        $onEntryActivateClick: function (event) {
            const Entry                = event.target.getParent('.quiqqer-products-settings-pricefieldfactors-entry');
            const MultiplierInput      = Entry.getElement('input[name="multiplier"]');
            const SourceFieldSelect    = Entry.getElement('select[name="sourceFieldId"]');
            const UpdateOnSaveCheckbox = Entry.getElement('input[name="update_on_save"]');
            const ConfigContainer      = Entry.getElement('.quiqqer-products-settings-pricefieldfactors-entry-multiplier');

            MultiplierInput.disabled      = !event.target.checked;
            SourceFieldSelect.disabled    = !event.target.checked;
            UpdateOnSaveCheckbox.disabled = !event.target.checked;

            if (event.target.checked) {
                MultiplierInput.focus();
                ConfigContainer.removeClass(cssClassHidden);
            } else {
                ConfigContainer.addClass(cssClassHidden);
            }

            this.$updateValue();
        },

        /**
         * If user clicks "update prices now" button
         */
        $onUpdatePricesClick: function () {
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
                    onOpen: (Win) => {
                        const BtnAll = new QUIButton({
                            text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.confirm_all'),
                            textimage: 'fa fa-asterisk',
                            disabled : true,
                            events   : {
                                onClick: () => {
                                    Win.Loader.show();

                                    this.$updatePrices().then(() => {
                                        Win.close();
                                    });
                                }
                            }
                        });

                        const BtnActiveOnly = new QUIButton({
                            text     : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.confirm_active'),
                            textimage: 'fa fa-asterisk',
                            disabled : true,
                            events   : {
                                onClick: () => {
                                    Win.Loader.show();

                                    this.$updatePrices(true).then(() => {
                                        Win.close();
                                    });
                                }
                            }
                        });

                        Win.addButton(BtnAll);
                        Win.addButton(BtnActiveOnly);

                        Win.Loader.show();

                        this.$checkSystem().then(function (Data) {
                            Win.Loader.hide();

                            BtnAll.enable();
                            BtnActiveOnly.enable();

                            if (Data.timeSufficient) {
                                return;
                            }

                            const Content = Win.getContent();

                            new Element('p', {
                                'class': 'message-attention quiqqer-products-settings-pricefieldfactors-warning',
                                html   : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.info_cli')
                            }).inject(Content);

                            new Element('div', {
                                'class': 'quiqqer-products-settings-pricefieldfactors-cli',
                                html   : Data.commands.all + '<br/>' + Data.commands.active
                            }).inject(Content);

                            Win.setAttribute('maxHeight', 450);
                            Win.resize();
                        });
                    }
                }
            }).open();
        },

        /**
         * Update current price factors
         */
        $updateValue: function () {
            const entries               = this.$Elm.getElements('.quiqqer-products-settings-pricefieldfactors-entry');
            const Value                 = {};
            const CategoryPriorityInput = this.$Elm.getElement('input[name="categoryPriority"]');

            entries.forEach((Entry) => {
                const ActiveCheckbox               = Entry.getElement('input[name="active"]');
                const MultiplierInput              = Entry.getElement('input[name="multiplier"]');
                const SourceFieldSelect            = Entry.getElement('select[name="sourceFieldId"]');
                const UpdateOnSaveCheckbox         = Entry.getElement('input[name="update_on_save"]');
                const RoundingVatSelect            = Entry.getElement('select[name="rounding_vat"]');
                const RoundingTypeSelect           = Entry.getElement('select[name="rounding_type"]');
                const RoundingTypeCustomValueInput = Entry.getElement('input[name="decimal_custom_value"]');
                const SurchargeAmountInput         = Entry.getElement('input[name="fixedSurchargeAmount"]');
                const SurchargePriorityInput       = Entry.getElement('select[name="fixedSurchargePriority"]');

                if (!ActiveCheckbox.checked) {
                    return;
                }

                Value[Entry.get('data-id')] = {
                    fixedSurchargeAmount  : parseFloat(SurchargeAmountInput.value),
                    fixedSurchargePriority: SurchargePriorityInput.value,
                    multiplier            : parseFloat(MultiplierInput.value),
                    sourceFieldId         : parseInt(SourceFieldSelect.value),
                    updateOnSave          : UpdateOnSaveCheckbox.checked,
                    rounding              : {
                        vat   : RoundingVatSelect.value,
                        type  : RoundingTypeSelect.value,
                        custom: RoundingTypeCustomValueInput.value.trim()
                    }
                };
            });

            if (CategoryPriorityInput) {
                Value.categoryPriority = parseInt(CategoryPriorityInput.value);
            }

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

            return new Promise((resolve, reject) => {
                QUIAjax.post('package_quiqqer_products_ajax_settings_updatePrices', resolve, {
                    'package' : 'quiqqer/products',
                    activeOnly: activeOnly ? 1 : 0,
                    categoryId: this.getAttribute('category_id'),
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
        },

        /**
         * Check if system is capable of running product updates via web server.
         *
         * Returns CLI commands for manual execution.
         *
         * @return {Promise}
         */
        $checkSystem: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_products_ajax_settings_checkSystem', resolve, {
                    'package' : 'quiqqer/products',
                    categoryId: this.getAttribute('category_id'),
                    onError   : reject
                });
            });
        },

        /**
         * Get vat entries for rounding vat base.
         *
         * @return {Promise}
         */
        $getVatEntries: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_tax_ajax_getAvailableTax', resolve, {
                    'package': 'quiqqer/tax',
                    onError  : reject
                });
            });
        }
    });
});