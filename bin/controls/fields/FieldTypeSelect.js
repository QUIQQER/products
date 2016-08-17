/**
 * Feld-Type Auswahl
 *
 * @module package/quiqqer/products/bin/controls/fields/FieldTypeSelect
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/buttons/Button
 * @require package/quiqqer/products/bin/Fields
 * @require Locale
 *
 * @event onFilterChange [self, filter]
 */
define('package/quiqqer/products/bin/controls/fields/FieldTypeSelect', [

    'qui/QUI',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/Fields',
    'Locale'

], function (QUI, QUIButton, Fields, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIButton,
        Type   : 'package/quiqqer/products/bin/controls/fields/FieldTypeSelect',

        Binds: [
            '$onInject',
            '$onChange'
        ],

        initialize: function (options) {
            this.setAttributes({
                text        : QUILocale.get(lg, 'categories.window.fieldtype.filter'),
                textimage   : 'fa fa-filter',
                name        : 'select',
                showIcons   : false,
                dropDownIcon: false
            });

            this.$value = '';
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject,
                onChange: this.$onChange
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            Fields.getFieldTypes().then(function (fieldTypes) {

                fieldTypes.sort(function (a, b) {
                    var aText = QUILocale.get(a.locale[0], a.locale[1]);
                    var bText = QUILocale.get(b.locale[0], a.locale[1]);

                    if (aText > bText) {
                        return 1;
                    }

                    if (aText < bText) {
                        return -1;
                    }

                    return 0;
                });

                this.getContextMenu(function (Menu) {
                    Menu.setAttribute('maxHeight', 300);
                    Menu.setAttribute('showIcons', false);
                    Menu.clear();
                });

                this.appendChild({
                    text : QUILocale.get(lg, 'categories.window.fieldtype.filter.showAll'),
                    value: ''
                });

                for (var i = 0, len = fieldTypes.length; i < len; i++) {
                    this.appendChild({
                        text : QUILocale.get(fieldTypes[i].locale[0], fieldTypes[i].locale[1]),
                        value: fieldTypes[i].name
                    });
                }
                
            }.bind(this));
        },

        /**
         * event : on change
         *
         * @param {Object} self
         * @param {Object} ContextItem
         */
        $onChange: function (self, ContextItem) {
            var value = ContextItem.getAttribute('value');

            if (value === '') {
                this.setAttribute('text', QUILocale.get(lg, 'categories.window.fieldtype.filter'));
            } else {
                this.setAttribute('text', QUILocale.get(lg, 'fieldtype.' + value));
            }

            this.$value = value;

            this.fireEvent('filterChange', [this, value]);
        },

        /**
         * Return the select value
         *
         * @returns {string}
         */
        getValue: function () {
            return this.$value;
        }
    });
});