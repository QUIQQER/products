/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/classes/Categories
 *
 * @require css!package/quiqqer/products/bin/controls/categories/Update.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/fields/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Update.css'

], function (QUI, QUIControl, QUILocale, Handler, Translation, template) {
    "use strict";

    var Fields = new Handler();

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
                html   : template
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
                Fields.getFieldTypes()
            ]).then(function (result) {
                var fieldTypes      = result[1],
                    fieldData       = result[0],
                    FieldTypes      = Elm.getElement('[name="fieldtype"]'),
                    FieldPriority   = Elm.getElement('[name="priority"]'),
                    FieldPrefix     = Elm.getElement('[name="prefix"]'),
                    FieldSuffix     = Elm.getElement('[name="suffix"]'),
                    FieldSearchType = Elm.getElement('[name="searchtype"]');

                for (var i = 0, len = fieldTypes.length; i < len; i++) {
                    new Element('option', {
                        html : QUILocale.get('quiqqer/products', 'fieldtype.' + fieldTypes[i]),
                        value: fieldTypes[i]
                    }).inject(FieldTypes);
                }

                // set data to the form
                FieldTypes.value      = fieldData.type;
                FieldPriority.value   = fieldData.priority;
                FieldPrefix.value     = fieldData.prefix;
                FieldSuffix.value     = fieldData.suffix;
                FieldSearchType.value = fieldData.search_type;

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

                Fields.updateChild(
                    self.getAttribute('fieldId'),
                    {
                        type       : Form.elements.fieldtype.value,
                        search_type: Form.elements.searchtype.value,
                        prefix     : Form.elements.prefix.value,
                        suffix     : Form.elements.suffix.value,
                        priority   : Form.elements.priority.value
                    }
                ).then(function () {
                    return self.$Translation.save();
                }).then(resolve()).catch(reject);
            });
        }
    });
});
