/**
 * @module package/quiqqer/products/bin/controls/products/permissions/Permission
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require controls/Control
 */
define('package/quiqqer/products/bin/controls/products/permissions/Permission', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : '',

        options: {
            permission: ''
        },

        initialize: function (options) {
            this.parent(options);
        },

        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-permissions'
            });


            return this.$Elm;
        }
    });
});
