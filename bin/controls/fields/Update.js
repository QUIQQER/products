/**
 * Control for update a field
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/utils/Form
 * @require Locale
 * @require package/quiqqer/products/bin/classes/Fields
 * @require package/quiqqer/translator/bin/controls/Update
 * @require text!package/quiqqer/products/bin/controls/fields/Create.html
 * @require css!package/quiqqer/products/bin/controls/fields/Update.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/fields/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Form',
    'Locale',
    'Mustache',
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUIFormUtils, QUILocale, Mustache, InputMultiLang, Handler, Translation, template) {
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
                    contentText      : '',
                    tableHeader      : QUILocale.get(lg, 'control.field.create.header'),
                    fieldTitle       : QUILocale.get('quiqqer/system', 'title'),
                    fieldWorkingTitle: QUILocale.get(lg, 'workingTitle'),
                    fieldType        : QUILocale.get(lg, 'fieldtype'),
                    fieldOptions     : QUILocale.get(lg, 'fieldoptions'),
                    fieldPriority    : QUILocale.get(lg, 'priority'),
                    fieldPrefix      : QUILocale.get(lg, 'prefix'),
                    fieldSuffix      : QUILocale.get(lg, 'suffix'),
                    fieldSearchtype  : QUILocale.get(lg, 'searchtype'),
                    fieldRequired    : QUILocale.get(lg, 'requiredField'),
                    fieldRequiredDesc: QUILocale.get(lg, 'requiredFieldDesc'),
                    fieldSystem      : QUILocale.get(lg, 'systemField'),
                    fieldSystemDesc  : QUILocale.get(lg, 'systemFieldDesc'),
                    fieldStandard    : QUILocale.get(lg, 'standardField'),
                    fieldStandardDesc: QUILocale.get(lg, 'standardFieldDesc'),
                    fieldPublic      : QUILocale.get(lg, 'publicField'),
                    fieldPublicDesc  : QUILocale.get(lg, 'publicFieldDesc')
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
                'group': 'quiqqer/products',
                'var'  : 'products.field.' + id + '.title'
            }).inject(Elm.getElement('.field-title'));

            this.$WorkingTitle = new Translation({
                'group': 'quiqqer/products',
                'var'  : 'products.field.' + id + '.workingtitle'
            }).inject(Elm.getElement('.field-workingtitle'));

            Promise.all([
                Fields.getChild(id),
                Fields.getFieldTypes(),
                Fields.getFieldTypeSettings(),
                Fields.getSearchTypesForField(id)
            ]).then(function (result) {
                var i, len, settings;

                var fieldTypes      = result[1],
                    fieldData       = result[0],
                    fieldSettings   = result[2],
                    searchTypes     = result[3],
                    FieldTypes      = Elm.getElement('[name="type"]'),
                    FieldOptions    = Elm.getElement('[name="options"]'),
                    FieldPriority   = Elm.getElement('[name="priority"]'),
                    FieldPrefix     = Elm.getElement('[name="prefix"]'),
                    FieldSuffix     = Elm.getElement('[name="suffix"]'),
                    FieldSearchType = Elm.getElement('[name="search_type"]'),
                    FieldRequired   = Elm.getElement('[name="requiredField"]'),
                    FieldSystem     = Elm.getElement('[name="systemField"]'),
                    FieldPublic     = Elm.getElement('[name="publicField"]'),
                    FieldStandard   = Elm.getElement('[name="standardField"]');

                for (i = 0, len = fieldTypes.length; i < len; i++) {
                    settings = '';

                    if (fieldTypes[i] in fieldSettings) {
                        settings = fieldSettings[fieldTypes[i]];
                    }

                    new Element('option', {
                        html           : QUILocale.get(lg, 'fieldtype.' + fieldTypes[i]),
                        value          : fieldTypes[i],
                        'data-settings': settings
                    }).inject(FieldTypes);
                }

                if (!searchTypes.length) {
                    new Element('span', {
                        'class': 'field-container-field',
                        html   : QUILocale.get(lg, 'fieldtype.not.searchable')
                    }).replaces(FieldSearchType);
                } else {
                    for (i = 0, len = searchTypes.length; i < len; i++) {
                        new Element('option', {
                            html : QUILocale.get(lg, 'searchtype.' + searchTypes[i] + '.title'),
                            value: searchTypes[i]
                        }).inject(FieldSearchType);
                    }
                }

                // options
                var options = fieldData.options;

                if (typeOf(options) != 'string') {
                    options = JSON.encode(options);
                }

                // set data to the form
                FieldTypes.value      = fieldData.type;
                FieldOptions.value    = options;
                FieldPriority.value   = fieldData.priority;
                FieldPrefix.value     = fieldData.prefix;
                FieldSuffix.value     = fieldData.suffix;
                FieldSearchType.value = fieldData.search_type;

                FieldRequired.checked = fieldData.isRequired;
                FieldSystem.checked   = fieldData.isSystem;
                FieldStandard.checked = fieldData.standard;
                FieldPublic.checked   = fieldData.isPublic;

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

                self.fireEvent('loaded');
            }.bind(this));
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
                Fields.updateChild(fieldId, {
                    type         : Form.elements.type.value,
                    search_type  : search_type,
                    prefix       : Form.elements.prefix.value,
                    suffix       : Form.elements.suffix.value,
                    priority     : Form.elements.priority.value,
                    standardField: Form.elements.standardField.checked ? 1 : 0,
                    requiredField: Form.elements.requiredField.checked ? 1 : 0,
                    publicField  : Form.elements.publicField.checked ? 1 : 0,
                    options      : Form.elements.options.value
                }).then(function () {
                    return self.$Translation.save();
                }).then(function () {
                    return self.$WorkingTitle.save();
                }).then(resolve()).catch(reject);
            });
        },

        /**
         * Load the extra settings from a field type
         *
         * @param {HTMLSelectElement} FieldTypes
         */
        $loadSettings: function (FieldTypes) {
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
