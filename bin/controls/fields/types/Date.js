define('package/quiqqer/products/bin/controls/fields/types/Date', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/Date',

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
         * event: on import
         */
        $onImport: function () {
            const Elm = this.getElm();
            const value = Elm.value;

            // is numeric = unix timestamp
            if ((value - 0) == value && ('' + value).trim().length > 0) {
                const D = new Date(value * 1000);

                const day = ("0" + D.getDate()).slice(-2);
                const month = ("0" + (D.getMonth() + 1)).slice(-2);

                Elm.value = D.getFullYear() + "-" + (month) + "-" + (day);
            }

            Elm.addClass('field-container-field');
            Elm.type = 'date';
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
