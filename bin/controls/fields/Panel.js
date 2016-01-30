/**
 * Field management
 *
 * @module package/quiqqer/products/bin/controls/fields/Panel
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/classes/Fields',

    'css!package/quiqqer/products/bin/controls/fields/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale, Handler) {
    "use strict";

    var lg     = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/fields/Panel',

        Binds: [
            'createChild',
            '$onCreate',
            '$onInject',
            '$onResize'
        ],

        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.fields.panel.title'),
                icon : 'fa-file-text-o'
            });

            this.parent(options);

            this.$Grid          = null;
            this.$GridContainer = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        $onResize: function () {
            var size = this.$GridContainer.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Refresh the panel
         */
        refresh: function () {
            var self = this;

            this.Loader.show();
            this.parent();

            Fields.getList().then(function (data) {
                self.$Grid.setData({
                    data: data
                });

                self.Loader.hide();
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            var self    = this,
                Content = this.getContent();

            // buttons
            this.addButton({
                name     : 'add',
                text     : QUILocale.get('quiqqer/system', 'add'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.createChild
                }
            });

            this.addButton({
                name     : 'edit',
                text     : QUILocale.get('quiqqer/system', 'edit'),
                textimage: 'fa fa-edit',
                disabled : true,
                events   : {
                    onClick: function () {
                        self.updateChild(
                            self.$Grid.getSelectedData()[0].id
                        );
                    }
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'delete',
                text     : QUILocale.get('quiqqer/system', 'delete'),
                textimage: 'fa fa-trash',
                disabled : true,
                events   : {
                    onClick: function () {
                        self.deleteChild(
                            self.$Grid.getSelectedData()[0].id
                        );
                    }
                }
            });


            // grid
            this.$GridContainer = new Element('div', {
                'class': 'products-fields-panel-container'
            }).inject(Content);

            var GridContainer = new Element('div', {
                'class': 'products-fields-panel-grid'
            }).inject(this.$GridContainer);

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
                    header   : 'Feld-Typ',
                    dataIndex: 'fieldtype',
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
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$onResize().then(function () {
                this.refresh();
            }.bind(this));
        },


        createChild: function () {

        }
    });
});
