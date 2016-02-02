/**
 * @module package/quiqqer/products/bin/controls/fields/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 */
define('package/quiqqer/products/bin/controls/fields/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/classes/Fields'

], function (QUI, QUIConfirm, QUILocale, Grid, Handler) {
    "use strict";

    var lg     = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/fields/Window',

        Binds: [
            '$onOpen',
            '$onResize',
            'refresh'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 800
        },

        initialize: function (options) {

            this.parent(options);

            this.setAttributes({
                title: QUILocale.get(lg, 'fields.window.confirm.title'),
                icon : 'fa fa-plus'
            });

            this.$Grid = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            this.Loader.show();

            var self    = this,
                Content = this.getContent();

            Content.set({
                html  : '',
                styles: {
                    opacity: 0
                }
            });

            var Container = new Element('div').inject(Content);

            this.$Grid = new Grid(Container, {
                pagination : true,
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : 'Feld-Typ',
                    dataIndex: 'type',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : 'Priorität',
                    dataIndex: 'priority',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Präfix',
                    dataIndex: 'prefix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Suffix',
                    dataIndex: 'suffix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Such-Typ',
                    dataIndex: 'searchtype',
                    dataType : 'text',
                    width    : 200
                }]
            });

            this.$Grid.addEvents({
                onRefresh: this.refresh
            });

            this.refresh().then(function () {
                return self.$onResize();

            }).then(function () {
                self.Loader.hide();

                moofx(Content).animate({
                    opacity: 1
                });
            });
        },

        /**
         * event : on resize
         *
         * @return {Promise}
         */
        $onResize: function () {
            var self = this;

            return new Promise(function (resolve) {

                if (!self.$Grid) {
                    return resolve();
                }

                var Content = self.getContent(),
                    size    = Content.getSize();

                Promise.all([
                    self.$Grid.setHeight(size.y - 40),
                    self.$Grid.setWidth(size.x - 40)
                ]).then(resolve);
            });
        },

        /**
         * Refresh the table
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            return Fields.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page
            }).then(function (gridData) {
                self.$Grid.setData(gridData);
            });
        },

        /**
         * submission of the popup
         */
        submit: function () {

            if (!this.$Grid) {
                return;
            }

            var selected = this.$Grid.getSelectedData();

            if (!selected.length) {
                return;
            }

            this.fireEvent('submit', [this, selected[0].id]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
