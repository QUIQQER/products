/**
 * Parent Klasse für ein field control
 *
 * @module package/quiqqer/products/bin/controls/frontend/fields/Field
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 *
 * @event onChange [{Object} self, {Number} fieldId]
 */
define('package/quiqqer/products/bin/controls/frontend/fields/Field', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/Field',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$fieldId = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         *
         * @returns {boolean}
         */
        isField: function () {
            return true;
        },

        /**
         * Return the field ID
         *
         * @returns {boolean|number}
         */
        getFieldId: function () {
            return this.$fieldId;
        },

        /**
         * Return the current field value
         *
         * @return {String}
         */
        getValue: function () {
            return '';
        }
    });
});
