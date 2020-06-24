/**
 * @module package/quiqqer/products/bin/controls/products/settings/DefaultSorting
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/settings/DefaultSorting', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale'

], function (QUI, QUIControl, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/settings/DefaultSorting',

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

            var Table      = this.getElm().getParent('table');
            var UseSorting = Table.getElement('[name="quiqqer.products.settings.useOwnSorting"]');

            UseSorting.addEvent('change', this.reload);
        },

        /**
         * Reload the list
         */
        reload: function () {
            var self = this;

            this.getElm().set('disabled', true);

            this.$getAvailableSorting().then(function (Instance) {
                return Instance.getActiveFields();
            }).then(function (fields) {
                self.getElm().innerHTML = '';

                // @todo zusatzfelder // zB date sorting

                for (var i = 0, len = fields.length; i < len; i++) {
                    new Element('option', {
                        value: 'F' + fields[i].id + ' DESC',
                        html : fields[i].title + ' ' + QUILocale.get(lg, 'sortDESC')
                    }).inject(self.getElm());

                    new Element('option', {
                        value: 'F' + fields[i].id + ' ASC',
                        html : fields[i].title + ' ' + QUILocale.get(lg, 'sortASC')
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
            var self = this;

            return new Promise(function (resolve) {
                var Table            = self.getElm().getParent('table');
                var AvailableSorting = Table.getElement('[name="quiqqer.products.settings.availableSorting"]');

                if (!AvailableSorting.get('data-quiid')) {
                    return new Promise(function (res) {
                        (function () {
                            self.$getAvailableSorting().then(res);
                        }).delay(100);
                    }).then(resolve);
                }

                var Instance = QUI.Controls.getById(AvailableSorting.get('data-quiid'));
                resolve(Instance);
            });
        },

        /**
         * @return {null|Object}
         */
        $getSite: function () {
            // is it in site?
            var PanelNode = this.getElm().getParent('.qui-panel');

            if (PanelNode) {
                var Panel = QUI.Controls.getById(PanelNode.get('data-quiid'));

                if (Panel.getType() === 'controls/projects/project/site/Panel') {
                    return Panel.getSite();
                }
            }

            return null;
        }
    });
});
