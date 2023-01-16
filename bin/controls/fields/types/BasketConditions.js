/**
 * @module package/quiqqer/products/bin/controls/fields/types/BasketConditions
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/BasketConditions', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/BasketConditions',

        Binds: [
            '$onImport',
            '$setValue',
            'getValue',
            'setData',
            'enable',
            'disable'
        ],

        options: {
            value: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Select = null;
            this.$Input = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        $onImport: function () {
            this.$Input = this.getElm();

            // Value Select
            this.$Select = new Element('select', {
                'class': 'field-container-field'
            }).inject(this.$Input, 'after');

            let value = this.$Input.value;
            let statuses = [
                'type_1',
                'type_2',
                'type_3',
                'type_4',
                'type_5',
                'type_6'
            ];

            for (let i = 0, len = statuses.length; i < len; i++) {
                new Element('option', {
                    value: statuses[i].replace('type_', ''),
                    html : QUILocale.get(lg, 'controls.basket.conditions.option.' + statuses[i])
                }).inject(this.$Select);

                if (statuses[i] === value) {
                    this.$Select.value = value;
                }
            }

            (() => {
                if (this.$Select.value === '' || this.$Select.value === 'type_1') {
                    this.$Select.value = '1';
                    this.$setValue();
                }
            }).delay(500);

            this.$Select.addEvent('change', this.$setValue);
        },

        /**
         * Set field value to input
         */
        $setValue: function () {
            this.$Input.value = this.$Select.value;
        },

        /**
         * Set value to control
         *
         * @param {String} val
         */
        setData: function (val) {
            this.$Select.value = val;
            this.$setValue();
        },

        /**
         * Return the current value
         *
         * @returns {Object}
         */
        getValue: function () {
            return this.$Select.value;
        },

        /**
         * Enable control
         */
        enable: function () {
            this.$Select.disabled = false;
        },

        /**
         * Disable control
         */
        disable: function () {
            this.$Select.disabled = true;
        }
    });
});
