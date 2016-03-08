/**
 * Watchlist
 *
 * @module package/quiqqer/products/bin/classes/Watchlist
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 *
 * @event onRefresh
 */
define('package/quiqqer/products/bin/classes/Watchlist', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Watchlist',

        initialize: function () {
            this.$products = {};
        },

        /**
         *
         * @param {Number} productId - ID of the product
         * @param {Number} [count]
         * @returns {Promise}
         */
        addProduct: function (productId, count) {
            count = parseInt(count) || 1;

            if (!count) {
                count = 1;
            }

            return new Promise(function (resolve, reject) {
                (function () {
                    resolve();
                }).delay(1000);
            });
        }
    });
});
