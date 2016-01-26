/**
 *
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

    'css!package/quiqqer/products/bin/controls/categories/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUISitemap, QUISitemapItem, Grid, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/categories/Panel',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onResize',
            'toggleSitemap'
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
            this.parent();
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

            this.$Sitemap = new QUISitemap().inject(this.$SitemapContainer);

            this.$Sitemap.appendChild(
                new QUISitemapItem({
                    text: 'Kategorien',
                    id  : 0,
                    icon: 'fa fa-shopping-basket'
                })
            );

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
                    header   : QUILocale.get(lg, 'products.categories.grid.fields'),
                    dataIndex: 'fields',
                    dataType : 'text',
                    width    : 200
                }]
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.resize();
            this.refresh();
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            var size = this.$GridContainer.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
            this.$Grid.resize();
        },

        /**
         * toggle the sitemap display
         */
        toggleSitemap: function () {
            return new Promise(function () {

                var self   = this,
                    Button = this.getButtons('sitemap'),
                    status = Button.isActive(),
                    size   = this.$GridContainer.getSize();

                if (status === false) {

                    this.$GridFX.animate({
                        paddingLeft: 320
                    }, {
                        duration: 200
                    });

                    this.$Grid.setWidth(size.x - 340).then(function () {
                        self.$SitemapFX.animate({
                            opacity: 1,
                            width  : 300
                        }, {
                            duration: 200,
                            callback: function () {
                                Button.setActive();
                            }
                        });
                    });

                    return;
                }

                this.$SitemapFX.animate({
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

                        Button.setNormal();
                    }
                });

            }.bind(this));
        }
    });
});
