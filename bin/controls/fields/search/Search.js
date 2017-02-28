/**
 * @module package/quiqqer/products/bin/controls/fields/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Felder suche
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/Fields
 * @require Locale
 */
define('package/quiqqer/products/bin/controls/fields/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/controls/fields/FieldTypeSelect',
    'Locale'

], function (QUI, QUIControl, Grid, Fields, FieldTypeSelect, QUILocale) {
    "use strict";


    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/search/Search',

        Binds: [
            '$onInject',
            'refresh',
            'submit'
        ],

        options: {
            multiple       : false,
            fieldTypeFilter: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    height : '100%',
                    width  : '100%'
                }
            });

            var GridContainer = new Element('div', {
                styles: {
                    height: '100%',
                    width : '100%'
                }
            }).inject(this.$Elm);

            this.$Grid = new Grid(GridContainer, {
                pagination       : true,
                multipleSelection: this.getAttribute('multiple'),
                perPage          : 150,
                buttons          : [
                    new FieldTypeSelect({
                        events: {
                            onFilterChange: function (FTS, value) {
                                this.setAttribute('fieldTypeFilter', value);
                                this.refresh();
                            }.bind(this)
                        }
                    })
                ],
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
                    header   : QUILocale.get(lg, 'publicField'),
                    dataIndex: 'isPublic',
                    dataType : 'node',
                    width    : 60
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
                }, {
                    header   : QUILocale.get(lg, 'showInDetails'),
                    dataIndex: 'showInDetails',
                    dataType : 'node',
                    width    : 60
                }]
            });

            this.$Grid.addEvents({
                onRefresh : this.refresh,
                onDblClick: this.submit,
                onClick   : function () {
                    this.fireEvent('click', [this]);
                }.bind(this)
            });

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.resize();
        },

        /**
         * resize
         *
         * @return {Promise}
         */
        resize: function () {
            return new Promise(function (resolve) {
                var size = this.getElm().getSize();

                Promise.all([
                    this.$Grid.setHeight(size.y),
                    this.$Grid.setWidth(size.x),
                    this.refresh()
                ]).then(resolve);

            }.bind(this));
        },

        /**
         * submit the selected elements
         */
        submit: function () {
            var ids = this.getSelected();

            if (!ids.length) {
                return;
            }

            this.fireEvent('submit', [this, ids]);
        },

        /**
         * Return the selected ids
         *
         * @returns {Array}
         */
        getSelected: function () {
            return this.$Grid.getSelectedData().map(function (Entry) {
                return Entry.id;
            });
        },

        /**
         * refresh the table data
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            this.fireEvent('refreshBegin');

            return Fields.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page,
                type   : this.getAttribute('fieldTypeFilter')
            }).then(function (result) {
                var i, len;
                var gridData = result;

                for (i = 0, len = gridData.data.length; i < len; i++) {
                    gridData.data[i].typeText = QUILocale.get(
                        lg,
                        'fieldtype.' + gridData.data[i].type
                    );
                }


                // if no grid array, create a grid array
                if (!("data" in gridData)) {
                    gridData = {data: gridData};
                }

                var ElmOk = new Element('span', {
                    'class': 'fa fa-check'
                });

                var ElmFalse = new Element('span', {
                    'class': 'fa fa-remove'
                });

                gridData.data.each(function (value, key) {
                    if (value.isStandard) {
                        gridData.data[key].isStandard = ElmOk.clone();
                    } else {
                        gridData.data[key].isStandard = ElmFalse.clone();
                    }

                    if (value.isRequired) {
                        gridData.data[key].isRequired = ElmOk.clone();
                    } else {
                        gridData.data[key].isRequired = ElmFalse.clone();
                    }

                    if (value.isPublic) {
                        gridData.data[key].isPublic = ElmOk.clone();
                    } else {
                        gridData.data[key].isPublic = ElmFalse.clone();
                    }

                    if (value.showInDetails) {
                        gridData.data[key].showInDetails = ElmOk.clone();
                    } else {
                        gridData.data[key].showInDetails = ElmFalse.clone();
                    }

                    value.fieldtype = QUILocale.get(lg, 'fieldtype.' + value.type);
                });

                self.$Grid.setData(gridData);
                self.fireEvent('refresh');
            });
        }
    });
});