/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onClick [this, id, Item]
 * @event onChildContextMenu [this, Item, event]
 */
define('package/quiqqer/products/bin/controls/categories/Sitemap', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/utils/Functions',
    'package/quiqqer/products/bin/classes/Categories',
    'Locale'

], function (QUI, QUIControl, QUISitemap, QUISitemapItem, QUIFunctionUtils, Handler, QUILocale) {
    "use strict";

    var Categories = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/Sitemap',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onItemOpen',
            '$onItemClose',
            '$onItemClick'
        ],

        options: {
            selectedId: false,
            multiple  : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Sitemap = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var self = this,
                Elm  = this.parent();

            var showContextMenu = QUIFunctionUtils.debounce(function () {
                self.fireEvent('childContextMenu', [
                    self,
                    this.Item,
                    this.event
                ]);
            }, 200, true);

            this.$Sitemap = new QUISitemap({
                name    : 'map',
                multiple: this.getAttribute('multiple'),
                events  : {
                    onChildContextMenu: function (Map, Item, event) {
                        showContextMenu.bind({
                            Map  : Map,
                            Item : Item,
                            event: event
                        })();
                    }
                }
            }).inject(Elm);

            this.$Sitemap.appendChild(
                new QUISitemapItem({
                    text  : QUILocale.get('quiqqer/products', 'products.category.0.title'),
                    id    : 0,
                    value : 0,
                    icon  : 'fa fa-shopping-basket',
                    events: {
                        onOpen : this.$onItemOpen,
                        onClose: this.$onItemClose,
                        onClick: this.$onItemClick
                    }
                })
            );

            return Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.$Sitemap.firstChild().open();

            var selectedId = this.getAttribute('selectedId');

            if (selectedId === false) {
                return;
            }

            var self   = this,
                values = this.$Sitemap.getChildrenByValue(selectedId);

            if (values.length) {
                values[0].click();
                return;
            }

            Categories.getPath(selectedId).then(function (path) {

                var openElement = function (id) {
                    return new Promise(function (resolve, reject) {
                        var values = self.$Sitemap.getChildrenByValue(id);
                        if (!values.length) {
                            reject();
                            return;
                        }

                        self.$onItemOpen(values[1]).then(function () {
                            resolve();
                        });
                    });
                };

                var Prom = Promise.resolve();

                for (var i = 0, len = path.length; i < len; i++) {
                    Prom.then(openElement(path[0]));
                }

                Prom.then(function () {
                    var result = self.$Sitemap.getChildrenByValue(selectedId);

                    if (result[0]) {
                        result[0].click();
                    }
                });
            });
        },

        /**
         * event:  map item on open
         *
         * @param {Object} Item
         */
        $onItemOpen: function (Item) {

            var self       = this,
                categoryId = Item.getAttribute('value');

            return new Promise(function (resolve) {
                if (Item.isOpen() && Item.getChildren().length) {
                    return resolve();
                }

                Item.removeIcon('fa-shopping-basket');
                Item.removeIcon('fa-sitemap');
                Item.addIcon('fa fa-spinner fa-spin');

                Categories.getChildren(categoryId || 0, {
                    countChildren: 1
                }).then(function (data) {
                    var i, len, entry;

                    for (i = 0, len = data.length; i < len; i++) {
                        entry = data[i];

                        new QUISitemapItem({
                            text       : entry.title,
                            value      : entry.id,
                            icon       : 'fa fa-sitemap',
                            hasChildren: parseInt(entry.countChildren),
                            events     : {
                                onOpen : self.$onItemOpen,
                                onClose: self.$onItemClose,
                                onClick: self.$onItemClick
                            }
                        }).inject(Item);
                    }

                    Item.removeIcon('fa-spinner');

                    if (Item.getAttribute('id') === 0) {
                        Item.addIcon('fa fa-shopping-basket');
                    } else {
                        Item.addIcon('fa fa-sitemap');
                    }

                    if (data.length) {
                        Item.open();
                    }

                    resolve();
                });
            });
        },

        /**
         * event:  map item on close
         *
         * @param {Object} Item
         */
        $onItemClose: function (Item) {
            Item.clearChildren();
            Item.setAttribute('hasChildren', 1);
        },

        /**
         * event on click
         *
         * @param Item
         */
        $onItemClick: function (Item) {
            this.fireEvent('click', [
                this,
                Item.getAttribute('value'),
                Item
            ]);
        },

        /**
         * Return the selected item(s)
         *
         * @returns {array}
         */
        getSelected: function () {
            return this.$Sitemap.getSelectedChildren();
        },

        /**
         * Return the selected item(s)
         *
         * @returns {array}
         */
        getMap: function () {
            return this.$Sitemap;
        },

        /**
         * Return the first child of the sitemap
         *
         * @returns {Object}
         */
        firstChild: function () {
            return this.$Sitemap.firstChild();
        }
    });
});
