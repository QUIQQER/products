/**
 * @module package/quiqqer/products/bin/controls/products/OverwritableFieldList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/OverwritableFieldList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUISwitch, Grid, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/OverwritableFieldList',

        Binds: [
            '$onInject',
            'refresh'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {Element}
         */
        create: function () {
            this.parent();

            this.$Elm = new Element('div', {
                'class'   : 'quiqqer-products-variant-overwritable-fields',
                id        : this.getId(),
                'data-qui': 'package/quiqqer/products/bin/controls/products/variants/OverwritableFieldList',
                styles    : {
                    height: '100%'
                }
            });


            var Container = new Element('div').inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                pagination : true,
                width      : Container.getSize().x,
                height     : Container.getSize().y,
                perPage    : 20,
                page       : 1,
                serverSort : true,
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'status'),
                    dataIndex: 'status',
                    dataType : 'QUI',
                    width    : 60,
                    sortable : false
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60,
                    sortable : true
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }, {
                    header   : QUILocale.get(lg, 'workingTitle'),
                    dataIndex: 'workingtitle',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }, {
                    header   : QUILocale.get(lg, 'fieldtype'),
                    dataIndex: 'fieldtype',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }]
            });

            this.$Grid.addEvents({
                onRefresh: this.refresh
            });

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            if (!this.$Grid) {
                return;
            }

            var size = this.$Elm.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Saves the overwritable fields to the product
         *
         * @return {Promise}
         */
        save: function () {
            var self = this;

            return new Promise(function (resolve) {
                var fields = self.$Grid.getData().filter(function (entry) {
                    return entry.status.getStatus();
                }).map(function (entry) {
                    return entry.id;
                });

                QUIAjax.post('package_quiqqer_products_ajax_products_variant_saveOverwritableFields', resolve, {
                    'package'   : 'quiqqer/products',
                    productId   : self.getAttribute('productId'),
                    overwritable: JSON.encode(fields)
                });
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.refresh().then(function () {
                this.fireEvent('load', [this]);
            }.bind(this));
        },

        /**
         * refresh the grid
         *
         * @return {Promise}
         */
        refresh: function () {
            var self    = this,
                options = this.$Grid.options;

            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getOverwritableFieldList', function (result) {
                    var i, len, entry, Status;
                    var data = [];

                    var overwritable = result.overwritable;

                    for (i = 0, len = result.fields.length; i < len; i++) {
                        entry = result.fields[i];

                        if (!overwritable.length || overwritable.indexOf(entry.id) === -1) {
                            Status = new QUISwitch({
                                status: false
                            });
                        } else {
                            Status = new QUISwitch({
                                status: true
                            });
                        }

                        data.push({
                            status      : Status,
                            id          : parseInt(entry.id),
                            title       : entry.title,
                            workingtitle: entry.workingtitle,
                            fieldtype   : entry.type
                        });
                    }

                    self.$Grid.setData({
                        data : data,
                        total: result.total,
                        page : result.page
                    });

                    resolve();
                }, {
                    'package': 'quiqqer/products',
                    productId: self.getAttribute('productId'),
                    options  : JSON.encode({
                        perPage: options.perPage,
                        page   : options.page,
                        sortOn : options.sortOn,
                        sortBy : options.sortBy
                    })
                });
            });
        }
    });
});