/**
 * @module package/quiqqer/products/bin/controls/fields/types/CheckboxInput
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/fields/types/CheckboxInput', [

    'qui/QUI',
    'qui/controls/Control',

    'css!package/quiqqer/products/bin/controls/fields/types/CheckboxInput.css'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/CheckboxInput',

        Binds: [
            '$onImport',
            '$setValue',
            'getValue',
            'enable',
            'disable'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input      = null;
            this.$Checkbox   = null;
            this.$ValueInput = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            var Container = new Element('div', {
                'class': 'quiqqer-products-field-checkboxinput-container field-container-field'
            }).inject(this.$Input, 'before');

            this.$Checkbox = new Element('input', {
                'class': 'quiqqer-products-field-checkboxinput-checkbox',
                type   : 'checkbox'
            }).inject(Container);

            this.$ValueInput = new Element('input', {
                'class': 'quiqqer-products-field-checkboxinput-value',
                type   : 'text'
            }).inject(Container);

            this.$Checkbox.addEvent('change', this.$setValue);
            this.$ValueInput.addEvent('change', this.$setValue);
        },

        /**
         * Set field value to internal input
         */
        $setValue: function () {
            this.$Input.value = JSON.encode({
                checked: this.$Checkbox.checked,
                value  : this.$ValueInput.value.trim()
            });
        },

        /**
         * Set data to field control
         *
         * @param {String} value
         */
        setData: function (value) {
            if (!value) {
                return;
            }

            value = JSON.decode(value);

            this.$Checkbox.checked = value.checked;
            this.$ValueInput.value = value.value;
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return JSON.encode({
                checked: this.$Checkbox.checked,
                value  : this.$ValueInput.value.trim()
            });
        },

        /**
         * Enable control
         */
        enable: function () {
            this.$Checkbox.disabled   = false;
            this.$ValueInput.disabled = false;
        },

        /**
         * Disable control
         */
        disable: function () {
            this.$Checkbox.disabled   = true;
            this.$ValueInput.disabled = true;
        }
    });
});
