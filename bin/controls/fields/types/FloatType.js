/**
 * Komma zahl feld
 */
define('package/quiqqer/products/bin/controls/fields/types/FloatType', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/FloatType',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const Elm = this.getElm();

            Elm.addClass('field-container-field');

            Elm.type = 'number';
            Elm.step = 'any';
            Elm.placeholder = '10.9999';
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.getElm().value;
        }
    });
});
