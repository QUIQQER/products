/**
 * Field select window
 * User can select a field
 *
 * @module package/quiqqer/products/bin/controls/fields/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require Locale
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/classes/Fields
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
            'refresh',
            'submit'
        ],

        options: {
            maxHeight      : 600,
            maxWidth       : 800,
            multiple       : false,
            title          : QUILocale.get(lg, 'fields.window.confirm.title'),
            icon           : 'fa fa-plus',
            fieldTypeFilter: false
        },

        initialize: function (options) {

            this.parent(options);

            this.$Grid            = null;
            this.$FieldTypeFilter = null;

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
                pagination       : true,
                multipleSelection: this.getAttribute('multiple'),
                buttons          : [{
                    text: QUILocale.get(lg, 'categories.window.fieldtype.filter'),
                    name: 'select'
                }],
                columnModel      : [{
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
                    header   : QUILocale.get(lg, 'workingTitle'),
                    dataIndex: 'workingtitle',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'fieldtype'),
                    dataIndex: 'typeText',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'priority'),
                    dataIndex: 'priority',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'prefix'),
                    dataIndex: 'prefix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'suffix'),
                    dataIndex: 'suffix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'searchtype'),
                    dataIndex: 'searchtype',
                    dataType : 'text',
                    width    : 200
                }, {
                    dataIndex: 'type',
                    dataType : 'hidden'
                }]
            });

            this.$Grid.addEvents({
                onRefresh : this.refresh,
                onDblClick: this.submit
            });

            this.$FieldTypeFilter = self.$Grid.getButtons().filter(function (Btn) {
                return Btn.getAttribute('name') == 'select';
            })[0];

            this.$FieldTypeFilter.addEvent('change', function (Btn, ContextItem) {
                var value = ContextItem.getAttribute('value');

                if (value === '') {
                    self.$FieldTypeFilter.setAttribute(
                        'text',
                        QUILocale.get(lg, 'categories.window.fieldtype.filter')
                    );
                } else {
                    self.$FieldTypeFilter.setAttribute(
                        'text',
                        QUILocale.get(lg, 'fieldtype.' + value)
                    );
                }

                self.setAttribute('fieldTypeFilter', value);
                self.refresh();
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

            return Promise.all([
                Fields.getFieldTypes(),
                Fields.getList({
                    perPage: this.$Grid.options.perPage,
                    page   : this.$Grid.options.page,
                    type   : this.getAttribute('fieldTypeFilter')
                })
            ]).then(function (result) {
                var i, len;
                var fieldTypes = result[0],
                    gridData   = result[1];

                fieldTypes.sort(function (a, b) {
                    var aText = QUILocale.get(lg, 'fieldtype.' + a);
                    var bText = QUILocale.get(lg, 'fieldtype.' + b);

                    if (aText > bText) {
                        return 1;
                    }
                    if (aText < bText) {
                        return -1;
                    }

                    return 0;
                });

                self.$FieldTypeFilter.getContextMenu(function (Menu) {
                    Menu.setAttribute('maxHeight', 300);
                    Menu.clear();
                });

                self.$FieldTypeFilter.appendChild({
                    text : QUILocale.get(lg, 'categories.window.fieldtype.filter.showAll'),
                    value: ''
                });

                for (i = 0, len = fieldTypes.length; i < len; i++) {
                    self.$FieldTypeFilter.appendChild({
                        text : QUILocale.get(lg, 'fieldtype.' + fieldTypes[i]),
                        value: fieldTypes[i]
                    });
                }

                for (i = 0, len = gridData.data.length; i < len; i++) {
                    gridData.data[i].typeText = QUILocale.get(
                        lg,
                        'fieldtype.' + gridData.data[i].type
                    );
                }

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
