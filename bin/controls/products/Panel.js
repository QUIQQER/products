/**
 * Product management
 *
 * @module package/quiqqer/products/bin/controls/products/Panel
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/ButtonMultiple',
    'qui/controls/windows/Confirm',
    'qui/controls/contextmenu/Separator',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/controls/products/Create',
    'package/quiqqer/products/bin/controls/products/Product',
    'package/quiqqer/productsearch/bin/controls/products/search/Search',

    'css!package/quiqqer/products/bin/controls/products/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIButtonMultiple, QUIConfirm, QUIMenuSeparator, Grid, QUILocale,
             Products, CreateProduct, ProductPanel, Search) {
    "use strict";

    const lg = 'quiqqer/products';

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
                icon : 'fa fa-shopping-bag',
                title: QUILocale.get(lg, 'products.panel.title')
            });

            this.parent(options);

            this.$Search = null;
            this.$ButtonAdd = null;

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

            const Action   = this.getButtons('actions'),
                  children = Action.getChildren();

            const Delete = children.filter(function (Btn) {
                return Btn.getAttribute('name') === 'delete';
            })[0];

            const Edit = children.filter(function (Btn) {
                return Btn.getAttribute('name') === 'edit';
            })[0];

            const Copy = children.filter(function (Btn) {
                return Btn.getAttribute('name') === 'copy';
            })[0];

            const Activate = children.filter(function (Btn) {
                return Btn.getAttribute('name') === 'activate';
            })[0];

            const Deactivate = children.filter(function (Btn) {
                return Btn.getAttribute('name') === 'deactivate';
            })[0];

            Delete.disable();
            Copy.disable();
            Edit.disable();
            Activate.disable();
            Deactivate.disable();

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
            this.getContent().addClass('quiqqer-products-productPanel');
            this.getContent().setStyle('padding', 10);

            // buttons
            this.$ButtonAdd = new QUIButton({
                name     : 'add',
                text     : QUILocale.get('quiqqer/core', 'add'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.createChild
                }
            });

            this.addButton(this.$ButtonAdd);

            // aktionen
            const Actions = new QUIButton({
                name      : 'actions',
                text      : QUILocale.get(lg, 'btn.actions'),
                menuCorner: 'topRight',
                styles    : {
                    'float': 'right'
                }
            });

            Actions.appendChild({
                name    : 'edit',
                text    : QUILocale.get('quiqqer/core', 'edit'),
                icon    : 'fa fa-edit',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.updateChild(
                            this.$Search.getSelected()[0]
                        );
                    }
                }
            });

            Actions.appendChild({
                name    : 'copy',
                text    : QUILocale.get('quiqqer/core', 'copy'),
                icon    : 'fa fa-copy',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.copyChild(
                            this.$Search.getSelected()[0]
                        );
                    }
                }
            });

            Actions.appendChild(
                new QUIMenuSeparator()
            );

            Actions.appendChild({
                name    : 'activate',
                text    : QUILocale.get('quiqqer/core', 'activate'),
                icon    : 'fa fa-check',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.activateChildren();
                    }
                }
            });

            Actions.appendChild({
                name    : 'deactivate',
                text    : QUILocale.get('quiqqer/core', 'deactivate'),
                icon    : 'fa fa-remove',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.deactivateChildren();
                    }
                }
            });

            Actions.appendChild(
                new QUIMenuSeparator()
            );

            Actions.appendChild({
                name    : 'delete',
                text    : QUILocale.get('quiqqer/core', 'delete'),
                icon    : 'fa fa-trash',
                disabled: true,
                events  : {
                    onClick: (Btn) => {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        this.deleteChild(this.$Search.getSelected()).catch(function (Exception) {
                            if (Exception.getType() === 'qui/controls/messages/Error') {
                                return;
                            }

                            console.error(Exception);
                        }).then(() => {
                            Btn.setAttribute('textimage', 'fa fa-trashcan');
                        });
                    }
                }
            });

            this.getButtonBar().appendChild(Actions);

            this.getButtonBar().appendChild(
                new Element('button', {
                    type   : 'button',
                    'class': 'qui-button qui-utils-noselect',
                    html   : '<span class="fa fa-search"></span>',
                    styles : {
                        'float'    : 'right',
                        marginRight: 5
                    },
                    events : {
                        click: () => {
                            this.$Search.search();
                        }
                    }
                })
            );

            this.$SearchInput = new Element('input', {
                placeholder: QUILocale.get(lg, 'controls.products.search'),
                styles     : {
                    'float': 'right',
                    margin : 10,
                    width  : 200
                },
                events     : {
                    keyup: (e) => {
                        e.stop();

                        this.getContent()
                            .getElements('[name="search"]')
                            .set('value', this.$SearchInput.value);

                        if (e.key === 'enter') {
                            this.$Search.search();
                        }
                    }
                }
            });

            this.getButtonBar().appendChild(this.$SearchInput);
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Search = new Search({
                injectShow: false,
                events    : {
                    onClick: () => {
                        const Action   = this.getButtons('actions'),
                              children = Action.getChildren();

                        const selected = this.$Search.getSelected();

                        const Edit = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'edit';
                        })[0];

                        const Copy = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'copy';
                        })[0];

                        const Delete = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'delete';
                        })[0];

                        const Activate = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'activate';
                        })[0];

                        const Deactivate = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'deactivate';
                        })[0];

                        Delete.enable();
                        Activate.enable();
                        Deactivate.enable();

                        if (selected.length === 1) {
                            Copy.enable();
                            Edit.enable();
                        } else {
                            Copy.disable();
                            Edit.disable();
                        }
                    },

                    onDblClick: () => {
                        this.updateChild(this.$Search.getSelected()[0]);
                    },

                    onSearchBegin: () => {
                        const Action   = this.getButtons('actions'),
                              children = Action.getChildren();

                        const Delete = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'delete';
                        })[0];

                        const Edit = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'edit';
                        })[0];

                        const Copy = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'copy';
                        })[0];

                        const Activate = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'activate';
                        })[0];

                        const Deactivate = children.filter(function (Btn) {
                            return Btn.getAttribute('name') === 'deactivate';
                        })[0];

                        Copy.disable();
                        Edit.disable();
                        Delete.disable();
                        Activate.disable();
                        Deactivate.disable();

                        this.Loader.show();
                    },

                    onSearch: () => {
                        this.Loader.hide();
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
            this.Loader.show();

            require([
                'package/quiqqer/products/bin/controls/products/CreateProductWindow'
            ], (CreateProductWindow) => {
                const categories = this.$Search.$Form.$Sitemap.getSelected().map(function (Item) {
                    return Item.getAttribute('value');
                });

                new CreateProductWindow({
                    categories: categories,
                    events    : {
                        onProductCreated: (Win, product) => {
                            this.refresh();
                            this.updateChild(product.id);
                        }
                    }
                }).open();

                this.Loader.hide();
            });
        },

        /**
         * Opens the product panel
         *
         * @param {Number} productId
         */
        updateChild: function (productId) {
            this.Loader.show();

            Products.openProduct(productId).then(() => {
                this.Loader.hide();
            });
        },

        /**
         * Activate all marked products
         */
        activateChildren: function () {
            this.Loader.show();

            Products.activateChildren(
                this.$Search.getSelected()
            ).then(() => {
                this.Loader.hide();
                this.refresh();
            }).catch(() => {
                this.Loader.hide();
                this.refresh();
            });
        },

        /**
         * Deactivate all marked products
         */
        deactivateChildren: function () {
            this.Loader.show();

            Products.deactivateChildren(
                this.$Search.getSelected()
            ).then(() => {
                this.Loader.hide();
                this.refresh();
            }).catch(() => {
                this.Loader.hide();
                this.refresh();
            });
        },

        /**
         * Opens the product panel
         *
         * @param {Number} productId
         */
        copyChild: function (productId) {
            this.Loader.show();

            Products.copy(productId).then((newProductId) => {
                Products.openProduct(newProductId).then(() => {
                    this.Loader.hide();
                    this.refresh();
                });
            });
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number|Array} productIds
         * @return {Promise}
         */
        deleteChild: function (productIds) {
            return new Promise((resolve, reject) => {
                if (typeOf(productIds) === 'number') {
                    productIds = [productIds];
                }

                if (typeOf(productIds) !== 'array') {
                    return reject();
                }

                if (!productIds.length) {
                    return reject();
                }

                Products.getChildren(productIds).then((data) => {
                    if (!data.length) {
                        return reject();
                    }

                    let products = '<ul>';

                    for (let i = 0, len = data.length; i < len; i++) {
                        products = products + '<li>' + data[i].id + ': ' +
                                   data[i].title + '</li>';
                    }

                    products = products + '</ul>';

                    new QUIConfirm({
                        title      : QUILocale.get(lg, 'products.window.delete.title'),
                        text       : QUILocale.get(lg, 'products.window.delete.text', {
                            products: products
                        }),
                        information: QUILocale.get(lg, 'products.window.delete.information', {
                            products: products
                        }),
                        autoclose  : false,
                        maxHeight  : 400,
                        maxWidth   : 600,
                        icon       : 'fa fa-trashcan',
                        texticon   : 'fa fa-trashcan',
                        events     : {
                            onSubmit: (Win) => {
                                Win.Loader.show();

                                Products.deleteChildren(productIds).then(() => {
                                    Win.close();
                                    this.refresh();

                                    QUI.getMessageHandler().then((MH) => {
                                        MH.addSuccess(
                                            QUILocale.get(lg, 'message.success.products.delete')
                                        );
                                    });
                                });
                            }
                        }
                    }).open();

                    resolve();
                }, reject);
            });
        }
    });
});
