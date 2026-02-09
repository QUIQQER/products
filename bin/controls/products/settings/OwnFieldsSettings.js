define('package/quiqqer/products/bin/controls/products/settings/OwnFieldsSettings', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/settings/OwnFieldsSettings',

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
            const self = this;
            const Table = this.$Elm.getParent('table');
            const AvailableSorting = Table.getElement('[name="quiqqer.products.settings.availableSorting"]');
            const Row = AvailableSorting.getParent('tr');

            if (!this.$Elm.checked) {
                Row.setStyle('display', 'none');
            }

            this.$Elm.addEvent('change', function () {
                if (!self.$Elm.checked) {
                    Row.setStyle('display', 'none');
                    return;
                }

                Row.setStyle('display', null);

                const SortingInstance = QUI.Controls.getById(AvailableSorting.get('data-quiid'));

                if (SortingInstance) {
                    SortingInstance.resize();
                    SortingInstance.refresh();
                }
            });
        }
    });
});
