/**
 * @module package/quiqqer/products/bin/controls/fields/types/TimePeriod
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/fields/types/TimePeriod', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',

    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/TimePeriod.html',
    'css!package/quiqqer/products/bin/controls/fields/types/TimePeriod.css'

], function (QUI, QUIControl, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/TimePeriod',

        Binds: [
            '$onImport',
            '$getOptions',
            '$onSelectChange',
            '$setValue'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$UnitSelect = null;
            this.$fieldId    = null;
            this.$FromInput  = null;
            this.$ToInput    = null;
            this.$Content    = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Event: onImport
         */
        $onImport: function () {
            var self  = this,
                Elm   = this.getElm(),
                Value = false;

            this.$fieldId = Elm.get('name').split('-')[1];

            var lgPrefix = 'controls.fields.types.TimePeriod.template.';

            this.$Content = new Element('div', {
                'class': 'quiqqer-products-fields-types-timeperiod field-container-field',
                html   : Mustache.render(template, {
                    labelFrom      : QUILocale.get(lg, lgPrefix + 'labelFrom'),
                    labelTo        : QUILocale.get(lg, lgPrefix + 'labelTo'),
                    labelUnit      : QUILocale.get(lg, lgPrefix + 'labelUnit'),
                    labelUnitSecond: QUILocale.get(lg, lgPrefix + 'labelUnitSecond'),
                    labelUnitMinute: QUILocale.get(lg, lgPrefix + 'labelUnitMinute'),
                    labelUnitHour  : QUILocale.get(lg, lgPrefix + 'labelUnitHour'),
                    labelUnitDay   : QUILocale.get(lg, lgPrefix + 'labelUnitDay'),
                    labelUnitWeek  : QUILocale.get(lg, lgPrefix + 'labelUnitWeek'),
                    labelUnitMonth : QUILocale.get(lg, lgPrefix + 'labelUnitMonth'),
                    labelUnitYear  : QUILocale.get(lg, lgPrefix + 'labelUnitYear')
                })
            }).inject(Elm, 'after');

            // Value Select
            this.$UnitSelect = this.$Content.getElement('select');
            this.$UnitSelect.addEvent('change', this.$setValue);

            // From input
            this.$FromInput = this.$Content.getElement('input[name="from"]');
            this.$FromInput.addEvent('change', this.$setValue);

            // To Input
            this.$ToInput = this.$Content.getElement('input[name="to"]');
            this.$ToInput.addEvent('change', this.$setValue);

            (function () {
                if (!Elm.value) {
                    return;
                }

                Value = JSON.decode(Elm.value);

                self.$UnitSelect.value = Value.unit;
                self.$FromInput.value  = Value.from;
                self.$ToInput.value    = Value.to;
            }).delay(200);
        },

        /**
         * Set field value to input
         */
        $setValue: function () {
            this.getElm().value = JSON.encode({
                from: this.$FromInput.value.trim(),
                to  : this.$ToInput.value.trim(),
                unit: this.$UnitSelect.value
            });
        },

        /**
         * Return the current value
         *
         * @returns {Object}
         */
        getValue: function () {
            return {
                from: this.$FromInput.value.trim(),
                to  : this.$ToInput.value.trim(),
                unit: this.$UnitSelect.value
            };
        }
    });
});
