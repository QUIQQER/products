/**
 * Add to Watchlist button
 *
 * @module package/quiqqer/products/bin/controls/watchlist/ButtonAdd
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/watchlist/ButtonAdd', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/watchlist/ButtonAdd',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$pid = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var Elm = this.getElm(),
                pid = Elm.get('data-pid');

            if (!pid || pid === '') {
                return;
            }

            this.$pid = pid;

            Elm.addEvent('click', function() {

            });

            Elm.disabled = false;
        },

        /**
         * event: on inject
         */
        $onInject: function () {

        }
    });
});
