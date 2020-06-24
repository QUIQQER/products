/**
 * @module package/quiqqer/products/bin/controls/products/settings/OwnFieldsSettings
 */
define('package/quiqqer/products/bin/controls/products/settings/OwnFieldsSettings', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/settings/OwnFieldsSettings',

        Binds: [
            '$onImport'
        ],

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
            var self  = this;
            var Table = this.$Elm.getParent('table');

            var AvailableSorting = Table.getElement('[name="quiqqer.products.settings.availableSorting"]');

            var Row = AvailableSorting.getParent('tr');

            if (!this.$Elm.checked) {
                Row.setStyle('display', 'none');
            }

            this.$Elm.addEvent('change', function () {
                if (!self.$Elm.checked) {
                    Row.setStyle('display', 'none');
                    return;
                }

                Row.setStyle('display', null);

                var SortingInstance = QUI.Controls.getById(AvailableSorting.get('data-quiid'));

                if (SortingInstance) {
                    SortingInstance.resize();
                }
            });
        }
    });
});
