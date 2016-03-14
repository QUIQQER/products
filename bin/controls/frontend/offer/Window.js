/**
 * @module package/quiqqer/products/bin/controls/frontend/offer/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Popup
 */
define('package/quiqqer/products/bin/controls/frontend/offer/Window', [

    'qui/QUI',
    'qui/controls/windows/Popup'

], function (QUI, QUIPopup) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'package/quiqqer/products/bin/controls/frontend/offer/Window',

        options: {
            maxHeight: 800,
            maxWidth : 1200
        },

        initialize: function (options) {
            this.parent(options);
        },

        $onOpen: function () {

        }
    });
});
