/**
 * @module package/quiqqer/products/bin/controls/products/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte
 */
define('package/quiqqer/products/bin/controls/products/search/Search', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

        },

        /**
         * event : on inject
         */
        $onInject: function () {

        }
    });
});
