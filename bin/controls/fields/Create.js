/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Locale
 * @require package/quiqqer/products/bin/classes/Fields
 * @require package/quiqqer/translator/bin/controls/Create
 * @require text!package/quiqqer/products/bin/controls/fields/Create.html
 * @require css!package/quiqqer/products/bin/controls/fields/Create.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/fields/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Create',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUILocale, Mustache, Handler, Translation, template) {
    "use strict";

    var lg     = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/Create',

        Binds: [
            '$onInject'
        ],

        options: {},

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
                    contentText      : '<div class="field-create-header">' +
                                       QUILocale.get(lg, 'control.field.create.content') +
                                       '</div>',
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
                Elm  = self.getElm();

            this.$Translation = new Translation({
                group: 'quiqqer/products'
            }).inject(Elm.getElement('.field-title'));

            Fields.getFieldTypes().then(function (fieldTypes) {

                var FieldTypes = Elm.getElement('[name="type"]');

                for (var i = 0, len = fieldTypes.length; i < len; i++) {
                    new Element('option', {
                        html : QUILocale.get('quiqqer/products', 'fieldtype.' + fieldTypes[i]),
                        value: fieldTypes[i]
                    }).inject(FieldTypes);
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
            var self = this,
                Elm  = self.getElm();

            return new Promise(function (resolve, reject) {

                if (!self.$Translation) {
                    return reject('Translation not found');
                }

                var Form = Elm.getElement('form');

                Fields.createChild({
                    type         : Form.elements.type.value,
                    search_type  : Form.elements.search_type.value,
                    prefix       : Form.elements.prefix.value,
                    suffix       : Form.elements.suffix.value,
                    priority     : Form.elements.priority.value,
                    standardField: Form.elements.standardField.checked ? 1 : 0,
                    systemField  : 0,
                    requiredField: Form.elements.requiredField.checked ? 1 : 0
                }).then(function (data) {

                    self.$Translation.setAttribute(
                        'var',
                        'products.field.' + data.id + '.title'
                    );

                    self.$Translation.save().then(function () {
                        resolve();
                    }).catch(reject);
                });
            });
        }
    });
});
