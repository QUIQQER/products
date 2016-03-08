/**
 * Offer button
 *
 * @module package/quiqqer/products/bin/controls/offer/Button
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/offer/Button', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {

    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/offer/Button',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {

        }
    });
});

