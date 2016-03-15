/**
 *
 * @module package/quiqqer/products/bin/controls/fields/search/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/Fields
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/fields/search/Window.css
 */
define('package/quiqqer/products/bin/controls/fields/search/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/search/Window.css'

], function (QUI, QUIControl, QUIConfirm, Grid, Fields, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/fields/search/Window',

        Binds: [
            '$onOpen',
            '$onResize',
            'tableRefresh'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 400,
            icon     : 'fa fa-file-text-o',
            title    : 'Feld-Auswahl',
            autoclose: false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/system', 'accept'),
                textimage: 'fa fa-search'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.$ButtonCancel = null;
            this.$ButtonSubmit = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on resize
         *
         * @return {Promise}
         */
        $onResize: function () {
            return new Promise(function (resolve) {

                var Content = this.getContent(),
                    size    = Content.getSize();

                Promise.all([
                    this.$Grid.setHeight(size.y - 40),
                    this.$Grid.setWidth(size.x - 40),
                    this.tableRefresh()
                ]).then(resolve);

            }.bind(this));
        },

        /**
         * refresh the table data
         *
         * @return {Promise}
         */
        tableRefresh: function () {
            var self = this;

            this.Loader.show();

            return Fields.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page
            }).then(function (data) {

                var ElmOk = new Element('span', {
                    'class': 'fa fa-check'
                });

                var ElmFalse = new Element('span', {
                    'class': 'fa fa-remove'
                });

                data.data.each(function (value, key) {
                    if (value.isStandard) {
                        data.data[key].isStandard = ElmOk.clone();
                    } else {
                        data.data[key].isStandard = ElmFalse.clone();
                    }

                    if (value.isRequired) {
                        data.data[key].isRequired = ElmOk.clone();
                    } else {
                        data.data[key].isRequired = ElmFalse.clone();
                    }

                    value.fieldtype = QUILocale.get(lg, 'fieldtype.' + value.type);
                });

                self.$Grid.setData(data);
                self.Loader.hide();
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win) {
            var self    = this,
                Content = Win.getContent();

            Content.set('html', '');

            var GridContainer = new Element('div', {
                styles: {
                    height: '100%',
                    width : '100%'
                }
            }).inject(Content);

            this.$Grid = new Grid(GridContainer, {
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
                    header   : QUILocale.get(lg, 'fieldtype'),
                    dataIndex: 'fieldtype',
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
                    header   : QUILocale.get(lg, 'standardField'),
                    dataIndex: 'isStandard',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get(lg, 'requiredField'),
                    dataIndex: 'isRequired',
                    dataType : 'node',
                    width    : 60
                }]
            });

            this.$Grid.addEvents({
                onRefresh : this.tableRefresh,
                onDblClick: function () {
                    self.submit();
                }
            });

            this.$onResize();
        },

        /**
         * Submit
         */
        submit: function () {
            var ids = this.$Grid.getSelectedData().map(function (Entry) {
                return Entry.id;
            });

            if (!ids.length) {
                return;
            }

            this.fireEvent('submit', [this, ids]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
