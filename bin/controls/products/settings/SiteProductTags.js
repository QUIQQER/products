/**
 * @module package/quiqqer/products/bin/controls/products/settings/SiteProductTags
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/settings/SiteProductTags', [

    'qui/QUI',
    'qui/controls/Control',
    'Packages'

], function (QUI, QUIControl, Packages) {
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
            var self = this,
                Elm  = this.getElm();

            var onError = function (err) {
                // package not exists
                console.error(err);

                var Row = Elm.getParent('.qui-xml-panel-row');

                if (Row) {
                    Row.setStyle('display', 'none');
                }
            };

            Packages.getPackage('quiqqer/productstags').then(function () {
                require(['package/quiqqer/tags/bin/tags/Select'], function () {
                    Elm.set('data-qui', 'package/quiqqer/tags/bin/tags/Select');
                    Elm.set('data-quiid', '');

                    QUI.parse(Elm.getParent()).then(function () {
                        var Site    = self.getAttribute('Site'),
                            Control = QUI.Controls.getById(Elm.get('data-quiid'));

                        Control.setProject(Site.getProject());
                    });
                }, onError);
            }).catch(onError);
        }

    });
});