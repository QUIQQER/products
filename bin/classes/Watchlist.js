/**
 * Watchlist
 *
 * @module package/quiqqer/products/bin/classes/Watchlist
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
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

        },

        addProduct: function (pid, count) {
            return new Promise(function (resolve, reject) {

            });
        }
    });
});
