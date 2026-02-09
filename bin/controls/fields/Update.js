/**
 * Control for update a field
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/fields/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'Locale',
    'Ajax',
    'Mustache',
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/utils/Fields',
    'package/quiqqer/translator/bin/controls/Update',
    'package/quiqqer/products/bin/Products',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUIConfirm, QUIFormUtils, QUILocale, QUIAjax,
             Mustache, InputMultiLang, Handler, FieldUtils, Translation, Products, template
) {
    'use strict';

    const lg = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/Create',

        Binds: [
            '$onInject'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Translation = null;
            this.$WorkingTitle = null;
            this.$Description = null;

            this.$Suffix = null;
            this.$Prefix = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            const Elm = this.parent();

            Elm.set({
                'class': 'field-create',
                html: Mustache.render(template, {
                    contentText: '',
                    tableHeader: QUILocale.get(lg, 'control.field.create.header'),
                    fieldTitle: QUILocale.get('quiqqer/system', 'title'),
                    fieldWorkingTitle: QUILocale.get(lg, 'workingTitle'),
                    fieldType: QUILocale.get(lg, 'fieldtype'),
                    fieldOptions: QUILocale.get(lg, 'fieldoptions'),
                    fieldPriority: QUILocale.get(lg, 'priority'),
                    fieldPrefix: QUILocale.get(lg, 'prefix'),
                    fieldSuffix: QUILocale.get(lg, 'suffix'),
                    fieldSearchtype: QUILocale.get(lg, 'searchtype'),
                    fieldRequired: QUILocale.get(lg, 'requiredField'),
                    fieldRequiredDesc: QUILocale.get(lg, 'requiredFieldDesc'),
                    fieldSystem: QUILocale.get(lg, 'systemField'),
                    fieldSystemDesc: QUILocale.get(lg, 'systemFieldDesc'),
                    fieldStandard: QUILocale.get(lg, 'standardField'),
                    fieldStandardDesc: QUILocale.get(lg, 'standardFieldDesc'),
                    fieldPublic: QUILocale.get(lg, 'publicField'),
                    fieldPublicDesc: QUILocale.get(lg, 'publicFieldDesc'),
                    fieldDefaultValue: QUILocale.get(lg, 'fieldDefaultValue'),
                    fieldShowInDetails: QUILocale.get(lg, 'showInDetails'),
                    fieldfieldShowInDetailsDesc: QUILocale.get(lg, 'showInDetailsDesc'),
                    fieldConsiderPriceCalculation: QUILocale.get(lg, 'fieldConsiderPriceCalculation'),
                    fieldDescription: QUILocale.get(lg, 'control.field.create.tpl.fieldDescription'),
                    fieldDescriptionDesc: QUILocale.get(lg, 'control.field.create.tpl.fieldDescriptionDesc'),
                    fieldEditable: QUILocale.get(lg, 'fieldEditable'),
                    fieldEditableDesc: QUILocale.get(lg, 'fieldEditableDesc'),
                    fieldInherited: QUILocale.get(lg, 'fieldInherited'),
                    fieldInheritedDesc: QUILocale.get(lg, 'fieldInheritedDesc')
                })
            });

            Elm.getElement('.editable-inherited-table').setStyle('display', 'none');

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const self = this,
                Elm = self.getElm(),
                id = this.getAttribute('fieldId');

            this.$Translation = new Translation({
                'group': 'quiqqer/products',
                'var': 'products.field.' + id + '.title',
                'package': 'quiqqer/products'
            }).inject(Elm.getElement('.field-title'));

            this.$WorkingTitle = new Translation({
                'group': 'quiqqer/products',
                'var': 'products.field.' + id + '.workingtitle',
                'package': 'quiqqer/products'
            }).inject(Elm.getElement('.field-workingtitle'));

            this.$Description = new Translation({
                'group': 'quiqqer/products',
                'var': 'products.field.' + id + '.description',
                'package': 'quiqqer/products',
                createIfNotExists: true
            }).inject(Elm.getElement('.field-description'));

            Promise.all([
                Fields.getChild(id),
                Fields.getFieldTypes(),
                Fields.getFieldTypeSettings(),
                Fields.getSearchTypesForField(id)
            ]).then(function (result) {
                let i, len, settings;

                const fieldTypes = result[1],
                    fieldData = result[0],
                    fieldSettings = result[2],
                    searchTypes = result[3],
                    FieldTypes = Elm.getElement('[name="type"]'),
                    FieldOptions = Elm.getElement('[name="options"]'),
                    FieldPriority = Elm.getElement('[name="priority"]'),
                    FieldPrefix = Elm.getElement('[name="prefix"]'),
                    FieldSuffix = Elm.getElement('[name="suffix"]'),
                    FieldRequired = Elm.getElement('[name="requiredField"]'),
                    FieldSystem = Elm.getElement('[name="systemField"]'),
                    FieldPublic = Elm.getElement('[name="publicField"]'),
                    FieldShowInDetails = Elm.getElement('[name="showInDetails"]'),
                    FieldStandard = Elm.getElement('[name="standardField"]');

                fieldTypes.sort(function (a, b) {
                    const aField = QUILocale.get(a.locale[0], a.locale[1]);
                    const bField = QUILocale.get(b.locale[0], b.locale[1]);

                    if (aField === bField) {
                        return 0;
                    }

                    return aField < bField ? -1 : 1;
                });

                for (i = 0, len = fieldTypes.length; i < len; i++) {
                    settings = '';

                    if (id in fieldSettings) {
                        settings = fieldSettings[id];
                    }

                    new Element('option', {
                        html: QUILocale.get(fieldTypes[i].locale[0], fieldTypes[i].locale[1]),
                        value: fieldTypes[i].name,
                        'data-settings': settings
                    }).inject(FieldTypes);
                }

                if (!searchTypes.length) {
                    new Element('span', {
                        'class': 'field-container-field',
                        html: QUILocale.get(lg, 'fieldtype.not.searchable')
                    }).replaces(Elm.getElement('.field-search_type'));

                } else {
                    const FieldSearchType = new Element('select', {
                        name: 'search_type',
                        value: fieldData.search_type,
                        styles: {
                            width: '100%'
                        }
                    }).inject(Elm.getElement('.field-search_type'));

                    for (i = 0, len = searchTypes.length; i < len; i++) {
                        new Element('option', {
                            html: QUILocale.get(lg, 'searchtype.' + searchTypes[i] + '.title'),
                            value: searchTypes[i]
                        }).inject(FieldSearchType);
                    }

                    FieldSearchType.value = fieldData.search_type;
                }

                // field value
                switch (fieldData.type) {
                    case 'Url':
                    case 'BoolType':
                    case 'FloatType':
                    case 'Textarea':
                    case 'IntType':
                        const DefaultValue = Elm.getElement('.field-defaultValue');

                        DefaultValue.getParent('tr').setStyle('display', null);

                        new Element('input', {
                            'class': 'field-container-field field-defaultValue',
                            value: fieldData.defaultValue || '',
                            name: 'defaultValue'
                        }).replaces(DefaultValue);

                        break;
                }


                // options
                let options = fieldData.options;

                if (typeOf(options) !== 'string') {
                    options = JSON.encode(options);
                }

                // set data to the form
                FieldTypes.value = fieldData.type;
                FieldOptions.value = options;
                FieldPriority.value = fieldData.priority;
                FieldPrefix.value = fieldData.prefix;
                FieldSuffix.value = fieldData.suffix;

                FieldRequired.checked = fieldData.isRequired;
                FieldSystem.checked = fieldData.isSystem;
                FieldStandard.checked = fieldData.isStandard;
                FieldPublic.checked = fieldData.isPublic;
                FieldShowInDetails.checked = fieldData.showInDetails;

                const loadSettings = function () {
                    self.$loadSettings(this);
                }.bind(FieldTypes);


                this.$Prefix = new InputMultiLang().imports(
                    Elm.getElement('[name="prefix"]')
                );

                this.$Suffix = new InputMultiLang().imports(
                    Elm.getElement('[name="suffix"]')
                );


                FieldTypes.addEvent('change', loadSettings);
                FieldTypes.disabled = true;

                loadSettings();
            }.bind(this)).then(function () {
                return FieldUtils.canUsedAsDetailField(id);
            }).then(function (canUsedAsDetail) {
                if (!canUsedAsDetail) {
                    Elm.getElement('[name="showInDetails"]').checked = false;
                    Elm.getElement('[name="showInDetails"]').disabled = true;
                    return;
                }

                return FieldUtils.canUsedAsDetailField(
                    Elm.getElement('[name="type"]').value
                );
            }).then(function (canUsedAsDetail) {
                if (!canUsedAsDetail) {
                    Elm.getElement('[name="showInDetails"]').checked = false;
                    Elm.getElement('[name="showInDetails"]').disabled = true;
                }
            }).then(function () {
                // title description are always public
                if (id === 4 || id === 5) {
                    Elm.getElement('[name="publicField"]').checked = true;
                    Elm.getElement('[name="publicField"]').disabled = true;
                }

                self.fireEvent('loaded');
            });
        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {
            const self = this,
                Elm = self.getElm();

            return new Promise(function (resolve, reject) {

                if (!self.$Translation) {
                    return reject('Translation not found');
                }

                let Form = Elm.getElement('form'),
                    fieldId = self.getAttribute('fieldId'),
                    search_type = '';

                if (typeof Form.elements.search_type !== 'undefined') {
                    search_type = Form.elements.search_type.value;
                }

                // trigger update
                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('showMessages', false);

                }).then(function () {
                    let defaultValue = null;

                    if (typeof Form.elements.defaultValue !== 'undefined') {
                        defaultValue = Form.elements.defaultValue.value;
                    }

                    return Fields.updateChild(fieldId, {
                        type: Form.elements.type.value,
                        search_type: search_type,
                        prefix: Form.elements.prefix.value,
                        suffix: Form.elements.suffix.value,
                        priority: Form.elements.priority.value,
                        standardField: Form.elements.standardField.checked ? 1 : 0,
                        requiredField: Form.elements.requiredField.checked ? 1 : 0,
                        publicField: Form.elements.publicField.checked ? 1 : 0,
                        showInDetails: Form.elements.showInDetails.checked ? 1 : 0,
                        options: Form.elements.options.value,
                        defaultValue: defaultValue,
                        fieldEditable: Form.elements.fieldEditable.checked ? 1 : 0,
                        fieldInherited: Form.elements.fieldInherited.checked ? 1 : 0
                    });
                }).then(function (PRODUCT_ARRAY_STATUS) {
                    if (PRODUCT_ARRAY_STATUS == Fields.PRODUCT_ARRAY_CHANGED) {
                        // product array changed,
                        return self.saveFieldToAllProducts();
                    }
                }).then(function () {
                    return Promise.all([
                        self.$Translation.save(),
                        self.$WorkingTitle.save(),
                        self.$Description.save()
                    ]);
                }).then(function () {
                    return QUI.getMessageHandler();
                }).then(function (MH) {
                    MH.setAttribute('showMessages', true);

                    MH.addSuccess(
                        QUILocale.get(lg, 'message.field.successfully.created')
                    );

                }).then(resolve).catch(function (e) {
                    QUI.getMessageHandler().then(function (MH) {
                        MH.setAttribute('showMessages', true);
                        reject(e);
                    });
                });
            });
        },

        /**
         *
         * @returns {Promise}
         */
        saveFieldToAllProducts: function () {
            const self = this;

            return new Promise(function (resolve) {
                new QUIConfirm({
                    icon: 'fa fa-file-text-o',
                    title: QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.title'),
                    text: QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.text', {
                        fieldId: self.getAttribute('fieldId'),
                        fieldName: self.$Translation.getValue()
                    }),
                    texticon: 'fa fa-file-text-o',
                    information: QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.information', {
                        fieldId: self.getAttribute('fieldId'),
                        fieldName: self.$Translation.getValue()
                    }),
                    maxHeight: 500,
                    maxWidth: 750,
                    autoclose: false,
                    events: {
                        onOpen: function (Win) {
                            Win.Loader.show();

                            const SubmitBtn = Win.getButton('submit');

                            SubmitBtn.disable();

                            Products.getProductCount().then(function (count) {
                                if (count < 500) {
                                    SubmitBtn.enable();
                                    Win.Loader.hide();
                                    return;
                                }

                                QUIAjax.get(
                                    'package_quiqqer_products_ajax_products_getSetFieldAttributesToProductsCmd',
                                    function (cmd) {
                                        Win.Loader.hide();

                                        Win.setAttribute(
                                            'information',
                                            QUILocale.get(
                                                'quiqqer/products',
                                                'fields.window.productarray.changed.information.console_tool',
                                                {
                                                    cmd: cmd,
                                                    fieldId: self.getAttribute('fieldId'),
                                                    fieldName: self.$Translation.getValue()
                                                }
                                            )
                                        );

                                        resolve();
                                    },
                                    {
                                        'package': 'quiqqer/products',
                                        fieldId: self.getAttribute('fieldId')
                                    }
                                );
                            });
                        },
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            QUIAjax.post('package_quiqqer_products_ajax_fields_setProductFieldArray', function () {
                                Win.close();
                            }, {
                                'package': 'quiqqer/products',
                                fieldId: self.getAttribute('fieldId')
                            });
                        },

                        onClose: resolve
                    }
                }).open();
            });
        },

        /**
         * Load the extra settings from a field type
         *
         * @param {HTMLSelectElement} FieldTypes
         */
        $loadSettings: function (FieldTypes) {
            if (FieldTypes.value === '') {
                return;
            }

            const self = this,
                Option = FieldTypes.getElement('[value="' + FieldTypes.value + '"]'),
                settings = Option.get('data-settings');

            let Form = FieldTypes.getParent('form'),
                FormOptions = Form.elements.options,
                Container = Form.getElement('.field-options'),
                Cell = Container.getParent('td');

            if (!FormOptions) {
                FormOptions = new Element('input', {
                    type: 'hidden',
                    name: 'options'
                }).inject(Container);
            }

            //FieldTypes.disabled = true;

            if (settings === '') {
                Cell.setStyles({
                    display: 'none',
                    padding: null
                });

                Container.getChildren().each(function (Child) {
                    if (Child != FormOptions) {
                        Child.destroy();
                    }
                });

                //FieldTypes.disabled = false;
                FieldTypes.focus();
                return;
            }

            const Loader = new Element('span', {
                'class': 'fa fa-spinner fa-spin',
                styles: {
                    left: 10,
                    position: 'absolute',
                    top: 10
                }
            }).inject(Container);

            require([settings], function (Control) {
                Loader.destroy();

                new Control({
                    fieldId: self.getAttribute('fieldId')
                }).imports(FormOptions);

                FieldTypes.focus();
            }, function (err) {
                console.error(err);
                console.error(arguments);
                //FieldTypes.disabled = false;
            });
        }
    });
});
