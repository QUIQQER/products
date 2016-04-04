/**
 * @module package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 *
 * @event onChange [{Object} self, {Number} fieldId]
 */
define('package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/frontend/fields/Field'

], function (QUI, FieldControl) {
    "use strict";

    return new Class({
        Extends: FieldControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList',

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
            var self = this,
                Elm  = this.getElm();

            this.$fieldId = Elm.get('data-field').toInt();

            Elm.addEvent('change', function () {
                self.fireEvent('change', [self]);
            });

            Elm.disabled = false;
        },

        /**
         * Return the field value
         *
         * @returns {*|string}
         */
        getValue: function () {
            return this.getElm().value;
        }
    });
});
