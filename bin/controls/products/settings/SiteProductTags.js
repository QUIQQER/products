/**
 * @module package/quiqqer/products/bin/controls/products/settings/SiteProductTags
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/settings/SiteProductTags', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Products'

], function (QUI, QUIControl, Products) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/settings/SiteProductTags',

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            Products.getInstalledProductPackages().then(function (Packages) {
                if (!Packages['quiqqer/productstags']) {
                    var Row = Elm.getParent('tr');

                    if (Row) {
                        Row.setStyle('display', 'none');
                    }
                }
            });
        }
    });
});