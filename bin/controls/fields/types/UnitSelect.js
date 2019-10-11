/**
 * @module package/quiqqer/products/bin/controls/fields/types/UnitSelect
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/fields/types/UnitSelect', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'package/quiqqer/products/bin/Fields',

], function (QUI, QUIControl, QUILocale, ProductFields) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/UnitSelect',

        Binds: [
            '$onImport',
            '$getOptions',
            '$onSelectChange',
            '$setValue',
            'enable',
            'disable'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Select        = null;
            this.$fieldId       = null;
            this.$QuantityInput = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm();

            this.$fieldId = Elm.get('name').split('-')[1];

            // loader
            var Loader = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    lineHeight: 30,
                    textAlign : 'center',
                    width     : 50
                }
            }).inject(Elm, 'after');

            // Value Select
            this.$Select = new Element('select', {
                'class': 'field-container-field'
            }).inject(Elm, 'after');

            // Quantity input
            this.$QuantityInput = new Element('input', {
                type  : 'number',
                events: {
                    change: this.$setValue
                }
            }).inject(this.$Select, 'before');

            ProductFields.getFieldOptions(this.$fieldId).then(function (Options) {
                if (!Options) {
                    Elm.disabled = true;
                    return;
                }

                var i, len, label;

                var selectValue  = false,
                    defaultValue = false;

                if (Elm.value) {
                    var value = JSON.decode(Elm.value);

                    selectValue = value.id;

                    if (value.quantity) {
                        self.$QuantityInput.value = value.quantity;
                    }
                }

                for (var id in Options.entries) {
                    if (!Options.entries.hasOwnProperty(id)) {
                        continue;
                    }

                    var Option = Options.entries[id];
                    var Title  = Option.title;

                    if (USER.lang in Title) {
                        label = Title[USER.lang];
                    } else {
                        label = 'Option ' + id;
                    }

                    new Element('option', {
                        html        : label,
                        value       : id,
                        'data-input': Option.quantityInput ? 1 : 0
                    }).inject(self.$Select);

                    if (Option.default) {
                        defaultValue = id;
                    }
                }

                if (selectValue) {
                    self.$Select.value = selectValue;
                } else if (defaultValue) {
                    self.$Select.value = defaultValue;
                }

                var SelectedOption = self.$Select.getElement('option[value="' + self.$Select.value + '"]');

                if (!parseInt(SelectedOption.get('data-input'))) {
                    self.$QuantityInput.setStyle('display', 'none');
                }

                self.$Select.addEvent('change', self.$onSelectChange);

                Loader.set(
                    'html',
                    '<span class="fa fa-tag"></span>'
                );
            });
        },

        /**
         * Event: on value select
         *
         * @param {DOMEvent} event
         */
        $onSelectChange: function (event) {
            var Option = this.$Select.getElement('option[value="' + event.target.value + '"]');

            this.$setValue();

            if (!parseInt(Option.get('data-input'))) {
                this.$QuantityInput.setStyle('display', 'none');
                return;
            }

            this.$QuantityInput.setStyle('display', '');
            this.$QuantityInput.focus();
        },

        /**
         * Set field value to input
         */
        $setValue: function () {
            this.getElm().value = JSON.encode({
                id      : this.$Select.value,
                quantity: this.$QuantityInput ? this.$QuantityInput.value.trim() : false
            });
        },

        /**
         * Return the current value
         *
         * @returns {Object}
         */
        getValue: function () {
            return {
                id      : this.$Select.value,
                quantity: this.$QuantityInput ? this.$QuantityInput.value.trim() : false
            };
        },

        /**
         * Enable control
         */
        enable: function() {
            this.$QuantityInput.disabled = false;
            this.$Select.disabled = false;
        },

        /**
         * Disable control
         */
        disable: function() {
            this.$QuantityInput.disabled = true;
            this.$Select.disabled = true;
        }
    });
});
