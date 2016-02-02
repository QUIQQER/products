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
 * @require css!package/quiqqer/products/bin/controls/categories/Create.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/fields/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Create',

    'text!package/quiqqer/products/bin/controls/fields/Create.html',
    'css!package/quiqqer/products/bin/controls/fields/Create.css'

], function (QUI, QUIControl, QUILocale, Handler, Translation, template) {
    "use strict";

    var Fields = new Handler();

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
                html   : template
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

                var FieldTypes = Elm.getElement('[name="fieldtype"]');

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
                    type       : Form.elements.fieldtype.value,
                    search_type: Form.elements.searchtype.value,
                    prefix     : Form.elements.prefix.value,
                    suffix     : Form.elements.suffix.value,
                    priority   : Form.elements.priority.value
                }).then(function (data) {
                    console.warn(data);
                    self.$TitleTranslate.setAttribute(
                        'var',
                        'products.field.' + data.id + '.title'
                    );

                    this.$Translation.save().then(function () {
                        resolve();
                    }).catch(reject);
                });
            });
        }
    });
});
