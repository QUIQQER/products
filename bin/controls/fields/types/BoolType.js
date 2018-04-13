/**
 * @module package/quiqqer/products/bin/controls/fields/types/BoolType
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/BoolType', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/BoolType',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$Input = Elm;

            this.$Input.type    = 'checkbox';
            this.$Input.checked = parseInt(this.$Input.value);

            this.$Input.addEvent('change', function () {
                this.value = this.checked ? 1 : 0;
            });

            this.$Elm = new Element('div', {
                'class': 'field-container-field'
            }).wraps(this.$Input);
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        }
    });
});
