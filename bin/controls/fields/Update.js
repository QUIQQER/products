/**
 * Category sitemap
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
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUIFormUtils, QUILocale, Mustache, Handler, Translation, template) {
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

            this.$Translation = null;

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
                    fieldType        : QUILocale.get(lg, 'fieldtype'),
                    fieldPriority    : QUILocale.get(lg, 'priority'),
                    fieldPrefix      : QUILocale.get(lg, 'prefix'),
                    fieldSuffix      : QUILocale.get(lg, 'suffix'),
                    fieldSearchtype  : QUILocale.get(lg, 'searchtype'),
                    fieldRequired    : QUILocale.get(lg, 'requiredField'),
                    fieldRequiredDesc: QUILocale.get(lg, 'requiredFieldDesc'),
                    fieldSystem      : QUILocale.get(lg, 'systemField'),
                    fieldSystemDesc  : QUILocale.get(lg, 'systemFieldDesc'),
                    fieldStandard    : QUILocale.get(lg, 'standardField'),
                    fieldStandardDesc: QUILocale.get(lg, 'standardFieldDesc')
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

            Promise.all([
                Fields.getChild(id),
                Fields.getFieldTypes(),
                Fields.getFieldTypeSettings()
            ]).then(function (result) {
                var i, len, settings;

                var fieldTypes      = result[1],
                    fieldData       = result[0],
                    fieldSettings   = result[2],
                    FieldTypes      = Elm.getElement('[name="type"]'),
                    FieldPriority   = Elm.getElement('[name="priority"]'),
                    FieldPrefix     = Elm.getElement('[name="prefix"]'),
                    FieldSuffix     = Elm.getElement('[name="suffix"]'),
                    FieldSearchType = Elm.getElement('[name="search_type"]'),
                    FieldRequired   = Elm.getElement('[name="requiredField"]'),
                    FieldSystem     = Elm.getElement('[name="systemField"]'),
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

                // set data to the form
                FieldTypes.value      = fieldData.type;
                FieldPriority.value   = fieldData.priority;
                FieldPrefix.value     = fieldData.prefix;
                FieldSuffix.value     = fieldData.suffix;
                FieldSearchType.value = fieldData.search_type;

                FieldRequired.checked = fieldData.isRequired;
                FieldSystem.checked   = fieldData.isSystem;
                FieldStandard.checked = fieldData.standard;

                var loadSettings = function () {
                    self.$loadSettings(this);
                }.bind(FieldTypes);

                FieldTypes.addEvent('change', loadSettings);

                loadSettings();

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

                var Form    = Elm.getElement('form'),
                    fieldId = self.getAttribute('fieldId');

                Fields.updateChild(fieldId, {
                    type         : Form.elements.type.value,
                    search_type  : Form.elements.search_type.value,
                    prefix       : Form.elements.prefix.value,
                    suffix       : Form.elements.suffix.value,
                    priority     : Form.elements.priority.value,
                    standardField: Form.elements.standardField.checked ? 1 : 0,
                    requiredField: Form.elements.requiredField.checked ? 1 : 0
                }).then(function () {
                    return self.$Translation.save();
                }).then(resolve()).catch(reject);
            });
        },

        /**
         * Load the extra settings from a field type
         *
         * @param {HTMLSelectElement} FieldTypes
         */
        $loadSettings: function (FieldTypes) {
            var Option   = FieldTypes.getElement('[value="' + FieldTypes.value + '"]'),
                settings = Option.get('data-settings');

            var TableNode = FieldTypes.getParent('table'),
                Cell      = TableNode.getElement('.extra-settings');

            FieldTypes.disabled = true;

            if (settings === '') {
                var Container = Cell.getFirst('div');

                if (!Container) {
                    FieldTypes.disabled = false;
                    FieldTypes.focus();
                    return;
                }

                moofx([Container, Cell]).animate({
                    height : 0,
                    opacity: 0,
                    margin : 0,
                    padding: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Cell.set('html', '');

                        Cell.setStyles({
                            display: 'none',
                            padding: null
                        });

                        FieldTypes.disabled = false;
                        FieldTypes.focus();
                    }
                });

                return;
            }

            Cell.set({
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    display: null
                }
            });

            require([settings], function (Control) {
                moofx(Cell).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Cell.set({
                            html  : '',
                            styles: {
                                height: Cell.getSize().y
                            }
                        });

                        var Container = new Element('div', {
                            styles: {
                                'float' : 'left',
                                height  : 0,
                                overflow: 'hidden',
                                opacity : 0,
                                width   : '100%'
                            }
                        }).inject(Cell);

                        Cell.setStyle('opacity', 1);

                        new Control().inject(Container);

                        moofx(Container).animate({
                            height : Container.getScrollSize().y,
                            opacity: 1
                        }, {
                            duration: 200,
                            callback: function () {
                                FieldTypes.disabled = false;
                                FieldTypes.focus();
                            }
                        });
                    }
                });

            }, function () {
                Cell.set({
                    html  : '',
                    styles: {
                        display: 'none'
                    }
                });

                FieldTypes.disabled = false;
            });

        }
    });
});
