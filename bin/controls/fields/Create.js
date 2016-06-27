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
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Create',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUILocale, Mustache, InputMultiLang, Handler, Translation, template) {
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

            this.$Translation  = null;
            this.$WorkingTitle = null;

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
                    contentText        : '<div class="field-create-header">' +
                                         QUILocale.get(lg, 'control.field.create.content') +
                                         '</div>',
                    tableHeader        : QUILocale.get(lg, 'control.field.create.header'),
                    fieldTitle         : QUILocale.get('quiqqer/system', 'title'),
                    fieldWorkingTitle  : QUILocale.get(lg, 'workingTitle'),
                    fieldType          : QUILocale.get(lg, 'fieldtype'),
                    fieldPriority      : QUILocale.get(lg, 'priority'),
                    fieldPrefix        : QUILocale.get(lg, 'prefix'),
                    fieldSuffix        : QUILocale.get(lg, 'suffix'),
                    fieldSearchtype    : QUILocale.get(lg, 'searchtype'),
                    fieldSearchtypeInfo: QUILocale.get(lg, 'fieldSearchtypeInfo'),
                    fieldRequired      : QUILocale.get(lg, 'requiredField'),
                    fieldRequiredDesc  : QUILocale.get(lg, 'requiredFieldDesc'),
                    fieldSystem        : QUILocale.get(lg, 'systemField'),
                    fieldSystemDesc    : QUILocale.get(lg, 'systemFieldDesc'),
                    fieldStandard      : QUILocale.get(lg, 'standardField'),
                    fieldStandardDesc  : QUILocale.get(lg, 'standardFieldDesc'),
                    fieldPublic        : QUILocale.get(lg, 'publicField'),
                    fieldPublicDesc    : QUILocale.get(lg, 'publicFieldDesc')
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

            this.$WorkingTitle = new Translation({
                group: 'quiqqer/products'
            }).inject(Elm.getElement('.field-workingtitle'));

            this.$Prefix = new InputMultiLang().imports(
                Elm.getElement('[name="prefix"]')
            );

            this.$Suffix = new InputMultiLang().imports(
                Elm.getElement('[name="suffix"]')
            );

            Fields.getFieldTypes().then(function (fieldTypes) {

                var FieldTypes = Elm.getElement('[name="type"]');

                fieldTypes.sort(function (a, b) {
                    var aField = QUILocale.get(a.locale[0], a.locale[1]);
                    var bField = QUILocale.get(b.locale[0], b.locale[1]);

                    if (aField == bField) {
                        return 0;
                    }

                    return aField < bField ? -1 : 1;
                });

                for (var i = 0, len = fieldTypes.length; i < len; i++) {
                    new Element('option', {
                        html : QUILocale.get(fieldTypes[i].locale[0], fieldTypes[i].locale[1]),
                        value: fieldTypes[i].name
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

                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('showMessages', false);

                }).then(function () {
                    return Fields.createChild({
                        type         : Form.elements.type.value,
                        search_type  : '',
                        prefix       : Form.elements.prefix.value,
                        suffix       : Form.elements.suffix.value,
                        priority     : Form.elements.priority.value,
                        standardField: Form.elements.standardField.checked ? 1 : 0,
                        systemField  : 0,
                        publicField  : Form.elements.publicField.checked ? 1 : 0,
                        requiredField: Form.elements.requiredField.checked ? 1 : 0
                    });

                }).then(function (data) {
                    self.$Translation.setAttribute(
                        'var',
                        'products.field.' + data.id + '.title'
                    );

                    self.$WorkingTitle.setAttribute(
                        'var',
                        'products.field.' + data.id + '.workingtitle'
                    );

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
        }
    });
});
