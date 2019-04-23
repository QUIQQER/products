/**
 * @module package/quiqqer/products/bin/controls/products/OverwriteableFieldList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldList', [

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
        Type   : 'package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldList',

        Binds: [
            '$onInject'
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
                'class'   : 'quiqqer-products-variant-overwriteable-fields',
                id        : this.getId(),
                'data-qui': 'package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldList',
                styles    : {
                    height: '100%'
                }
            });


            var Container = new Element('div').inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                pagination : true,
                width      : Container.getSize().x,
                height     : Container.getSize().y,
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'status'),
                    dataIndex: 'status',
                    dataType : 'QUI',
                    width    : 60
                }, {
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
                }]
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
         * Saves the overwriteable fields to the product
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

                QUIAjax.post('package_quiqqer_products_ajax_products_variant_saveOverwriteableFields', resolve, {
                    'package'    : 'quiqqer/products',
                    productId    : self.getAttribute('productId'),
                    overwriteable: JSON.encode(fields)
                });
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            QUIAjax.get('package_quiqqer_products_ajax_products_variant_getOverwriteableFieldList', function (result) {
                var i, len, entry, Status;
                var data = [];

                var overwriteable = result.overwriteable;

                for (i = 0, len = result.fields.length; i < len; i++) {
                    entry = result.fields[i];

                    if (!overwriteable.length || overwriteable.indexOf(entry.id) === -1) {
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

                data.sort(function (a, b) {
                    return a.id - b.id;
                });

                self.$Grid.setData({
                    data: data
                });

                self.fireEvent('load', [self]);
            }, {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId')
            });
        }
    });
});
