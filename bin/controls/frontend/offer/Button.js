/**
 * Offer button
 *
 * @module package/quiqqer/products/bin/controls/offer/Button
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/controls/frontend/offer/Window
 */
define('package/quiqqer/products/bin/controls/frontend/offer/Button', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/frontend/offer/Window'

], function (QUI, QUIControl, OfferWindow) {

    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/offer/Button',

        Binds: [
            '$onImport',
            '$openWindow'
        ],

        options: {
            productId: false
        },

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
            var Elm = this.getElm(),
                pid = Elm.get('data-pid');

            if (!pid || pid === '') {
                return;
            }

            this.setAttribute('productId', pid);

            Elm.addEvent('click', this.$openWindow);
            Elm.disabled = false;
        },

        /**
         * opens the offer window
         */
        $openWindow: function () {
            new OfferWindow({
                productId: this.getAttribute('productId')
            }).open();
        }
    });
});
