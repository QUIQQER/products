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
 * @require package/quiqqer/products/bin/controls/search/SearchField
 * @require Ajax
 * @require Locale
 * @require package/quiqqer/products/bin/controls/frontend/category/ProductListFilter
 *
 * @event onFilterChange [self]
 */

// uri require
require.config({
    paths: {
        'URI'     : URL_OPT_DIR + 'bin/uri.js/src/URI',
        'IPv6'    : URL_OPT_DIR + 'bin/uri.js/src/IPv6',
        'punycode': URL_OPT_DIR + 'bin/uri.js/src/punycode',

        'SecondLevelDomains': URL_OPT_DIR + 'bin/uri.js/src/SecondLevelDomains'
    }
});

define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'qui/controls/buttons/Button',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/Search',
    'package/quiqqer/products/bin/controls/search/SearchField',
    'Ajax',
    'Locale',
    'URI',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListField'

], function (QUI, QUIControl, QUISelect, QUIButton, QUIElementUtils,
             Search, SearchField, QUIAjax, QUILocale, URI, ProductListFilter, ProductListField) {

    "use strict";

    var lg = 'quiqqer/products';

    // history popstate for mootools
    Element.NativeEvents.popstate = 2;

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
            '$onInject',
            '$onFilterChange'
        ],

        options: {
            categoryId: false,
            view      : 'gallery',
            sort      : false,
            project   : false,
            lang      : false,
            siteId    : false,
            autoload  : true
        },

        initialize: function (options) {
            this.parent(options);

            this.$load = false;


            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList    = null;
            this.$BarSort       = null;
            this.$BarDisplays   = null;
            this.$More          = null;
            this.$Sort          = null;

            this.$FXContainer = null;
            this.$FXLoader    = null;
            this.$FXMore      = null;

            this.$Container         = null;
            this.$ContainerLoader   = null;
            this.$CategoryMore      = null;
            this.$FilterDisplay     = null;
            this.$FilterResultInfo  = null;
            this.$FilterClearButton = null;
            this.$FilterList        = null;
            this.$FilterFieldList   = null;

            this.$fields       = {};
            this.$selectFilter = [];
            this.$selectFields = [];
            this.$categories   = [];

            this.$sortingEnabled       = true;
            this.$__readWindowLocation = false;

            this.$moreButtonIsVisible = false;
            this.$moreButtonClicked   = 0;
            this.$loadingMore         = false;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });

            QUI.addEvent('resize', function () {
                this.$recalcFilterDimensions();
            }.bind(this));
        },

        /**
         * Execute a search and display the results
         */
        execute: function () {
            this.$onFilterChange();
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
            var Elm = this.getElm(),
                cid = Elm.get('data-productlist-id');

            if (parseInt(Elm.get('data-autoload')) === 0) {
                this.setAttribute('autoload', false);
            }

            this.$ButtonDetails = Elm.getElements('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGallery = Elm.getElements('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList    = Elm.getElements('.quiqqer-products-productList-sort-display-list');
            this.$Container     = Elm.getElement('.quiqqer-products-productList-products-container');
            this.$ContainerReal = Elm.getElement('.quiqqer-products-productList-products-container-real');

            this.$FilterFL          = Elm.getElement('.quiqqer-products-productList-fl');
            this.$FilterDisplay     = Elm.getElement('.quiqqer-products-productList-filterList');
            this.$FilterList        = Elm.getElement('.quiqqer-products-productList-filterList-list');
            this.$FilterFieldList   = Elm.getElement('.quiqqer-products-productList-filterList-fields');
            this.$FilterResultInfo  = Elm.getElement('.quiqqer-products-productList-resultInfo-text');
            this.$FilterClearButton = Elm.getElement('.quiqqer-products-productList-resultInfo-clearbtn');

            this.$FilterContainer = document.getElement('.quiqqer-products-productList-filter-container-' + cid);

            if (Elm.get('data-categories') && Elm.get('data-categories') !== '') {
                Elm.get('data-categories').split(',').each(function (categoryId) {
                    this.$categories.push(parseInt(categoryId));
                }.bind(this));
            }


            if (!this.$Container) {
                return;
            }

            this.$ContainerLoader = new Element('div', {
                'class': 'quiqqer-products-productList-loader',
                'html' : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    display: 'none',
                    opacity: 0
                }
            }).inject(this.$Container);

            this.$FXContainer = moofx(this.$Container);
            this.$FXLoader    = moofx(this.$ContainerLoader);

            // delete noscript tags -> because CSS
            Elm.getElements('noscript').destroy();

            // mobile touch css helper
            if (!!("ontouchstart" in document.documentElement)) {
                Elm.addClass("touch");
            }

            // filter
            if (this.$FilterContainer) {
                var inner = this.$FilterContainer.get('html');

                this.$FilterContainer.set('html', '');

                new Element('div', {
                    html  : inner,
                    styles: {
                        'float'      : 'left',
                        paddingBottom: 20,
                        width        : '100%'
                    }
                }).inject(this.$FilterContainer);
            }

            this.$BarFilter    = Elm.getElement('.quiqqer-products-productList-sort-filter');
            this.$BarSort      = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays  = Elm.getElement('.quiqqer-products-productList-sort-display');
            this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryGallery-catgory-more');

            if (!this.$CategoryMore) {
                this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryList-catgory-more');
            }

            if (this.$FilterClearButton) {
                this.$FilterClearButton.addEvent('click', this.clearFilter);
            }

            if (this.$FilterContainer) {
                this.$FilterContainer.setStyle(
                    'height',
                    this.$FilterContainer.getSize().y
                );

                this.$renderFilter();
                this.$renderFilterFields();
            }

            if (this.$BarFilter) {
                this.$BarFilter.getElement('.button').addEvent('click', this.toggleFilter);
                this.$BarFilter.setStyle('display', null);
            } else if (this.$FilterContainer) {
                // open filter, if no filter button exists
                moofx(this.$FilterContainer).animate({
                    background: 'transparent'
                }, {
                    duration: 200,
                    callback: function () {
                        this.$FilterContainer.addClass(
                            'quiqqer-products-productList-filterContainerLoaded'
                        );
                        this.$FilterContainer.removeClass(
                            'quiqqer-products-productList-filterContainerLoading'
                        );

                        this.$FilterContainer.setStyles({
                            height: null
                        });
                    }.bind(this)
                });
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
                var Select  = this.$BarSort.getElement('select'),
                    options = Select.getElements('option');

                this.$Sort = new QUISelect({
                    showIcons      : false,
                    placeholderText: QUILocale.get(lg, 'product.list.sort.placeholder'),
                    events         : {
                        onChange: this.$onFilterChange
                    }
                });

                for (var i = 0, len = options.length; i < len; i++) {
                    this.$Sort.appendChild(
                        options[i].get('html'),
                        options[i].get('value')
                    );
                }

                this.$BarSort.set('html', '');
                this.$Sort.inject(this.$BarSort);
                this.$BarSort.setStyle('display', null);
            }

            if (this.$BarDisplays) {
                this.$BarDisplays.setStyle('display', null);
            }

            this.$parseElements(Elm);

            if (this.$More) {
                this.$FXMore = moofx(this.$More.getParent());

                this.$More.addEvent('click', function () {
                    if (!this.$More.hasClass('disabled')) {
                        this.$moreButtonClicked++;
                        this.next();
                    }
                }.bind(this));

                this.$showMoreButton();
            }

            // more button auto loading
            QUI.addEvent('scroll', function () {
                if (!this.$More) {
                    return;
                }

                if (this.$moreButtonClicked < 3) {
                    return;
                }

                if (!this.$moreButtonIsVisible) {
                    return;
                }

                if (this.$loadingMore) {
                    return;
                }

                var isInView = QUIElementUtils.isInViewport(this.$More);

                if (isInView) {
                    this.next();
                }
            }.bind(this));

            // read url
            window.addEvent('popstate', function () {
                this.$load = false;
                this.$readWindowLocation().then(function () {
                    this.$onFilterChange();
                    this.$load = true;
                }.bind(this));
            }.bind(this));


            if (typeof Pace !== 'undefined') {
                var loaded = false;

                Pace.on('done', function () {
                    if (loaded) {
                        return;
                    }
                    this.$readWindowLocation().then(function () {
                        this.$load = true;
                        loaded     = true;

                        if (this.getAttribute('autoload')) {
                            this.$onFilterChange();
                            this.$__readWindowLocation = false;
                        }
                    }.bind(this));
                }.bind(this));
                return;
            }

            (function () {
                this.$readWindowLocation().then(function () {
                    this.$load = true;
                    if (this.getAttribute('autoload')) {
                        this.$onFilterChange();
                        this.$__readWindowLocation = false;
                    }
                }.bind(this));
            }).delay(500, this);
        },

        /**
         * read the url params and set the params to the product list
         *
         * @returns {Promise}
         */
        $readWindowLocation: function () {
            return new Promise(function (resolve) {
                this.$__readWindowLocation = true;

                var Url    = URI(window.location),
                    search = Url.search(true);

                if (!Object.getLength(search)) {
                    resolve();
                    return;
                }

                // fields
                if ("f" in search) {
                    var fieldList = this.getElm().getElements(
                        '.quiqqer-products-search-field'
                    ).map(function (Field) {
                        return QUI.Controls.getById(Field.get('data-quiid'));
                    });

                    try {
                        var Field, fieldId;

                        var fieldParams    = JSON.decode(search.f),
                            findFilterById = function (fieldId) {
                                for (var f in fieldList) {
                                    if (fieldList.hasOwnProperty(f) &&
                                        fieldList[f].getFieldId() == fieldId) {
                                        return fieldList[f];
                                    }
                                }
                                return false;
                            };

                        for (fieldId in fieldParams) {
                            if (!(fieldParams.hasOwnProperty(fieldId))) {
                                continue;
                            }

                            Field = findFilterById(fieldId);

                            if (Field) {
                                Field.setSearchValue(fieldParams[fieldId]);
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }

                // tags
                var tags = [];

                if ("t" in search) {
                    tags = search.t.split(',');
                }

                tags.each(this.addFilter.bind(this));

                // sort
                if ("sortBy" in search && "sortOn" in search) {
                    this.$Sort.setValue(
                        search.sortOn + ' ' + search.sortBy
                    );
                }

                // view
                if ("v" in search) {
                    switch (search.v) {
                        case 'detail':
                            this.resetButtons();
                            this.$ButtonDetails.addClass('active');
                            this.setAttribute('view', search.v);
                            break;

                        case 'list':
                            this.resetButtons();
                            this.$ButtonList.addClass('active');
                            this.setAttribute('view', search.v);
                            break;

                        case 'gallery':
                            this.resetButtons();
                            this.$ButtonGallery.addClass('active');
                            this.setAttribute('view', search.v);
                            break;
                    }
                }

                resolve();

            }.bind(this));
        },

        /**
         * write a history entry
         */
        $setWindowLocation: function () {
            if (!this.$load) {
                return;
            }

            if (this.$__readWindowLocation) {
                return;
            }

            if (!("history" in window)) {
                return;
            }

            // set history
            var history      = {},
                searchParams = this.$getSearchParams();

            if (searchParams.sortBy !== '') {
                history.sortBy = searchParams.sortBy;
            }

            if (searchParams.sortOn !== '') {
                history.sortOn = searchParams.sortOn;
            }

            if (searchParams.freetext !== '') {
                history.search = searchParams.freetext;
            }

            if (searchParams.search !== '' && history.search === '') {
                history.search = searchParams.search;
            }

            if (searchParams.tags.length) {
                history.t = searchParams.tags.join(',');
            }

            switch (this.getAttribute('view')) {
                case 'detail':
                case 'list':
                    history.v = this.getAttribute('view');
                    break;
            }

            if (searchParams.fields) {
                var fields = Object.filter(searchParams.fields, function (value) {
                    return value !== '';
                });

                if (fields) {
                    history.f = JSON.encode(fields);
                }
            }

            var url = location.pathname + '?' + Object.toQueryString(history);

            if ("origin" in location) {
                url = location.origin + url;
            }

            window.history.pushState({}, "", url);
        },

        /**
         * Render the next products
         *
         * @return {Promise}
         */
        next: function () {
            var self = this,
                size = this.$More.getSize();

            this.$More.addClass('disabled');
            this.$loadingMore = true;

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

                self.$renderSearch(true).then(function (data) {
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

                    self.$loadingMore = false;

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

            return this.$clearContainer()
                .then(this.$renderSearch.bind(this))
                .then(this.$showContainer.bind(this))
                .then(this.$setWindowLocation.bind(this));
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

            return this.$clearContainer()
                .then(this.$renderSearch.bind(this))
                .then(this.$showContainer.bind(this))
                .then(this.$setWindowLocation.bind(this));
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

            return this.$clearContainer()
                .then(this.$renderSearch.bind(this))
                .then(this.$showContainer.bind(this))
                .then(this.$setWindowLocation.bind(this));
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
         * Load the data view and return the searched products as html
         *
         * @param {Boolean} [next] - wanted more articles, default is false
         * @return {Promise}
         */
        $renderSearch: function (next) {
            next = typeof next !== 'undefined';

            var self          = this,
                view          = this.getAttribute('view'),
                sort          = this.getAttribute('sort'),
                categoryId    = this.getAttribute('categoryId'),
                ContainerReal = this.$ContainerReal,
                articles      = this.$ContainerReal.getElements('article').length;

            return new Promise(function (resolve) {
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

                    // button events
                    Ghost.getElements(
                        '.quiqqer-products-product-button-purchase'
                    ).addEvent('click', function (event) {
                        var Target    = event.target,
                            Article   = Target.getParent('article'),
                            productId = Article.get('data-pid');

                        Target.removeClass('fa-envelope');
                        Target.addClass('fa-spinner fa-spin');

                        require([
                            'package/quiqqer/watchlist/bin/controls/frontend/PurchaseWindow',
                            'package/quiqqer/watchlist/bin/classes/Product'
                        ], function (Purchase, WatchlistProduct) {
                            var Product = new WatchlistProduct({
                                id    : productId,
                                events: {
                                    onChange: self.$onProductChange
                                }
                            });

                            new Purchase({
                                products: [Product]
                            }).open();

                            Target.removeClass('fa-spinner');
                            Target.removeClass('fa-spin');
                            Target.addClass('fa-envelope');
                        });
                    });

                    Ghost.getElements(
                        '.quiqqer-products-product-button-add'
                    ).addEvent('click', function (event) {
                        var Target    = event.target,
                            Article   = Target.getParent('article'),
                            productId = Article.get('data-pid');

                        Target.removeClass('fa-plus');
                        Target.addClass('fa-spinner fa-spin');

                        require([
                            'package/quiqqer/watchlist/bin/Watchlist'
                        ], function (Watchlist) {
                            Watchlist.addProduct(productId).then(function () {
                                Target.removeClass('fa-spinner');
                                Target.removeClass('fa-spin');
                                Target.addClass('fa-check');

                                (function () {
                                    Target.removeClass('fa-check');
                                    Target.addClass('fa-plus');
                                }).delay(1000, this);
                            });
                        });
                    });


                    var Prom = Promise.resolve();

                    if (next === false) {
                        Prom = self.$clearContainer();
                    }

                    Prom.then(function () {
                        self.enableSorting();
                        Ghost.getElements('article').inject(ContainerReal);

                        return self.$showContainer();
                    }).then(function () {
                        resolve(result);
                    });
                }, {
                    'package'   : 'quiqqer/products',
                    view        : view,
                    sort        : sort,
                    articles    : articles,
                    next        : next ? 1 : 0,
                    categoryId  : categoryId,
                    project     : JSON.encode({
                        name: self.getAttribute('project'),
                        lang: self.getAttribute('lang')
                    }),
                    siteId      : self.getAttribute('siteId'),
                    searchParams: JSON.encode(self.$getSearchParams())
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
         * Return the current search params
         *
         * @returns {
         * {tags: (*|Array),
         * freetext: string,
         * fields: *,
         * sortOn: string,
         * sortBy: string}
         * }
         */
        $getSearchParams: function () {
            var i, len, Field;

            var fields     = {},
                categories = this.$categories,
                tags       = [],
                sortOn     = '',
                sortBy     = '',
                freetext   = '';

            if (this.$FilterContainer) {
                var value;
                var fieldNodes = this.$FilterContainer.getElements('.quiqqer-products-search-field');

                for (i = 0, len = fieldNodes.length; i < len; i++) {
                    Field = QUI.Controls.getById(fieldNodes[i].get('data-quiid'));
                    value = Field.getSearchValue();

                    if (!Field.isReady()) {
                        continue;
                    }

                    if (value !== '' && value !== false) {
                        fields[Field.getFieldId()] = Field.getSearchValue();
                    }
                }
            }

            if (this.$FilterList) {
                tags = this.$FilterList.getElements('[data-tag]').map(function (Elm) {
                    return Elm.get('data-tag');
                });
            }

            if (this.$Sort && this.$Sort.getValue() && this.$Sort.getValue() !== '') {
                var sort = this.$Sort.getValue().split(' ');

                sortBy = sort[1];
                sortOn = sort[0];
            }

            if (window.location.search) {
                var Url   = URI(window.location),
                    query = Url.query(true);


                if ("search" in query) {
                    freetext = query.search;
                }
            }

            return {
                tags      : tags,
                freetext  : freetext,
                fields    : fields,
                categories: categories,
                sortOn    : sortOn,
                sortBy    : sortBy
            };
        },

        /**
         * Delete all products
         *
         * @returns {Promise}
         */
        $clearContainer: function () {
            var self     = this,
                articles = this.$Container.getElements('article');

            this.$Container.setStyle(
                'height',
                this.$Container.getSize().y
            );

            this.$FXLoader.animate({
                opacity: 0
            }, {
                callback: function () {
                    self.$ContainerLoader.setStyle('display', 'none');
                }
            });

            return new Promise(function (resolve) {
                self.$FXContainer.animate({
                    opacity: 0
                }, {
                    duration: 500,
                    callback: function () {
                        articles.destroy();
                        resolve();
                    }
                });
            });
        },

        /**
         * Show the container
         *
         * @returns {Promise}
         */
        $showContainer: function () {
            return new Promise(function (resolve) {
                this.$FXContainer.animate({
                    height : this.$ContainerReal.getSize().y,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Show the container
         *
         * @returns {Promise}
         */
        $hideContainer: function () {
            return new Promise(function (resolve) {
                this.$FXContainer.animate({
                    opacity: 0
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Hide the container and show a loader in the container
         *
         * @returns {Promise}
         */
        $hideContainerWithLoader: function () {
            var self = this;

            var LoaderAnimation = new Promise(function (resolve) {
                self.$ContainerLoader.setStyle('opacity', 0);
                self.$ContainerLoader.setStyle('display', null);
                self.$FXLoader.animate({
                    opacity: 1
                }, {
                    duration: 200,
                    callback: resolve
                });

            });

            return Promise.all([
                LoaderAnimation,
                this.$hideContainer()
            ]);
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
            this.$moreButtonIsVisible = false;

            return new Promise(function (resolve) {
                this.$FXMore.animate({
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
                this.$FXMore.animate({
                    opacity: 1
                }, {
                    duration: 200,
                    callback: function () {
                        this.$More.removeClass('disabled');
                        this.$More.setStyle('cursor', null);
                        this.$moreButtonIsVisible = true;
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

            if (this.$Sort) {
                this.$Sort.disable();
            }
        },

        /**
         * enable all sorting functionality
         */
        enableSorting: function () {
            this.$sortingEnabled = true;

            this.$ButtonDetails.removeClass('disabled');
            this.$ButtonGallery.removeClass('disabled');
            this.$ButtonList.removeClass('disabled');

            if (this.$Sort) {
                this.$Sort.enable();
            }
        },

        /**
         * Execute search and display it
         *
         * @param {Object} params - search fields
         * @returns {Promise}
         */
        search: function (params) {
            return Search.search(this.getAttribute('siteId'), {
                    name: this.getAttribute('project'),
                    lang: this.getAttribute('lang')
                },
                params
            );
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
         * FILTER
         */

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
         * recalc the filter container dimensions
         *
         * @returns {Promise}
         */
        $recalcFilterDimensions: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            var opacity = this.$FilterContainer.getStyle('opacity').toInt();

            if (!opacity) {
                return Promise.resolve();
            }

            return this.showFilter();
        },

        /**
         * show the filter box
         *
         * @returns {Promise}
         */
        showFilter: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            if (this.$BarFilter) {
                var Opener = this.$BarFilter.getElement(
                    '.quiqqer-products-productList-sort-filter-opener'
                );

                Opener.removeClass('fa-angle-down');
                Opener.addClass('fa-angle-double-down');
            }

            var scrollHeight = this.$FilterContainer.getFirst('div').getComputedSize().totalHeight,
                height       = this.$FilterContainer.getSize().y;

            if (scrollHeight == height) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                var padding = '20px 0';

                if (this.$FilterContainer.getParent('.content-headerpage-multible-left')) {
                    padding = 0;
                }

                moofx(this.$FilterContainer).animate({
                    height : scrollHeight,
                    opacity: 1,
                    padding: padding
                }, {
                    duration: 250,
                    callback: function () {
                        resolve();
                    }
                });
            }.bind(this));
        },

        /**
         * hide the filter box
         *
         * @returns {Promise}
         */
        hideFilter: function () {
            if (!this.$FilterContainer) {
                return Promise.resolve();
            }

            var Opener = this.$BarFilter.getElement(
                '.quiqqer-products-productList-sort-filter-opener'
            );

            Opener.removeClass('fa-angle-double-down');
            Opener.addClass('fa-angle-down');

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
         * render the filter and field select boxes
         */
        $renderFilter: function () {
            if (!this.$FilterContainer) {
                return;
            }

            var c, i, len, clen, options, searchdata, Field, Control, Filter, Title, Select;

            // standard
            var filter = this.$FilterContainer.getElements(
                '.quiqqer-products-productList-filter-entry'
            );

            var change = function (values, Select) {
                for (var i = 0, len = values.length; i < len; i++) {
                    this.addFilter(values[i]);
                }

                // removing deleted filter
                var uncheckedFilter = Select.getChildren().filter(function (Item) {
                    return !Item.isChecked();
                }).map(function (Item) {
                    return Item.getAttribute('value');
                });

                for (i = 0, len = uncheckedFilter.length; i < len; i++) {
                    this.removeFilter(uncheckedFilter[i]);
                }
            }.bind(this);

            for (i = 0, len = filter.length; i < len; i++) {
                Filter = filter[i];
                Select = Filter.getElement('select');
                Title  = Filter.getElement(
                    '.quiqqer-products-productList-filter-entry-title'
                );

                // field
                if (!Select) {
                    // search fields
                    // Title.destroy();
                    Select     = Filter.getElement('input');
                    searchdata = null;

                    try {
                        searchdata = JSON.decode(Select.get('data-searchdata'));
                    } catch (e) {
                    }

                    Field = new SearchField({
                        fieldid   : Select.get('data-fieldid'),
                        searchtype: Select.get('data-searchtype'),
                        searchdata: searchdata,
                        title     : Title.get('text').trim(),
                        events    : {
                            onChange: this.$onFilterChange
                        }
                    }).inject(Filter);

                    this.$selectFields.push(Field);
                    Select.destroy();
                    continue;
                }

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

                Select.destroy();
                Control.inject(Filter);

                this.$selectFilter.push(Control);
            }
        },

        /**
         * Render the fields filter
         */
        $renderFilterFields: function () {
            if (!this.$FilterFieldList) {
                return;
            }

            var self    = this,
                onReady = function () {
                    new ProductListField({
                        Field: this
                    }).inject(self.$FilterFieldList);
                };

            this.$FilterFieldList.set('html', '');

            for (var i = 0, len = this.$selectFields.length; i < len; i++) {
                if (this.$selectFields[i].isReady() &&
                    this.$selectFields[i].getSearchValue()) {

                    new ProductListField({
                        Field: this.$selectFields[i]
                    }).inject(this.$FilterFieldList);

                    continue;
                }

                this.$selectFields[i].addEvent('ready', onReady.bind(this.$selectFields[i]));
            }
        },

        /**
         * Show the filter text
         * @returns {Promise}
         */
        showFilterDisplay: function () {
            if (!this.$FilterFL) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                var realheight = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: 'absolute',
                    height  : null
                });

                var height = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: null,
                    height  : realheight
                });

                moofx(this.$FilterFL).animate({
                    height : height,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: function () {
                        this.$FilterFL.setStyle('overflow', null);
                        this.$FilterFL.setStyle('height', null);

                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * Show the filter text
         * @returns {Promise}
         */
        hideFilterDisplay: function () {
            if (!this.$FilterFL) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                this.$FilterFL.setStyle('overflow', 'hidden');

                moofx(this.$FilterFL).animate({
                    height : 0,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * clear all filters
         */
        clearFilter: function () {
            if (!this.$FilterList) {
                return;
            }

            this.$FilterList.set('html', '');

            var i, len;

            var uncheck = function (Child) {
                Child.uncheck();
            };

            for (i = 0, len = this.$selectFilter.length; i < len; i++) {
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
            if (!this.$FilterClearButton) {
                return;
            }

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

            new ProductListFilter({
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
            if (!this.$selectFilter) {
                return;
            }

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
            if (!this.$FilterResultInfo) {
                return;
            }

            var self              = this,
                searchParams      = this.$getSearchParams(),
                searchCountParams = searchParams;

            if (!this.$load) {
                // filter display
                if (searchParams.tags.length || Object.getLength(searchParams.fields)) {
                    self.showFilterDisplay();
                } else {
                    self.hideFilterDisplay();
                }

                return;
            }

            this.fireEvent('filterChangeBegin');

            this.$hideContainerWithLoader();

            this.$FilterResultInfo.set(
                'html',
                '<span class="fa fa-spinner fa-spin"></span>'
            );


            // if no tags, no result count display
            if (searchParams.tags.length || Object.getLength(searchParams.fields)) {
                moofx(this.$FilterResultInfo).animate({
                    opacity: 1
                }, {
                    duration: 200
                });
            } else {
                moofx(this.$FilterResultInfo).animate({
                    opacity: 0
                }, {
                    duration: 200
                });
            }

            if (searchParams.tags.length) {
                this.$FilterClearButton.setStyle('display', null);
            } else {
                this.$FilterClearButton.setStyle('display', 'none');
            }

            this.$setWindowLocation();

            // refresh display
            searchCountParams.count = true;

            if (typeof this.$refreshTimer !== 'undefined' && this.$refreshTimer) {
                clearTimeout(this.$refreshTimer)
            }

            this.$refreshTimer = (function () {
                this.search(searchCountParams).then(function (result) {

                    self.$FilterResultInfo.set('html', QUILocale.get(lg, 'product.list.result.count', {
                        count: result
                    }));

                    // filter display
                    if (searchParams.tags.length || Object.getLength(searchParams.fields)) {
                        self.showFilterDisplay();
                    } else {
                        self.hideFilterDisplay();
                    }

                    self.fireEvent('filterChange');

                    return self.$renderSearch();
                });
            }).delay(200, this);
        },

        /**
         * Add a category
         *
         * @param {Integer} categoryId
         */
        addCategory: function (categoryId) {
            categoryId = parseInt(categoryId);

            if (this.$categories.contains(categoryId)) {
                return;
            }

            this.$categories.push(categoryId);
            this.$onFilterChange();
        },

        /**
         * Add an array of categories
         *
         * @param categories
         */
        addCategories: function (categories) {
            if (typeOf(categories) != 'array') {
                return;
            }

            for (var i = 0, len = categories.length; i < len; i++) {
                if (!this.$categories.contains(categories[i])) {
                    this.$categories.push(categories[i]);
                }
            }

            this.$onFilterChange();
        },

        /**
         * Removes a category
         *
         * @param {Integer} categoryId
         */
        removeCategory: function (categoryId) {
            categoryId = parseInt(categoryId);

            if (!this.$categories.contains(categoryId)) {
                return;
            }

            this.$categories.erase(categoryId);
            this.$onFilterChange();
        },

        /**
         * Remove an array of categories
         *
         * @param categories
         */
        removeCategories: function (categories) {
            if (typeOf(categories) != 'array') {
                return;
            }

            for (var i = 0, len = categories.length; i < len; i++) {
                this.$categories.erase(categories[i]);
            }

            this.$onFilterChange();
        },

        /**
         * Removes all categories
         */
        clearCategories: function () {
            this.$categories = [];
            this.$onFilterChange();
        }
    });
});
