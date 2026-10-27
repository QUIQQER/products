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

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/settings/PriceFieldFactors',

        Binds: [
            '$onImport',
            '$onRoundingToggle',
            '$getPriceFields',
            '$updateValue',
            '$onUpdatePricesClick',
            '$onClickRoundingVatSelect',
            '$getVatEntries'
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
            this.$Input = this.getElm();
            this.$Input.type = 'hidden';
            this.$Elm = document.createElement('div');
            this.$Elm.className = 'quiqqer-products-settings-pricefieldfactors';
            this.$Input.after(this.$Elm);

            const Content = document.createElement('div');
            Content.dataset.name = 'content';
            this.$Elm.appendChild(Content);
            this.Loader.inject(this.$Elm);
            this.Loader.show();

            let settings = {};

            try {
                const parsed = JSON.parse(this.$Input.value || '{}');

                if (
                    parsed
                    && typeof parsed === 'object'
                    && !Array.isArray(parsed)
                ) {
                    settings = parsed;
                }
            } catch (e) {
                // An empty or invalid configuration starts with all price factors disabled.
            }

            return Promise.all([
                this.$getPriceFields(),
                this.$getVatEntries()
            ]).then(([priceFields, availableVatEntries]) => {
                const vatEntries = [...availableVatEntries];

                if (!vatEntries.some(entry => Number(entry.vat) === 0)) {
                    vatEntries.unshift({vat: 0});
                }

                const fields = priceFields.map(field => ({
                    ...field,
                    sourceFields: priceFields,
                    configurationId: this.getId() + '-configuration-' + field.id,
                    factorHelpId: this.getId() + '-factor-help-' + field.id,
                    vatHelpId: this.getId() + '-vat-help-' + field.id,
                    roundingId: this.getId() + '-rounding-' + field.id
                }));

                Content.innerHTML = Mustache.render(template, {
                    priceFields: fields,
                    vatEntries,
                    isCategory: !!this.getAttribute('category_id'),
                    labelSourceField: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelSourceField'),
                    labelFactor: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelFactor'),
                    descFactor: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.descFactor'),
                    labelSurcharge: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelSurcharge'),
                    labelSurchargePriority: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelSurchargePriority'),
                    labelRounding: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelRounding'),
                    labelCalculation: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelCalculation'),
                    labelManual: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelManual'),
                    labelUpdateOnSave    : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelUpdateOnSave'),
                    labelCategoryPriority: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelCategoryPriority'),
                    descCategoryPriority : QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.descCategoryPriority'),

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
                });

                new QUIButton({
                    text: QUILocale.get(lg, 'controls.settings.PriceFieldFactors.btn.update'),
                    textimage: 'fa fa-refresh',
                    events: {
                        onClick: this.$onUpdatePricesClick
                    }
                }).inject(this.$Elm.querySelector('[data-name="buttons"]'));

                const CategoryPriority = this.$Elm.querySelector('[data-name="category-priority"]');

                if (CategoryPriority) {
                    CategoryPriority.value = settings.categoryPriority ?? 0;
                }

                this.$Elm.querySelectorAll('[data-name="entry"]').forEach(Entry => {
                    const fieldSettings = settings[Entry.dataset.id];

                    if (
                        fieldSettings
                        && typeof fieldSettings === 'object'
                    ) {
                        Entry.querySelector('[data-name="active"]').checked = true;
                        Entry.querySelector('[data-name="multiplier"]').value = fieldSettings.multiplier ?? 1;
                        Entry.querySelector('[data-name="source-field"]').value = fieldSettings.sourceFieldId;
                        Entry.querySelector('[data-name="update-on-save"]').checked = !!fieldSettings.updateOnSave;
                        Entry.querySelector('[data-name="surcharge-amount"]').value = fieldSettings.fixedSurchargeAmount ?? 0;
                        Entry.querySelector('[data-name="surcharge-priority"]').value = fieldSettings.fixedSurchargePriority ?? 'beforeRounding';
                        Entry.querySelector('[data-name="rounding-type"]').value = fieldSettings.rounding?.type ?? 'none';
                        Entry.querySelector('[data-name="rounding-vat"]').value = fieldSettings.rounding?.vat ?? 0;
                        Entry.querySelector('[data-name="rounding-custom"]').value = fieldSettings.rounding?.custom ?? '';
                        const hasRounding = (
                            (fieldSettings.rounding?.type ?? 'none') !== 'none'
                            || (fieldSettings.rounding?.custom ?? '') !== ''
                            || fieldSettings.fixedSurchargePriority === 'afterRounding'
                        );
                        Entry.querySelector('[data-name="rounding-options"]').hidden = !hasRounding;
                        Entry.querySelector('[data-name="rounding-toggle"]').setAttribute('aria-expanded', String(hasRounding));
                    }

                    this.$updateEntry(Entry);
                });

                this.$Elm.addEventListener('click', this.$onRoundingToggle);
                this.$Elm.addEventListener('input', this.$updateValue);
                this.$Elm.addEventListener('change', this.$updateValue);
            }).finally(() => {
                this.Loader.hide();
            });
        },

        /**
         * Show rounding options without changing the configured price rule.
         */
        $onRoundingToggle: function (event) {
            const Button = event.target.closest('[data-name="rounding-toggle"]');

            if (!Button || !this.$Elm.contains(Button)) {
                return;
            }

            const Entry = Button.closest('[data-name="entry"]');
            const Options = Entry.querySelector('[data-name="rounding-options"]');
            const expanded = Button.getAttribute('aria-expanded') !== 'true';
            Button.setAttribute('aria-expanded', String(expanded));
            Options.hidden = !expanded;
        },

        /**
         * Keep the configuration and its summary in sync with the activation checkbox.
         */
        $updateEntry: function (Entry) {
            const active = Entry.querySelector('[data-name="active"]').checked;
            const Configuration = Entry.querySelector('[data-name="configuration"]');
            const Summary = Entry.querySelector('[data-name="summary"]');
            Configuration.hidden = !active;
            Configuration.disabled = !active;
            Entry.classList.toggle('is-active', active);

            if (!active) {
                Summary.textContent = QUILocale.get(lg, 'controls.settings.PriceFieldFactors.tpl.labelManual');
                return;
            }

            const Source = Entry.querySelector('[data-name="source-field"]');
            const multiplier = Entry.querySelector('[data-name="multiplier"]').value;
            const sourceTitle = Source.selectedOptions[0]?.textContent ?? '';
            const Formatter = QUILocale.getNumberFormatter({maximumFractionDigits: 10});
            const factor = multiplier === '' ? '…' : Formatter.format(Number(multiplier));
            Summary.textContent = sourceTitle + ' × ' + factor;
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
            const settings = {};
            const CategoryPriority = this.$Elm.querySelector('[data-name="category-priority"]');

            this.$Elm.querySelectorAll('[data-name="entry"]').forEach(Entry => {
                this.$updateEntry(Entry);

                if (!Entry.querySelector('[data-name="active"]').checked) {
                    return;
                }

                settings[Entry.dataset.id] = {
                    fixedSurchargeAmount: Number.parseFloat(Entry.querySelector('[data-name="surcharge-amount"]').value),
                    fixedSurchargePriority: Entry.querySelector('[data-name="surcharge-priority"]').value,
                    multiplier: Number.parseFloat(Entry.querySelector('[data-name="multiplier"]').value),
                    sourceFieldId: Number.parseInt(Entry.querySelector('[data-name="source-field"]').value, 10),
                    updateOnSave: Entry.querySelector('[data-name="update-on-save"]').checked,
                    rounding: {
                        vat: Entry.querySelector('[data-name="rounding-vat"]').value,
                        type: Entry.querySelector('[data-name="rounding-type"]').value,
                        custom: Entry.querySelector('[data-name="rounding-custom"]').value.trim()
                    }
                };
            });

            if (CategoryPriority) {
                settings.categoryPriority = Number.parseInt(CategoryPriority.value, 10);
            }

            this.$Input.value = JSON.stringify(settings);
            this.$Input.dispatchEvent(new Event('change', {bubbles: true}));
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
