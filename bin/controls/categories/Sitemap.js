/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require package/quiqqer/products/bin/classes/Categories
 *
 * @event onClick [this, id, Item]
 */
define('package/quiqqer/products/bin/controls/categories/Sitemap', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'package/quiqqer/products/bin/classes/Categories'

], function (QUI, QUIControl, QUISitemap, QUISitemapItem, Handler) {
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
            selectedId: false
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
            var Elm = this.parent();

            this.$Sitemap = new QUISitemap({
                name: 'map'
            }).inject(Elm);

            this.$Sitemap.appendChild(
                new QUISitemapItem({
                    text  : 'Kategorien',
                    id    : 0,
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
                Item.addIcon('fa fa-spinner fa-spin');

                Categories.getChildren(categoryId).then(function (data) {

                    var i, len, entry;

                    for (i = 0, len = data.length; i < len; i++) {

                        entry = data[i];

                        new QUISitemapItem({
                            text       : entry.title,
                            value      : entry.id,
                            icon       : 'fa fa-shopping-basket',
                            hasChildren: parseInt(entry.countChildren),
                            events     : {
                                onOpen : self.$onItemOpen,
                                onClose: self.$onItemClose,
                                onClick: self.$onItemClick
                            }
                        }).inject(Item);
                    }

                    Item.removeIcon('fa-spinner');
                    Item.addIcon('fa fa-shopping-basket');
                    Item.open();

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
            this.fireEvent('click', [this, Item.getAttribute('value'), Item]);
        },

        /**
         * Return the active item
         *
         * @returns {Object}
         */
        getActive: function () {
            return this.$Sitemap.getSelectedChildren()[0];
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
