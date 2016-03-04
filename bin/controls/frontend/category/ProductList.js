/**
 * Category view
 * Display a product
 *
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function (QUI, QUIControl, Ajax) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductList',

        Binds: [
            '$onInject',
            'galleryView',
            'detailView',
            'listView',
            'next'
        ],

        options: {
            categoryId: false,
            view      : 'gallery',
            sort      : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList    = null;

            this.$BarSort     = null;
            this.$BarDisplays = null;

            this.$More   = null;
            this.$MoreFX = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            console.log('inject');
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$ButtonDetails = Elm.getElement('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGallery = Elm.getElement('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList    = Elm.getElement('.quiqqer-products-productList-sort-display-list');
            this.$Container     = Elm.getElement('.quiqqer-products-productList-products');

            this.$BarSort     = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays = Elm.getElement('.quiqqer-products-productList-sort-display');


            this.$More   = Elm.getElement('.quiqqer-products-productList-products-more .button');
            this.$MoreFX = moofx(this.$More);

            this.setAttribute('categoryId', this.getElm().get('data-cid').toInt());

            // events
            this.$ButtonDetails.addEvent('click', this.detailView);
            this.$ButtonGallery.addEvent('click', this.galleryView);
            this.$ButtonList.addEvent('click', this.listView);

            switch (this.getAttribute('view')) {
                case 'details':
                    this.$ButtonDetails.addClass('active');
                    break;
                case 'gallery':
                    this.$ButtonGallery.addClass('active');
                    break;
                case 'list':
                    this.$ButtonList.addClass('active');
                    break;
            }

            this.$BarSort.setStyle('display', null);
            this.$BarDisplays.setStyle('display', null);

            this.$parseElements(Elm);

            this.$More.addEvent('click', this.next);
            this.$More.removeClass('disabled');
        },

        /**
         *
         */
        next: function () {
            var size    = this.$More.getSize(),
                LastRow = this.getElm().getElement('[data-row]:last-child'),
                nextRow = LastRow.get('data-row').toInt() + 1;

            this.$More.addClass('disabled');

            this.$More.setStyles({
                height  : size.y,
                overflow: 'hidden',
                width   : size.x
            });

            this.$MoreFX.animate({
                color: 'transparent'
            }, {
                duration: 250,
                callback: function () {

                    var oldButtonText = this.$More.get('text');

                    this.$More.set('html', '<span class="fa fa-spinner fa-spin"></span>');
                    this.$More.setStyle('color', null);
                    this.$More.addClass('loading');

                    this.$loadData(nextRow).then(function () {

                        this.$More.set({
                            html  : oldButtonText,
                            styles: {
                                width: null
                            }
                        });

                        this.$More.removeClass('disabled');
                        this.$More.removeClass('loading');

                    }.bind(this));

                }.bind(this)
            });

        },

        /**
         * Change to gallery view
         *
         * @return {Promise}
         */
        galleryView: function () {
            this.resetButtons();
            this.$ButtonGallery.addClass('active');
            this.setAttribute('view', 'gallery');

            return this.$clearContainer().then(this.$loadData.bind(this));
        },

        /**
         * Change to detail view
         *
         * @return {Promise}
         */
        detailView: function () {
            this.resetButtons();
            this.$ButtonDetails.addClass('active');
            this.setAttribute('view', 'detail');

            return this.$clearContainer().then(this.$loadData.bind(this));
        },

        /**
         * Change to list view
         *
         * @return {Promise}
         */
        listView: function () {
            this.resetButtons();
            this.$ButtonList.addClass('active');
            this.setAttribute('view', 'list');

            return this.$clearContainer().then(this.$loadData.bind(this));
        },

        /**
         * remove all active class from the buttons
         */
        resetButtons: function () {
            this.$ButtonDetails.removeClass('active');
            this.$ButtonGallery.removeClass('active');
            this.$ButtonList.removeClass('active');
        },

        /**
         * Load the data view
         *
         * @param {Number} [row] - wanted row
         */
        $loadData: function (row) {
            row = row || 0;

            var self       = this,
                view       = this.getAttribute('view'),
                sort       = this.getAttribute('sort'),
                categoryId = this.getAttribute('categoryId'),
                Container  = this.$Container;

            return new Promise(function (resolve) {

                Ajax.get('package_quiqqer_products_ajax_controls_categories_productList', function (result) {
                    var Ghost = new Element('div', {
                        html: result
                    });

                    var Row = Ghost.getElement('[data-row]');

                    Row.setStyles({
                        opacity   : 0,
                        overflow  : 'hidden',
                        position  : 'absolute',
                        visibility: 'hidden',
                        width     : '100%'
                    });

                    Row.inject(Container);

                    var rowSize = Row.getSize();

                    Row.setStyles({
                        height  : 0,
                        position: 'relative'
                    });

                    moofx(Row).animate({
                        height    : rowSize.y,
                        opacity   : 1,
                        visibility: null
                    }, {
                        duration: 200,
                        callback: function () {
                            new Fx.Scroll(window.document).start(
                                0,
                                Row.getPosition().y - 100
                            ).chain(function () {
                                self.$Container.setStyle('height', null);
                                resolve();
                            });
                        }
                    });

                }, {
                    'package' : 'quiqqer/products',
                    view      : view,
                    sort      : sort,
                    row       : row,
                    categoryId: categoryId
                });

            });
        },

        /**
         *
         * @param Node
         */
        $parseElements: function (Node) {
            var self    = this,
                Details = Node.getElements(
                    '.quiqqer-products-productGallery-products-product-details'
                );

            Details.addEvent('click', function (event) {
                event.stop();
                self.showProductDetails(this.getParent('article'));
            });
        },

        /**
         *
         * @returns {Promise}
         */
        $clearContainer: function () {
            var self = this,
                rows = this.$Container.getElements('[data-row]');

            var stack = [],
                delay = 0;

            for (var i = 0, len = rows.length; i < len; i++) {
                if (i === 0) {
                    delay = 500;
                }

                if (i == 1) {
                    delay = 300;
                }

                if (i >= 2) {
                    delay = 100;
                }

                stack.push(
                    this.$hideRow(rows[i], delay)
                );
            }

            this.$Container.setStyle('height', this.$Container.getSize().y);

            return new Promise(function (resolve) {

                Promise.all(stack).then(function () {

                    moofx(self.$Container).animate({
                        height: 0
                    }, {
                        duration: 300,
                        callback: function () {
                            self.$Container.setStyle('height', null);
                            resolve();
                        }
                    });
                });

            }.bind(this));
        },

        /**
         * hide a row
         *
         * @param {HTMLDivElement} Row
         * @param {Number} [delay]
         * @returns {Promise}
         */
        $hideRow: function (Row, delay) {
            delay = delay || 100;

            return new Promise(function (resolve) {

                (function () {

                    moofx(Row).animate({
                        opacity: 0
                    }, {
                        duration: 200,
                        callback: function () {
                            Row.destroy();
                            resolve();
                        }
                    });

                }).delay(delay);

            });
        },

        /**
         * Shows the product details
         *
         * @param {HTMLDivElement} Product
         */
        showProductDetails: function (Product) {
            console.log(Product);
        }
    });
});
