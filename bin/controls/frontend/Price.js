/**
 * Price display
 *
 * @module package/quiqqer/products/bin/controls/Price
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/Price', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {

    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/Price',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        options: {
            price: 0 // float
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

        },

        /**
         * event : on inject
         */
        $onInject: function () {

        }
    });
});
