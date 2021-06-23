/**
 * @module package/quiqqer/products/bin/controls/fields/types/UserInput
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/UserInput', [

    'Locale',
    'qui/controls/Control'

], function (QUILocale, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/UserInput',

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
            var Elm = this.getElm();

            Elm.type = 'hidden';

            new Element('span', {
                'class': 'field-container-field',
                html   : QUILocale.get('quiqqer/products', 'controls.UserInput.info')
            }).inject(Elm, 'after');
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return null;
        }
    });
});
