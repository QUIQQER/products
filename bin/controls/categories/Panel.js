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
 * @require css!package/quiqqer/products/bin/controls/categories/Panel.css
 */
define('package/quiqqer/products/bin/controls/categories/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/products/bin/controls/categories/Create',

    'css!package/quiqqer/products/bin/controls/categories/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUISitemap, QUISitemapItem,
             Grid, QUILocale, Handler, CategoryMap, CreateCategory) {
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
            'updateChild'
        ],

        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'categories.panel.title')
            });

            this.parent(options);

            this.$Grid    = null;
            this.$Sitemap = null;

            this.$GridContainer    = null;
            this.$SitemapContainer = null;

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
         */
        refresh: function () {
            var self = this;

            this.parent();
            this.Loader.show();

            var Item = this.$Sitemap.getActive(),
                id   = Item.getAttribute('value');

            Categories.getChildren(id).then(function (gridData) {

                self.$Grid.setData({
                    data: gridData
                });

                self.Loader.hide();
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            var Content = this.getContent();

            Content.setStyles({
                padding: 0
            });

            // buttons
            this.addButton({
                name  : 'sitemap',
                image : 'icon-sitemap fa fa-sitemap',
                events: {
                    onClick: this.toggleSitemap
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'add',
                text     : QUILocale.get('quiqqer/system', 'add'),
                textimage: 'icon-plus fa fa-plus',
                events   : {
                    onClick: this.createChild
                }
            });

            this.addButton({
                name     : 'edit',
                text     : QUILocale.get('quiqqer/system', 'edit'),
                textimage: 'icon-edit fa fa-edit',
                events   : {
                    onClick: this.updateChild
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'edit',
                text     : QUILocale.get('quiqqer/system', 'delete'),
                textimage: 'icon-trashcan fa fa-trashcan',
                events   : {
                    onClick: this.deletChild
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
                    onClick: this.refresh
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
                    header   : 'Zugehörige Seite',
                    dataIndex: 'site',
                    dataType : 'text',
                    width    : 200
                }]
            });

            this.$Grid.addEvents({
                onDblClick: function () {

                    this.$Grid.getSelectedData()[0];
                }
            });
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

                        resolve();
                    }
                });
            });
        },

        /**
         * Opens the create child dialog
         */
        createChild: function () {
            var self     = this,
                Active   = self.$Sitemap.getActive(),
                parentId = '';

            if (Active) {
                parentId = Active.getAttribute('value');
            }

            this.closeSitemap().then(function () {

                self.createSheet({
                    title  : QUILocale.get(lg, 'categories.window.create.title'),
                    text   : QUILocale.get(lg, 'categories.window.create.text'),
                    buttons: false,
                    events : {
                        onShow: function (Sheet) {
                            new CreateCategory({
                                parentId: parentId,
                                events  : {
                                    onCancel : function () {
                                        Sheet.hide();
                                    },
                                    onSubmit : function () {
                                        self.Loader.show();
                                    },
                                    onSuccess: function () {
                                        Sheet.hide();
                                        self.refresh();
                                    }
                                }
                            }).inject(Sheet.getContent());
                        }
                    }
                }).show();

            });
        },

        /**
         * opens the edit dialog
         *
         * @param {Number} childId - Category-ID
         */
        updateChild: function (childId) {
            var self = this;

            self.Loader.show();

            this.createSheet({
                events: {
                    onShow: function (Sheet) {

                        Sheet.getContent().set({
                            styles: {
                                padding: 20
                            }
                        });

                        self.Loader.hide();
                    }
                }
            }).show();
        }
    });
});
