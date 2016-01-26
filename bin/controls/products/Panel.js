/**
 *
 */
define('package/quiqqer/products/bin/controls/products/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Panel',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.panel.title')
            });

            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        refresh: function () {
            this.parent();
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            var self = this;


        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        }
    });
});