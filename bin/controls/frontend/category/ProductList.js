/**
 * Category view
 * Display a category with filters and search
 *
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require qui/controls/buttons/Button
 * @require package/quiqqer/products/bin/Search
 * @require Ajax
 * @require package/quiqqer/products/bin/controls/frontend/category/ProductListFilter
 *
 * @event onFilterChange [self]
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/Search',
    'Ajax',
    'Locale',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter'

], function (QUI, QUIControl, QUISelect, QUIButton, Search, QUIAjax, QUILocale, Filter) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductList',

        Binds: [
            'galleryView',
            'detailView',
            'listView',
            'next',
            'toggleFilter',
            'clearFilter',
            'showAllCategories',
            '$hideMoreButton',
            '$showMoreButton',
            'scrollToLastRow',
            '$onInject'
        ],

        options: {
            categoryId: false,
            view      : 'gallery',
            sort      : false,
            project   : false,
            lang      : false,
            siteId    : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList    = null;
            this.$BarSort       = null;
            this.$BarDisplays   = null;
            this.$More          = null;
            this.$Sort          = null;
            this.$MoreFX        = null;

            this.$CategoryMore      = null;
            this.$FilterResultInfo  = null;
            this.$FilterClearButton = null;

            this.$fields         = {};
            this.$selectFilter   = [];
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
            this.$ButtonGallery = Elm.getElements('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList    = Elm.getElements('.quiqqer-products-productList-sort-display-list');
            this.$Container     = Elm.getElement('.quiqqer-products-productList-products-container');
            this.$FilterList    = Elm.getElement('.quiqqer-products-productList-filterList-list');

            this.$FilterContainer   = Elm.getElement('.quiqqer-products-productList-filter-container');
            this.$FilterResultInfo  = Elm.getElement('.quiqqer-products-productList-resultInfo-text');
            this.$FilterClearButton = Elm.getElement('.quiqqer-products-productList-resultInfo-clearbtn');

            this.$BarFilter    = Elm.getElement('.quiqqer-products-productList-sort-filter');
            this.$BarSort      = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays  = Elm.getElement('.quiqqer-products-productList-sort-display');
            this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryGallery-catgory-more');

            if (!this.$CategoryMore) {
                this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryList-catgory-more');
            }

            this.$FilterClearButton.addEvent('click', this.clearFilter);

            if (this.$BarFilter) {
                this.$renderFilter();
                this.$BarFilter.getElement('.button').addEvent('click', this.toggleFilter);
                this.$BarFilter.setStyle('display', null);
            }

            this.$More = Elm.getElement('.quiqqer-products-productList-products-more .button');

            this.setAttribute('categoryId', this.getElm().get('data-cid').toInt());
            this.setAttribute('project', this.getElm().get('data-project'));
            this.setAttribute('lang', this.getElm().get('data-lang'));
            this.setAttribute('siteId', this.getElm().get('data-siteid'));
            this.setAttribute('search', this.getElm().get('data-search'));

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
                this.$MoreFX = moofx(this.$More.getParent());

                this.$More.addEvent('click', function () {
                    if (!this.$More.hasClass('disabled')) {
                        this.next();
                    }
                }.bind(this));

                this.$More.removeClass('disabled');
                this.$showMoreButton();
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

                console.error(SearchNode);
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

                }).then(resolve);
            });
        },

        /**
         * Change to gallery view
         *
         * @return {Promise}
         */
        galleryView: function () {
            if (!this.$sortingEnabled) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonGallery.addClass('active');
            this.setAttribute('view', 'gallery');

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
                var searchParams = {
                    fields  : self.$fields,
                    freetext: self.$freetext
                };

                QUIAjax.get('package_quiqqer_products_ajax_controls_categories_productList', function (result) {
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
            var Products = Node.getElements('.quiqqer-products-productGallery-products-product'),
                Details  = Node.getElements('.quiqqer-products-productGallery-products-product-details');

            Products.set({
                tabIndex: -1,
                styles  : {
                    outline: 'none'
                }
            });

            Details.addEvents({
                click: function (event) {
                    event.stop();

                    event.target.getParent(
                        '.quiqqer-products-productGallery-products-product'
                    ).focus();
                }
            });


            var Categories      = Node.getElements('.quiqqer-products-categoryGallery-catgory'),
                CategoryDetails = Node.getElements('.quiqqer-products-categoryGallery-catgory-details');

            Categories.set({
                tabIndex: -1,
                styles  : {
                    outline: 'none'
                }
            });

            CategoryDetails.addEvents({
                click: function (event) {
                    event.stop();

                    event.target.getParent(
                        '.quiqqer-products-categoryGallery-catgory'
                    ).focus();
                }
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
                    duration: 250,
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
        scrollToLastRow: function () {
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
            this.$ButtonGallery.addClass('disabled');
            this.$ButtonList.addClass('disabled');

            this.$ButtonDetails.removeClass('active');
            this.$ButtonGallery.removeClass('active');
            this.$ButtonList.removeClass('active');

            this.$Sort.disable();
        },

        /**
         * enable all sorting functionality
         */
        enableSorting: function () {
            this.$sortingEnabled = true;

            this.$ButtonDetails.removeClass('disabled');
            this.$ButtonGallery.removeClass('disabled');
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
        },

        /**
         * toggle filter display
         *
         * @returns {Promise}
         */
        toggleFilter: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            var opacity = this.$FilterContainer.getStyle('opacity').toInt();

            if (opacity) {
                return this.hideFilter();
            }

            return this.showFilter();
        },

        /**
         * show filter
         *
         * @returns {Promise}
         */
        showFilter: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            var height = this.$FilterContainer.getScrollSize().y;

            return new Promise(function (resolve) {
                moofx(this.$FilterContainer).animate({
                    height : height + 40,
                    opacity: 1,
                    padding: '20px 0'
                }, {
                    duration: 250,
                    callback: function () {
                        resolve();
                    }
                });
            }.bind(this));
        },

        /**
         * hide filter
         *
         * @returns {Promise}
         */
        hideFilter: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                moofx(this.$FilterContainer).animate({
                    height : 0,
                    opacity: 0,
                    padding: 0
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * render the filter
         */
        $renderFilter: function () {
            var c, i, len, clen, options, Control, Filter, Title, Select;
            var filter = this.$FilterContainer.getElements(
                '.quiqqer-products-productList-filter-entry'
            );

            var change = function (values) {
                for (var i = 0, len = values.length; i < len; i++) {
                    this.addFilter(values[i]);
                }
            }.bind(this);

            for (i = 0, len = filter.length; i < len; i++) {
                Filter = filter[i];
                Select = Filter.getElement('select');
                Title  = Filter.getElement(
                    '.quiqqer-products-productList-filter-entry-title'
                );

                options = Select.getElements('option');

                Control = new QUISelect({
                    placeholderText      : Title.get('html').trim(),
                    placeholderSelectable: false,
                    multiple             : true,
                    checkable            : true,
                    styles               : {
                        width: '100%'
                    },
                    events               : {
                        onChange: change
                    }
                });

                for (c = 0, clen = options.length; c < clen; c++) {
                    Control.appendChild(
                        options[c].get('html').trim(),
                        options[c].get('value').trim()
                    );
                }

                Filter.set('html', '');
                Control.inject(Filter);

                this.$selectFilter.push(Control);
            }
        },

        /**
         * clear all filters
         */
        clearFilter: function () {
            this.$FilterList.set('html', '');

            var uncheck = function (Child) {
                Child.uncheck();
            };

            for (var i = 0, len = this.$selectFilter.length; i < len; i++) {
                this.$selectFilter[i].getChildren().each(uncheck);
            }

            moofx(this.$FilterClearButton).animate({
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    this.$FilterClearButton.setStyle('display', 'none');
                }.bind(this)
            });

            this.$onFilterChange();
        },

        /**
         * Add a filter to the list
         *
         * @param {String} filter
         */
        addFilter: function (filter) {
            if (this.$FilterClearButton.getStyle('display') === 'none') {
                this.$FilterClearButton.setStyle('display', null);

                moofx(this.$FilterClearButton).animate({
                    opacity: 1
                }, {
                    duration: 200
                });
            }


            if (this.$FilterList.getElement('[data-tag="' + filter + '"]')) {
                return;
            }

            new Filter({
                tag   : filter,
                events: {
                    onDestroy: function (Filter) {
                        this.removeFilter(Filter.getAttribute('tag'));
                    }.bind(this)
                }
            }).inject(this.$FilterList);

            this.$onFilterChange();
        },

        /**
         * remove a filter
         *
         * @param {String} filter
         */
        removeFilter: function (filter) {
            for (var i = 0, len = this.$selectFilter.length; i < len; i++) {
                this.$selectFilter[i].unselectChild(filter);
                this.$FilterList.getElements('[data-tag="' + filter + '"]').destroy();
            }

            this.$onFilterChange();
        },

        /**
         * event on filter change
         */
        $onFilterChange: function () {
            this.fireEvent('filterChangeBegin');

            this.$FilterResultInfo.set('html', '<span class="fa fa-spinner fa-spin"></span>');

            var tags = this.$FilterList.getElements('[data-tag]').map(function (Elm) {
                return Elm.get('data-tag');
            });

            // if no tags, no result count display
            if (!tags.length) {
                moofx(this.$FilterResultInfo).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        this.$FilterClearButton.setStyle('display', 'none');
                    }.bind(this)
                });
            } else {
                this.$FilterClearButton.setStyle('display', null);

                moofx(this.$FilterResultInfo).animate({
                    opacity: 1
                }, {
                    duration: 200
                });
            }


            console.info(tags);

            QUIAjax.get('package_quiqqer_products_ajax_search_frontend_execute', function (result) {

                this.$FilterResultInfo.set('html', QUILocale.get(lg, 'product.list.result.count', {
                    count: result
                }));

                this.fireEvent('filterChange');

            }.bind(this), {
                'package'   : 'quiqqer/products',
                sort        : this.getAttribute('sort'),
                categoryId  : this.getAttribute('categoryId'),
                project     : JSON.encode({
                    name: this.getAttribute('project'),
                    lang: this.getAttribute('lang')
                }),
                siteId      : this.getAttribute('siteId'),
                searchParams: JSON.encode({
                    tags    : tags,
                    freetext: '',
                    count   : true
                })
            });
        }
    });
});
