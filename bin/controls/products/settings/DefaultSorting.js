define('package/quiqqer/products/bin/controls/products/settings/DefaultSorting', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/settings/DefaultSorting',

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
            const UseSorting = Table.getElement('[name="quiqqer.products.settings.useOwnSorting"]');
            const AvailableSorting = Table.getElement('[name="quiqqer.products.settings.availableSorting"]');

            UseSorting.addEvent('change', this.reload);

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

                if (self.$getSite()) {
                    self.getElm().value = self.$getSite().getAttribute('quiqqer.products.settings.defaultSorting');
                }

                self.getElm().set('disabled', null);
            });
        },

        /**
         * @return {Promise}
         */
        $getAvailableSorting: function () {
            const self = this;

            return new Promise(function (resolve) {
                const Table = self.getElm().getParent('table');
                const AvailableSorting = Table.getElement('[name="quiqqer.products.settings.availableSorting"]');

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
        },

        /**
         * @return {null|Object}
         */
        $getSite: function () {
            // is it in site?
            const PanelNode = this.getElm().getParent('.qui-panel');

            if (PanelNode) {
                const Panel = QUI.Controls.getById(PanelNode.get('data-quiid'));

                if (Panel.getType() === 'controls/projects/project/site/Panel') {
                    return Panel.getSite();
                }
            }

            return null;
        }
    });
});
