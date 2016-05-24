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
 */
define('package/quiqqer/products/bin/controls/products/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/controls/products/Create',
    'package/quiqqer/products/bin/controls/products/Product',
    'package/quiqqer/products/bin/controls/products/search/Search'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale,
             Products, CreateProduct, ProductPanel, Search) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Panel',

        Binds: [
            'refresh',
            'createChild',
            'deleteChild',
            'updateChild',
            '$onShow',
            '$onHide',
            '$onCreate',
            '$onResize',
            '$onInject'
        ],

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'products.panel.title')
            });

            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize,
                onInject: this.$onInject,
                onShow  : this.$onShow,
                onHide  : this.$onHide
            });
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            this.parent();

            var Delete = this.getButtons('delete'),
                Edit   = this.getButtons('edit');

            Delete.enable();
            Edit.enable();

            return this.$Search.search();
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        $onResize: function () {
            return this.$Search.resize();
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self = this;

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
                            self.$Search.getSelected()[0]
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
                            self.$Search.getSelected()[0]
                        );
                    }
                }
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.$Search = new Search({
                injectShow: false,
                events    : {
                    onClick: function () {
                        var Delete = self.getButtons('delete'),
                            Edit   = self.getButtons('edit');

                        Delete.enable();
                        Edit.enable();
                    },

                    onDblClick: function () {
                        self.updateChild(self.$Search.getSelected()[0]);
                    },

                    onSearchBegin: function () {
                        var Delete = self.getButtons('delete'),
                            Edit   = self.getButtons('edit');

                        Delete.disable();
                        Edit.disable();

                        self.Loader.show();
                    },

                    onSearch: function () {
                        self.Loader.hide();
                    }
                }
            }).inject(this.getContent());

            this.refresh();
        },

        /**
         * event: on show
         */
        $onShow: function () {
            this.$Search.show.delay(300, this.$Search);
        },

        /**
         * event: on hide
         */
        $onHide: function () {
            this.$Search.hide();
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
