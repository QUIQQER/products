/**
 * Control for update a field
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
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

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUIConfirm, QUIFormUtils, QUILocale, QUIAjax,
             Mustache, InputMultiLang, Handler, FieldUtils, Translation, template) {
    "use strict";

    var lg     = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/Create',

        Binds: [
            '$onInject'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Translation  = null;
            this.$WorkingTitle = null;

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
            var Elm = this.parent();

            Elm.set({
                'class': 'field-create',
                html   : Mustache.render(template, {
                    contentText                : '',
                    tableHeader                : QUILocale.get(lg, 'control.field.create.header'),
                    fieldTitle                 : QUILocale.get('quiqqer/system', 'title'),
                    fieldWorkingTitle          : QUILocale.get(lg, 'workingTitle'),
                    fieldType                  : QUILocale.get(lg, 'fieldtype'),
                    fieldOptions               : QUILocale.get(lg, 'fieldoptions'),
                    fieldPriority              : QUILocale.get(lg, 'priority'),
                    fieldPrefix                : QUILocale.get(lg, 'prefix'),
                    fieldSuffix                : QUILocale.get(lg, 'suffix'),
                    fieldSearchtype            : QUILocale.get(lg, 'searchtype'),
                    fieldRequired              : QUILocale.get(lg, 'requiredField'),
                    fieldRequiredDesc          : QUILocale.get(lg, 'requiredFieldDesc'),
                    fieldSystem                : QUILocale.get(lg, 'systemField'),
                    fieldSystemDesc            : QUILocale.get(lg, 'systemFieldDesc'),
                    fieldStandard              : QUILocale.get(lg, 'standardField'),
                    fieldStandardDesc          : QUILocale.get(lg, 'standardFieldDesc'),
                    fieldPublic                : QUILocale.get(lg, 'publicField'),
                    fieldPublicDesc            : QUILocale.get(lg, 'publicFieldDesc'),
                    fieldDefaultValue          : QUILocale.get(lg, 'fieldDefaultValue'),
                    fieldShowInDetails         : QUILocale.get(lg, 'showInDetails'),
                    fieldfieldShowInDetailsDesc: QUILocale.get(lg, 'showInDetailsDesc')
                })
            });

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this,
                Elm  = self.getElm(),
                id   = this.getAttribute('fieldId');

            this.$Translation = new Translation({
                'group'  : 'quiqqer/products',
                'var'    : 'products.field.' + id + '.title',
                'package': 'quiqqer/products'
            }).inject(Elm.getElement('.field-title'));

            this.$WorkingTitle = new Translation({
                'group'  : 'quiqqer/products',
                'var'    : 'products.field.' + id + '.workingtitle',
                'package': 'quiqqer/products'
            }).inject(Elm.getElement('.field-workingtitle'));

            Promise.all([
                Fields.getChild(id),
                Fields.getFieldTypes(),
                Fields.getFieldTypeSettings(),
                Fields.getSearchTypesForField(id)
            ]).then(function (result) {
                var i, len, settings;

                var fieldTypes         = result[1],
                    fieldData          = result[0],
                    fieldSettings      = result[2],
                    searchTypes        = result[3],
                    FieldTypes         = Elm.getElement('[name="type"]'),
                    FieldOptions       = Elm.getElement('[name="options"]'),
                    FieldPriority      = Elm.getElement('[name="priority"]'),
                    FieldPrefix        = Elm.getElement('[name="prefix"]'),
                    FieldSuffix        = Elm.getElement('[name="suffix"]'),
                    FieldRequired      = Elm.getElement('[name="requiredField"]'),
                    FieldSystem        = Elm.getElement('[name="systemField"]'),
                    FieldPublic        = Elm.getElement('[name="publicField"]'),
                    FieldShowInDetails = Elm.getElement('[name="showInDetails"]'),
                    FieldStandard      = Elm.getElement('[name="standardField"]');

                fieldTypes.sort(function (a, b) {
                    var aField = QUILocale.get(a.locale[0], a.locale[1]);
                    var bField = QUILocale.get(b.locale[0], b.locale[1]);

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
                        html           : QUILocale.get(fieldTypes[i].locale[0], fieldTypes[i].locale[1]),
                        value          : fieldTypes[i].name,
                        'data-settings': settings
                    }).inject(FieldTypes);
                }

                if (!searchTypes.length) {
                    new Element('span', {
                        'class': 'field-container-field',
                        html   : QUILocale.get(lg, 'fieldtype.not.searchable')
                    }).replaces(Elm.getElement('.field-search_type'));

                } else {
                    var FieldSearchType = new Element('select', {
                        name  : 'search_type',
                        value : fieldData.search_type,
                        styles: {
                            width: '100%'
                        }
                    }).inject(Elm.getElement('.field-search_type'));

                    for (i = 0, len = searchTypes.length; i < len; i++) {
                        new Element('option', {
                            html : QUILocale.get(lg, 'searchtype.' + searchTypes[i] + '.title'),
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
                        var DefaultValue = Elm.getElement('.field-defaultValue');

                        DefaultValue.getParent('tr').setStyle('display', null);

                        new Element('input', {
                            'class': 'field-container-field field-defaultValue',
                            value  : fieldData.defaultValue || '',
                            name   : 'defaultValue'
                        }).replaces(DefaultValue);

                        break;
                }


                // options
                var options = fieldData.options;

                if (typeOf(options) !== 'string') {
                    options = JSON.encode(options);
                }

                // set data to the form
                FieldTypes.value    = fieldData.type;
                FieldOptions.value  = options;
                FieldPriority.value = fieldData.priority;
                FieldPrefix.value   = fieldData.prefix;
                FieldSuffix.value   = fieldData.suffix;

                FieldRequired.checked      = fieldData.isRequired;
                FieldSystem.checked        = fieldData.isSystem;
                FieldStandard.checked      = fieldData.isStandard;
                FieldPublic.checked        = fieldData.isPublic;
                FieldShowInDetails.checked = fieldData.showInDetails;

                var loadSettings = function () {
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
                    Elm.getElement('[name="showInDetails"]').checked  = false;
                    Elm.getElement('[name="showInDetails"]').disabled = true;
                    return;
                }

                return FieldUtils.canUsedAsDetailField(
                    Elm.getElement('[name="type"]').value
                );
            }).then(function (canUsedAsDetail) {
                if (!canUsedAsDetail) {
                    Elm.getElement('[name="showInDetails"]').checked  = false;
                    Elm.getElement('[name="showInDetails"]').disabled = true;
                }
            }).then(function () {
                self.fireEvent('loaded');
            });
        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {
            var self = this,
                Elm  = self.getElm();

            return new Promise(function (resolve, reject) {

                if (!self.$Translation) {
                    return reject('Translation not found');
                }

                var Form        = Elm.getElement('form'),
                    fieldId     = self.getAttribute('fieldId'),
                    search_type = '';

                if (typeof Form.elements.search_type !== 'undefined') {
                    search_type = Form.elements.search_type.value;
                }

                // trigger update
                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('showMessages', false);

                }).then(function () {
                    var defaultValue = null;

                    if (typeof Form.elements.defaultValue !== 'undefined') {
                        defaultValue = Form.elements.defaultValue.value;
                    }

                    return Fields.updateChild(fieldId, {
                        type         : Form.elements.type.value,
                        search_type  : search_type,
                        prefix       : Form.elements.prefix.value,
                        suffix       : Form.elements.suffix.value,
                        priority     : Form.elements.priority.value,
                        standardField: Form.elements.standardField.checked ? 1 : 0,
                        requiredField: Form.elements.requiredField.checked ? 1 : 0,
                        publicField  : Form.elements.publicField.checked ? 1 : 0,
                        showInDetails: Form.elements.showInDetails.checked ? 1 : 0,
                        options      : Form.elements.options.value,
                        defaultValue : defaultValue
                    });
                }).then(function (PRODUCT_ARRAY_STATUS) {
                    if (PRODUCT_ARRAY_STATUS == Fields.PRODUCT_ARRAY_CHANGED) {
                        // product array changed,
                        return self.saveFieldToAllProducts();
                    }

                    return Promise.resolve();
                }).then(function () {
                    return self.$Translation.save();
                }).then(function () {
                    return self.$WorkingTitle.save();
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
            var self = this;

            return new Promise(function (resolve) {
                new QUIConfirm({
                    icon       : 'fa fa-file-text-o',
                    title      : QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.title'),
                    text       : QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.text', {
                        fieldId  : self.getAttribute('fieldId'),
                        fieldName: self.$Translation.getValue()
                    }),
                    texticon   : 'fa fa-file-text-o',
                    information: QUILocale.get('quiqqer/products', 'fields.window.productarray.changed.information', {
                        fieldId  : self.getAttribute('fieldId'),
                        fieldName: self.$Translation.getValue()
                    }),
                    maxHeight  : 500,
                    maxWidth   : 750,
                    autoclose  : false,
                    events     : {
                        onSubmit: function (Win) {
                            Win.Loader.show();
                            QUIAjax.post('package_quiqqer_products_ajax_fields_setProductFieldArray', function () {
                                Win.close();
                            }, {
                                'package': 'quiqqer/products',
                                fieldId  : self.getAttribute('fieldId')
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

            var self     = this,
                Option   = FieldTypes.getElement('[value="' + FieldTypes.value + '"]'),
                settings = Option.get('data-settings');

            var Form        = FieldTypes.getParent('form'),
                FormOptions = Form.elements.options,
                Container   = Form.getElement('.field-options'),
                Cell        = Container.getParent('td'),
                Label       = Cell.getElement('label');

            if (!Label) {
                Label = Cell.getElement('.field-container');
            }

            if (!FormOptions) {
                FormOptions = new Element('input', {
                    type: 'hidden',
                    name: 'options'
                }).inject(Container);
            }

            //FieldTypes.disabled = true;

            if (settings === '') {
                moofx([Container, Cell, Label]).animate({
                    height : 0,
                    opacity: 0,
                    margin : 0,
                    padding: 0
                }, {
                    duration: 200,
                    callback: function () {
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
                    }
                });

                return;
            }

            var Loader = new Element('span', {
                'class': 'fa fa-spinner fa-spin',
                styles : {
                    left    : 10,
                    position: 'absolute',
                    top     : 10
                }
            }).inject(Container);

            Label.setStyles({
                display : null,
                height  : 0,
                overflow: 'hidden',
                position: 'relative'
            });

            Cell.setStyles({
                display : null,
                overflow: 'hidden',
                padding : null
            });

            moofx([Label, Cell, Container]).animate({
                height : 40,
                opacity: 1
            }, {
                duration: 200,
                callback: function () {
                    require([settings], function (Control) {

                        Label.setStyles({
                            height  : Label.getSize().y,
                            position: null
                        });

                        Cell.setStyles({
                            height: null
                        });

                        Container.setStyles({
                            height : null,
                            opacity: null,
                            margin : null,
                            padding: null,
                            width  : Container.getSize().x
                        });

                        Loader.destroy();

                        new Control({
                            fieldId: self.getAttribute('fieldId')
                        }).imports(FormOptions);

                        var height   = Container.getScrollSize().y,
                            computed = Container.getComputedSize();

                        height = height +
                            computed['padding-top'] +
                            computed['padding-bottom'];

                        moofx(Label).animate({
                            height: height
                        }, {
                            duration: 200,
                            callback: function () {
                                //FieldTypes.disabled = false;
                                FieldTypes.focus();
                            }
                        });

                    }, function (err) {
                        console.error(err);
                        console.error(arguments);
                        //FieldTypes.disabled = false;
                    });
                }
            });

        }
    });
});
