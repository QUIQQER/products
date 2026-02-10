define('package/quiqqer/products/bin/controls/products/settings/GlobalDefaultSorting', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/settings/GlobalDefaultSorting',

        Binds: [
            '$onImport',
            'reload'
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
            this.reload();

            const Table = this.getElm().getParent('table');
            const AvailableSorting = Table.getElement('[name="products.sortFields"]');

            if (AvailableSorting.get('data-quiid')) {
                QUI.Controls
                    .getById(AvailableSorting.get('data-quiid'))
                    .addEvent('change', this.reload);
            } else {
                AvailableSorting.addEvent('load', function () {
                    QUI.Controls
                        .getById(AvailableSorting.get('data-quiid'))
                        .addEvent('change', this.reload);
                }.bind(this));
            }
        },

        /**
         * Reload the list
         */
        reload: function () {
            const self = this;

            this.getElm().set('disabled', true);

            this.$getAvailableSorting().then(function (Instance) {
                return Instance.getActiveFields();
            }).then(function (fields) {
                self.getElm().innerHTML = '';

                for (let i = 0, len = fields.length; i < len; i++) {
                    new Element('option', {
                        value: fields[i].id + ' DESC',
                        html: fields[i].title + ' ' + QUILocale.get(lg, 'sortDESC')
                    }).inject(self.getElm());

                    new Element('option', {
                        value: fields[i].id + ' ASC',
                        html: fields[i].title + ' ' + QUILocale.get(lg, 'sortASC')
                    }).inject(self.getElm());
                }

                self.getElm().set('disabled', false);
            });
        },

        /**
         * @return {Promise}
         */
        $getAvailableSorting: function () {
            const self = this;

            return new Promise(function (resolve) {
                const Table = self.getElm().getParent('table');
                const AvailableSorting = Table.getElement('[name="products.sortFields"]');

                if (!AvailableSorting.get('data-quiid')) {
                    return new Promise(function (res) {
                        (function () {
                            self.$getAvailableSorting().then(res);
                        }).delay(100);
                    }).then(resolve);
                }

                const Instance = QUI.Controls.getById(AvailableSorting.get('data-quiid'));
                resolve(Instance);
            });
        }
    });
});
