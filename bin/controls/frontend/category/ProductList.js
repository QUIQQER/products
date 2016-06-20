/**
 * Category view
 * Display a product
 *
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require package/quiqqer/products/bin/Search
 * @require Ajax
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'package/quiqqer/products/bin/Search',
    'Ajax'

], function (QUI, QUIControl, QUISelect, Search, Ajax) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductList',

        Binds: [
            'galeryView',
            'detailView',
            'listView',
            'next',
            'showAllCategories',
            '$hideMoreButton',
            '$showMoreButton',
            '$scrollToLastRow',
            '$onInject'
        ],

        options: {
            categoryId: false,
            view      : 'galery',
            sort      : false,
            project   : false,
            lang      : false,
            siteId    : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$ButtonDetails = null;
            this.$ButtonGalery  = null;
            this.$ButtonList    = null;

            this.$BarSort     = null;
            this.$BarDisplays = null;

            this.$More   = null;
            this.$Sort   = null;
            this.$MoreFX = null;

            this.$CategoryMore = null;

            this.$fields         = {};
            this.$freetext       = '';
            this.$sortingEnabled = true;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {

        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm();

            this.$ButtonDetails = Elm.getElements('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGalery  = Elm.getElements('.quiqqer-products-productList-sort-display-galery');
            this.$ButtonList    = Elm.getElements('.quiqqer-products-productList-sort-display-list');
            this.$Container     = Elm.getElement('.quiqqer-products-productList-products');

            this.$BarSort      = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays  = Elm.getElement('.quiqqer-products-productList-sort-display');
            this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryGalery-catgory-more');

            if (!this.$CategoryMore) {
                this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryList-catgory-more');
            }


            this.$More   = Elm.getElements('.quiqqer-products-productList-products-more .button');
            this.$MoreFX = moofx(this.$More);

            this.setAttribute('categoryId', this.getElm().get('data-cid').toInt());
            this.setAttribute('project', this.getElm().get('data-project'));
            this.setAttribute('lang', this.getElm().get('data-lang'));
            this.setAttribute('siteId', this.getElm().get('data-siteid'));
            this.setAttribute('search', this.getElm().get('data-search'));

            // events
            this.$ButtonDetails.addEvent('click', this.detailView);
            this.$ButtonGalery.addEvent('click', this.galeryView);
            this.$ButtonList.addEvent('click', this.listView);

            switch (this.getAttribute('view')) {
                case 'details':
                    this.$ButtonDetails.addClass('active');
                    break;
                case 'galery':
                    this.$ButtonGalery.addClass('active');
                    break;
                case 'list':
                    this.$ButtonList.addClass('active');
                    break;
            }

            // categories
            if (this.$CategoryMore) {
                this.$CategoryMore.addEvent('click', this.showAllCategories);
            }

            // sort
            if (this.$BarSort) {
                this.$BarSort.set('html', '');

                this.$Sort = new QUISelect({
                    showIcons      : false,
                    placeholderText: 'Sortieren nach...'
                }).inject(this.$BarSort);

                this.$Sort.appendChild('Name aufsteigen', 'name');
                this.$Sort.appendChild('Name abssteigend', 'name');
                // this.$Sort.appendChild('Preis aufsteigen', 'name');
                // this.$Sort.appendChild('Preis abssteigend', 'name');

                this.$BarSort.setStyle('display', null);
            }

            if (this.$BarDisplays) {
                this.$BarDisplays.setStyle('display', null);
            }

            this.$parseElements(Elm);

            if (this.$More) {
                this.$More.addEvent('click', function () {
                    if (!this.$More.hasClass('disabled')) {
                        this.next();
                    }
                }.bind(this));

                this.$More.removeClass('disabled');
            }

            // bind to the search
            if (!this.getAttribute('search')) {
                return;
            }

            var SearchNode = document.getElement(
                '[data-name="' + this.getAttribute('search') + '"]'
            );

            if (SearchNode && SearchNode.get('data-quiid')) {
                var SearchForm = QUI.Controls.getById(SearchNode.get('data-quiid'));

                if (SearchForm) {
                    SearchForm.addEvent('change', function () {
                        self.$fields   = SearchForm.getFieldValues();
                        self.$freetext = SearchForm.getFreeTextSearch();
                        self.$clearContainer().then(self.$loadData.bind(self));
                    });

                    return;
                }

                console.log(SearchNode);
            }
        },

        /**
         * Render the next products
         *
         * @return {Promise}
         */
        next: function () {
            var self    = this,
                size    = this.$More.getSize(),
                LastRow = this.getElm().getElement('[data-row]:last-child'),
                nextRow = LastRow.get('data-row').toInt() + 1;

            this.$More.addClass('disabled');

            this.$More.setStyles({
                height  : size.y,
                overflow: 'hidden',
                width   : size.x
            });

            return new Promise(function (resolve) {
                self.$MoreFX.animate({
                    color: 'transparent'
                }, {
                    duration: 250,
                    callback: function () {
                        var oldButtonText = self.$More.get('text');

                        if (self.$More) {
                            self.$More.set('html', '<span class="fa fa-spinner fa-spin"></span>');
                            self.$More.setStyle('color', null);
                            self.$More.addClass('loading');
                        }

                        self.$loadData(nextRow).then(function (data) {
                            if (self.$More) {
                                self.$More.set({
                                    html  : oldButtonText,
                                    styles: {
                                        width: null
                                    }
                                });
                            }

                            if ("more" in data && data.more === false) {
                                self.$hideMoreButton();
                            } else {
                                self.$showMoreButton();
                            }

                            if (self.$More) {
                                self.$More.removeClass('loading');
                            }

                        }).then(function () {
                            return self.$scrollToLastRow();
                        }).then(resolve);
                    }
                });
            });
        },

        /**
         * Change to galery view
         *
         * @return {Promise}
         */
        galeryView: function () {
            if (!this.$sortingEnabled) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonGalery.addClass('active');
            this.setAttribute('view', 'galery');

            return this.$clearContainer().then(
                this.$loadData.bind(this)
            ).then(
                this.$showContainer.bind(this)
            );
        },

        /**
         * Change to detail view
         *
         * @return {Promise}
         */
        detailView: function () {
            if (!this.$sortingEnabled) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonDetails.addClass('active');
            this.setAttribute('view', 'detail');

            return this.$clearContainer().then(
                this.$loadData.bind(this)
            ).then(
                this.$showContainer.bind(this)
            );
        },

        /**
         * Change to list view
         *
         * @return {Promise}
         */
        listView: function () {
            if (!this.$sortingEnabled) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonList.addClass('active');
            this.setAttribute('view', 'list');

            return this.$clearContainer().then(
                this.$loadData.bind(this)
            ).then(
                this.$showContainer.bind(this)
            );
        },

        /**
         * remove all active class from the buttons
         */
        resetButtons: function () {
            this.$ButtonDetails.removeClass('active');
            this.$ButtonGalery.removeClass('active');
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
                var searchParams = {
                    fields  : self.$fields,
                    freetext: self.$freetext
                };

                Ajax.get('package_quiqqer_products_ajax_controls_categories_productList', function (result) {
                    if (!result) {
                        resolve(result);
                        return;
                    }

                    if ("more" in result && result.more === false) {
                        self.$hideMoreButton();
                    } else {
                        self.$showMoreButton();
                    }

                    var Ghost = new Element('div', {
                        html: result.html
                    });

                    var Row = Ghost.getElement('[data-row]');

                    // no results
                    if (!Row) {
                        self.disableSorting();
                        Container.set('html', '');
                        Ghost.inject(Container);

                        self.$showContainer().then(function () {
                            resolve(result);
                        });

                        return;
                    }

                    if (row === 0) {
                        Container.set('html', '');
                    }

                    self.enableSorting();

                    Row.setStyles({
                        width: '100%'
                    });

                    Row.inject(Container);

                    self.$showContainer().then(function () {
                        resolve(result);
                    });

                }, {
                    'package'   : 'quiqqer/products',
                    view        : view,
                    sort        : sort,
                    row         : row,
                    categoryId  : categoryId,
                    project     : JSON.encode({
                        name: self.getAttribute('project'),
                        lang: self.getAttribute('lang')
                    }),
                    siteId      : self.getAttribute('siteId'),
                    searchParams: JSON.encode(searchParams)
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
                    '.quiqqer-products-productGalery-products-product-details'
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

            this.$Container.setStyle('height', this.$Container.getSize().y);

            return new Promise(function (resolve) {

                moofx(self.$Container).animate({
                    opacity: 0
                }, {
                    duration: 500,
                    callback: function () {
                        rows.destroy();
                        resolve();
                    }
                });

            }.bind(this));
        },

        /**
         * Show the container
         *
         * @returns {Promise}
         */
        $showContainer: function () {
            var self = this,
                rows = this.$Container.getElements('[data-row]'),
                size = this.$Container.getScrollSize();

            var rowSize = rows.getSize().map(function (o) {
                return o.y;
            }).sum();

            return new Promise(function (resolve) {

                moofx(self.$Container).animate({
                    height : rowSize < size.y ? rowSize : size.y,
                    opacity: 1
                }, {
                    duration: 500,
                    callback: function () {
                        resolve();
                    }
                });

            }.bind(this));
        },

        /**
         * Scroll to the last row
         * @returns {Promise}
         */
        $scrollToLastRow: function () {
            var Row = this.$Container.getElement('[data-row]:last-child');

            return new Promise(function (resolve) {
                new Fx.Scroll(window.document).start(
                    0,
                    Row.getPosition().y - 100
                ).chain(resolve);
            });
        },

        /**
         * hide the more button
         *
         * @return {Promise}
         */
        $hideMoreButton: function () {
            if (!this.$More) {
                return Promise.resolve();
            }

            this.$More.addClass('disabled');
            this.$More.setStyle('cursor', 'default');

            return new Promise(function (resolve) {
                this.$MoreFX.animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * shows the more button
         *
         * @return {Promise}
         */
        $showMoreButton: function () {
            if (!this.$More) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                this.$MoreFX.animate({
                    opacity: 1
                }, {
                    duration: 200,
                    callback: function () {
                        this.$More.removeClass('disabled');
                        this.$More.setStyle('cursor', null);
                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * disable all sorting functionality
         */
        disableSorting: function () {
            this.$sortingEnabled = false;

            this.$ButtonDetails.addClass('disabled');
            this.$ButtonGalery.addClass('disabled');
            this.$ButtonList.addClass('disabled');

            this.$ButtonDetails.removeClass('active');
            this.$ButtonGalery.removeClass('active');
            this.$ButtonList.removeClass('active');

            this.$Sort.disable();
        },

        /**
         * enable all sorting functionality
         */
        enableSorting: function () {
            this.$sortingEnabled = true;

            this.$ButtonDetails.removeClass('disabled');
            this.$ButtonGalery.removeClass('disabled');
            this.$ButtonList.removeClass('disabled');

            this.$Sort.enable();
        },

        /**
         * Execute search and display it
         *
         * @param {Object} params - search fields
         * @returns {Promise}
         */
        search: function (params) {
            return Search.search(
                this.getAttribute('siteId'),
                {
                    name: this.getAttribute('project'),
                    lang: this.getAttribute('lang')
                },
                params
            ).then(function (result) {

                console.warn(result);

            });
        },

        /**
         * Shows the product details
         *
         * @param {HTMLDivElement} Product
         */
        showProductDetails: function (Product) {
            console.log(Product);
        },

        /**
         * Show all categories if some categories are hidden
         */
        showAllCategories: function () {
            var Categories = this.getElm().getElement(
                '.quiqqer-products-productList-categories'
            );
            console.log(Categories);
            if (!Categories) {
                return;
            }

            var hiddenChildren = Categories.getElements(
                '.quiqqer-products-catgory__hide'
            );

            if (!hiddenChildren.length) {
                return;
            }

            var size = Categories.getSize();

            Categories.setStyles({
                height  : size.y,
                overflow: 'hidden'
            });

            hiddenChildren.removeClass('quiqqer-products-catgory__hide');

            var wantedSizes = Categories.getScrollSize();

            if (this.$CategoryMore) {
                moofx(this.$CategoryMore).animate({
                    height : 0,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        this.$CategoryMore.setStyle('display', 'none');
                    }.bind(this)
                });
            }

            moofx(Categories).animate({
                height: wantedSizes.y
            }, {
                duratiobn: 250,
                callback : function () {

                }
            });
        }
    });
});
