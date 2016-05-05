/**
 * Product management
 *
 * @module package/quiqqer/products/bin/controls/products/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require Locale
 * @require package/quiqqer/products/bin/classes/Products
 * @require package/quiqqer/products/bin/controls/products/Create
 * @require package/quiqqer/products/bin/controls/products/Product
 * @require css!package/quiqqer/products/bin/controls/products/Panel.css
 */
define('package/quiqqer/products/bin/controls/products/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/products/bin/controls/products/Create',
    'package/quiqqer/products/bin/controls/products/Product',

    'css!package/quiqqer/products/bin/controls/products/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale,
             Handler, CreateProduct, ProductPanel) {
    "use strict";

    var lg       = 'quiqqer/products',
        Products = new Handler();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Panel',

        Binds: [
            'refresh',
            'createChild',
            'deleteChild',
            'updateChild',
            '$onCreate',
            '$onResize',
            '$onInject'
        ],

        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.panel.title')
            });

            this.parent(options);

            this.$Grid          = null;
            this.$GridContainer = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize,
                onInject: this.$onInject
            });
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            this.Loader.show();
            this.parent();

            return Products.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page
            }).then(function (data) {
                for (var i = 0, len = data.data.length; i < len; i++) {
                    data.data[i].status = new Element('span', {
                        'class': data.data[i].active ? 'fa fa-check' : 'fa fa-remove'
                    });
                }

                self.$Grid.setData(data);

                var Delete = self.getButtons('delete'),
                    Edit   = self.getButtons('edit');

                Delete.disable();
                Edit.disable();

                self.Loader.hide();
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
                'class': 'products-categories-panel-grid-container'
            }).inject(Content);

            var GridContainer = new Element('div', {
                'class': 'products-categories-panel-grid'
            }).inject(this.$GridContainer);

            this.$Grid = new Grid(GridContainer, {
                pagination : true,
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'status'),
                    dataIndex: 'status',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get('quiqqer/system', 'description'),
                    dataIndex: 'description',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'products.product.panel.grid.nettoprice'),
                    dataIndex: 'price',
                    dataType : 'text',
                    width    : 100
                }, {
                    dataIndex: 'active',
                    dataType : 'number',
                    hidden   : true
                }]
            });


            this.$Grid.addEvents({
                onRefresh : this.refresh,
                onClick   : function () {
                    var Delete = self.getButtons('delete'),
                        Edit   = self.getButtons('edit');

                    Delete.enable();
                    Edit.enable();

                },
                onDblClick: function () {
                    self.updateChild(
                        self.$Grid.getSelectedData()[0].id
                    );
                }
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * Opens the create child dialog
         */
        createChild: function () {

            var self = this;

            this.Loader.show();

            this.createSheet({
                title : QUILocale.get(lg, 'products.create.title'),
                events: {
                    onShow : function (Sheet) {

                        Sheet.getContent().setStyle('padding', 20);

                        var Product = new CreateProduct({
                            events: {
                                onLoaded: function () {
                                    self.Loader.hide();
                                }
                            }
                        }).inject(Sheet.getContent());


                        Sheet.addButton(
                            new QUIButton({
                                text     : QUILocale.get('quiqqer/system', 'save'),
                                textimage: 'fa fa-save',
                                events   : {
                                    onClick: function () {
                                        self.Loader.show();

                                        Product.submit().then(function () {
                                            Sheet.hide().then(function () {
                                                Sheet.destroy();
                                                self.refresh();
                                            });
                                        }).catch(function (err) {
                                            if (typeOf(err) == 'string') {
                                                QUI.getMessageHandler().then(function (MH) {
                                                    MH.addError(err);
                                                });
                                            }

                                            self.Loader.hide();
                                        });
                                    }
                                }
                            })
                        );
                    },
                    onClose: function (Sheet) {
                        Sheet.destroy();
                    }
                }
            }).show();
        },

        /**
         * Opens the product panel
         *
         * @param {Number} productId
         */
        updateChild: function (productId) {
            new ProductPanel({
                productId: productId
            }).inject(this.getParent());
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number} productId
         */
        deleteChild: function (productId) {
            var self = this;

            new QUIConfirm({
                title      : QUILocale.get(lg, 'products.window.delete.title'),
                text       : QUILocale.get(lg, 'products.window.delete.text', {
                    productId: productId
                }),
                information: QUILocale.get(lg, 'products.window.delete.information', {
                    productId: productId
                }),
                autoclose  : false,
                maxHeight  : 300,
                maxWidth   : 450,
                icon       : 'fa fa-trashcan',
                texticon   : 'fa fa-trashcan',
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();
                        Products.deleteChild(productId).then(function () {
                            Win.close();
                            self.refresh();
                        });
                    }
                }
            }).open();
        }
    });
});
