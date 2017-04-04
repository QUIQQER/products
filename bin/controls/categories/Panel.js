/**
 * Category panel
 *
 * @module package/quiqqer/products/bin/controls/categories/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require controls/grid/Grid
 * @require Locale
 * @require package/quiqqer/products/bin/controls/categories/Category
 * @require package/quiqqer/products/bin/classes/Categories
 * @require package/quiqqer/products/bin/controls/categories/Sitemap
 * @require package/quiqqer/products/bin/controls/categories/Create
 * @require css!package/quiqqer/products/bin/controls/categories/Panel.css
 */
define('package/quiqqer/products/bin/controls/categories/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/contextmenu/Menu',
    'qui/controls/contextmenu/Item',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/controls/categories/Category',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/products/bin/controls/categories/Create',

    'css!package/quiqqer/products/bin/controls/categories/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUISitemap, QUISitemapItem,
             QUIContextMenu, QUIContextItem,
             Grid, QUILocale, CategoryPanel, Handler, CategoryMap, CreateCategory) {
    "use strict";

    var lg         = 'quiqqer/products',
        Categories = new Handler();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/categories/Panel',

        Binds: [
            'refresh',
            '$onCreate',
            '$onInject',
            '$onResize',
            'toggleSitemap',
            'createChild',
            'updateChild',
            'deleteChild'
        ],

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'categories.panel.title'),
                icon : 'fa fa-sitemap'
            });

            this.parent(options);

            this.$Grid    = null;
            this.$Sitemap = null;

            this.$GridContainer      = null;
            this.$SitemapContainer   = null;
            this.$SitemapContextMenu = null;

            this.$SitemapFX = null;
            this.$GridFX    = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            this.parent();
            this.Loader.show();

            return Categories.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page
            }).then(function (gridData) {

                self.$Grid.setData(gridData);

                var Delete = self.getButtons('delete'),
                    Edit   = self.getButtons('edit');

                Delete.disable();
                Edit.disable();

                Delete.setAttribute(
                    'text',
                    QUILocale.get('quiqqer/system', 'delete')
                );

                Edit.setAttribute(
                    'text',
                    QUILocale.get('quiqqer/system', 'edit')
                );

                self.Loader.hide();
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            var self    = this,
                Content = this.getContent();

            Content.setStyles({
                padding: 0
            });

            // buttons
            this.addButton({
                name  : 'sitemap',
                image : 'fa fa-sitemap',
                events: {
                    onClick: this.toggleSitemap
                }
            });

            this.addButton({
                type: 'separator'
            });

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
                type: 'separator'
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


            // content
            this.$SitemapContainer = new Element('div', {
                'class': 'products-categories-panel-sitemap shadow',
                styles : {
                    opacity: 0,
                    width  : 0
                }
            }).inject(Content);

            this.$SitemapFX = moofx(this.$SitemapContainer);

            this.$Sitemap = new CategoryMap({
                events: {
                    onClick           : this.refresh,
                    onChildContextMenu: function (CatMap, Item, event) {
                        if (Item.getAttribute('value') === '') {
                            return;
                        }

                        event.stop();

                        self.$SitemapContextMenu.setPosition(
                            event.page.x,
                            event.page.y
                        );

                        self.$SitemapContextMenu.setAttribute('Category', Item);
                        self.$SitemapContextMenu.show();
                    }
                }
            }).inject(this.$SitemapContainer);


            this.$GridContainer = new Element('div', {
                'class': 'products-categories-panel-container'
            }).inject(Content);

            this.$GridFX = moofx(this.$GridContainer);

            var GridContainer = new Element('div', {
                'class': 'products-categories-panel-grid'
            }).inject(this.$GridContainer);

            this.$Grid = new Grid(GridContainer, {
                pagination : true,
                perPage    : 150,
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
                    header   : QUILocale.get('quiqqer/system', 'description'),
                    dataIndex: 'description',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'products.categories.grid.assigned.sites'),
                    dataIndex: 'site',
                    dataType : 'text',
                    width    : 200
                }]
            });

            this.$Grid.addEvents({
                onDblClick: function () {
                    self.updateChild(
                        self.$Grid.getSelectedData()[0].id
                    );
                },
                onClick   : function () {
                    var selected = self.$Grid.getSelectedData()[0],
                        Delete   = self.getButtons('delete'),
                        Edit     = self.getButtons('edit');

                    Delete.enable();
                    Edit.enable();

                    Delete.setAttribute(
                        'text',
                        QUILocale.get('quiqqer/system', 'delete') + ' (#' + selected.id + ')'
                    );

                    Edit.setAttribute(
                        'text',
                        QUILocale.get('quiqqer/system', 'edit') + ' (#' + selected.id + ')'
                    );
                },
                onRefresh : this.refresh
            });


            this.$SitemapContextMenu = new QUIContextMenu({
                events: {
                    onShow: function () {
                        var Menu     = self.$SitemapContextMenu,
                            Category = Menu.getAttribute('Category');

                        Menu.setTitle(Category.getAttribute('text'));
                        Menu.refresh();
                        Menu.focus();
                    },
                    onBlur: function () {
                        self.$SitemapContextMenu.hide();
                    }
                }
            }).inject(document.body);

            this.$SitemapContextMenu.appendChild(
                new QUIContextItem({
                    name  : 'add',
                    text  : 'Unterkategorie hinzufügen',
                    icon  : 'fa fa-plus',
                    events: {
                        onClick: function () {
                            var Menu     = self.$SitemapContextMenu,
                                Category = Menu.getAttribute('Category');

                            self.createChild(
                                Category.getAttribute('value')
                            );
                        }
                    }
                })
            ).appendChild(
                new QUIContextItem({
                    name  : 'edit',
                    text  : QUILocale.get('quiqqer/system', 'edit'),
                    icon  : 'fa fa-edit',
                    events: {
                        onClick: function () {
                            var Menu     = self.$SitemapContextMenu,
                                Category = Menu.getAttribute('Category');

                            self.updateChild(
                                Category.getAttribute('value')
                            );
                        }
                    }
                })
            ).appendChild(
                new QUIContextItem({
                    name  : 'delete',
                    text  : QUILocale.get('quiqqer/system', 'delete'),
                    icon  : 'fa fa-trash',
                    events: {
                        onClick: function () {
                            var Menu     = self.$SitemapContextMenu,
                                Category = Menu.getAttribute('Category');

                            self.deleteChild(
                                Category.getAttribute('value')
                            );
                        }
                    }
                })
            );

        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.resize();
            this.$Sitemap.firstChild().click();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            var size   = this.$GridContainer.getSize(),
                Button = this.getButtons('sitemap'),
                active = Button.isActive();

            if (active) {
                this.$Grid.setWidth(size.x - 340);
            } else {
                this.$Grid.setWidth(size.x - 40);
            }

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.resize();
        },

        /**
         * toggle the sitemap display
         */
        toggleSitemap: function () {
            return new Promise(function () {

                var Button = this.getButtons('sitemap'),
                    status = Button.isActive();

                if (status === false) {

                    this.openSitemap().then(function () {
                        Button.setActive();
                    });

                    return;
                }

                this.closeSitemap().then(function () {
                    Button.setNormal();
                });

            }.bind(this));
        },

        /**
         * open the sitemap
         *
         * @returns {Promise}
         */
        openSitemap: function () {
            var self = this;

            return new Promise(function (resolve) {

                var size = self.$GridContainer.getSize();

                self.$GridFX.animate({
                    paddingLeft: 320
                }, {
                    duration: 200
                });

                self.$Grid.setWidth(size.x - 340).then(function () {
                    self.$SitemapFX.animate({
                        opacity: 1,
                        width  : 300
                    }, {
                        duration: 200,
                        callback: function () {

                            self.getButtons('sitemap').setActive();

                            resolve();
                        }
                    });
                });
            });
        },

        /**
         * close the sitemap
         *
         * @returns {Promise}
         */
        closeSitemap: function () {
            var self = this;

            return new Promise(function (resolve) {

                var size = self.$GridContainer.getSize();

                self.$SitemapFX.animate({
                    opacity: 0,
                    width  : 0
                }, {
                    duration: 200,
                    callback: function () {
                        self.$GridFX.animate({
                            paddingLeft: 20
                        }, {
                            duration: 200
                        });

                        self.$Grid.setWidth(size.x - 40);
                        self.getButtons('sitemap').setNormal();

                        resolve();
                    }
                });
            });
        },

        /**
         * Opens the create child dialog
         *
         * @param {Number|String} [parentId] - Parent-ID
         */
        createChild: function (parentId) {
            var self   = this,
                Active = null;

            if (self.$Sitemap) {
                var selected = self.$Sitemap.getSelected();

                if (selected.length) {
                    Active = selected[0];
                }
            }

            if (typeof parentId === 'undefined' ||
                typeof parentId === 'object') {

                if (Active) {
                    parentId = Active.getAttribute('value');
                } else {
                    parentId = '';
                }
            }


            this.closeSitemap().then(function () {

                self.createSheet({
                    title  : QUILocale.get(lg, 'categories.create.title'),
                    buttons: false,
                    events : {
                        onShow : function (Sheet) {
                            new CreateCategory({
                                parentId: parentId,
                                events  : {
                                    onCancel : function () {
                                        Sheet.hide();
                                    },
                                    onSubmit : function () {
                                        self.Loader.show();
                                    },
                                    onSuccess: function (Create, categoryData) {
                                        Sheet.hide().then(function () {
                                            Sheet.destroy();
                                            self.refresh();

                                            self.updateChild(categoryData.id);
                                        });
                                    }
                                }
                            }).inject(Sheet.getContent());
                        },
                        onClose: function (Sheet) {
                            Sheet.destroy();
                        }
                    }
                }).show();

            });
        },

        /**
         * opens the edit dialog
         *
         * @param {Number} categoryId - Category-ID
         */
        updateChild: function (categoryId) {
            new CategoryPanel({
                categoryId: categoryId
            }).inject(this.getParent());
        },

        /**
         * Delete the category
         *
         * @param {Number} categoryId
         */
        deleteChild: function (categoryId) {
            var self = this;

            new QUIConfirm({
                title      : QUILocale.get(lg, 'categories.window.delete.title'),
                text       : QUILocale.get(lg, 'categories.window.delete.text', {
                    categoryId: categoryId
                }),
                information: QUILocale.get(lg, 'categories.window.delete.information', {
                    id: categoryId
                }),
                autoclose  : false,
                maxHeight  : 300,
                maxWidth   : 450,
                icon       : 'fa fa-trashcan',
                texticon   : 'fa fa-trashcan',
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        Categories.deleteChild(categoryId).then(function () {
                            Win.close();

                            self.$Sitemap.getSelected().each(function (Entry) {
                                if (Entry && Entry.getAttribute('value') !== '') {
                                    Entry.destroy();
                                }
                            });

                            self.$Sitemap.firstChild().click();
                            self.refresh();
                        });
                    }
                }
            }).open();
        }
    });
});
