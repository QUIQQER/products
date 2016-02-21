/**
 * Watchlist button
 *
 * @module package/quiqqer/products/bin/controls/watchlist/Button
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/controls/watchlist/Window
 */
define('package/quiqqer/products/bin/controls/watchlist/Button', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/watchlist/Window'

], function (QUI, QUIControl, WatchlistWindow) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/watchlist/Button',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Icon = null;
            this.$Text = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            var Elm = this.getElm();

            this.$Icon = Elm.getElement('.qui-products-watchlist-button-icon');
            this.$Text = Elm.getElement('.qui-products-watchlist-button-text');

            Elm.addEvent('click', function () {
                new WatchlistWindow().open();
            });

            this.$Icon.removeClass('fa-spinner');
            this.$Icon.removeClass('fa-spin');
            this.$Icon.addClass('fa-list-alt');
        }
    });
});

