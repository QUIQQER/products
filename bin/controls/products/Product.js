/**
 * Product management
 *
 * @module package/quiqqer/products/bin/controls/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require Locale
 */
define('package/quiqqer/products/bin/controls/products/Product', [

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
        Type   : 'package/quiqqer/products/bin/controls/products/Product',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        options: {
            productId: false
        },


        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title')
            });

            this.parent(options);

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

            var self    = this,
                Content = this.getContent();


        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        }
    });
});
