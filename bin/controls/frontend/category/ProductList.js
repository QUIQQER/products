/**
 * Category view
 * Display a category with filters and search
 *
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onFilterChange [self]
 * @event onQuiqqerProductsOpenProduct [self, productId]
 * @event onQuiqqerProductsCloseProduct [self, productId]
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/Search',
    'package/quiqqer/products/bin/Stats',
    'package/quiqqer/products/bin/controls/search/SearchField',
    'Ajax',
    'Locale',
    'URI',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListField'

], function (QUI, QUIControl, QUISelect, QUIButton, QUILoader, QUIElementUtils,
             Search, Piwik, SearchField, QUIAjax, QUILocale, URI, ProductListFilter, ProductListField) {

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
            'openFilterMenu',
            'showAllCategories',
            '$hideMoreButton',
            '$showMoreButton',
            'scrollToLastRow',
            '$onInject',
            '$onFilterChange',
            '$setWindowLocation',
            '$readWindowLocation'
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

            this.$load                = false;
            this.$readLocationRunning = false;

            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList    = null;
            this.$BarSort       = null;
            this.$BarDisplays   = null;
            this.$More          = null;
            this.$Sort          = null;

            this.$FXContainer     = null;
            this.$FXContainerReal = null;
            this.$FXLoader        = null;
            this.$FXMore          = null;

            this.$Container        = null;
            this.$ContainerLoader  = null;
            this.$ProductContainer = null;

            this.$CategoryMore      = null;
            this.$FilterSort        = null;
            this.$FilterDisplay     = null;
            this.$FilterMobile      = null;
            this.$FilterResultInfo  = null;
            this.$FilterClearButton = null;
            this.$FilterList        = null;
            this.$FilterFieldList   = null;

            this.$FreeText          = null;
            this.$FreeTextContainer = null;

            this.$fields       = {};
            this.$selectFilter = [];
            this.$selectFields = [];
            this.$categories   = [];
            this.$tags         = [];
            this.$productId    = false;

            this.$sortingEnabled      = true;
            this.$moreButtonIsVisible = false;
            this.$moreButtonClicked   = 0;
            this.$loadingMore         = false;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });

            QUI.addEvent('resize', function () {
                this.$recalculateFilterDimensions();
            }.bind(this));
        },

        /**
         * Execute a search and display the results
         */
        execute: function () {
            this.$setWindowLocation();
        },

        /**
         * Has the product list a free text field?
         *
         * @returns {boolean}
         */
        hasFreeText: function () {
            return !!this.$FreeText;
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
                Elm  = this.getElm(),
                cid  = Elm.get('data-productlist-id');

            if (parseInt(Elm.get('data-autoload')) === 0) {
                this.setAttribute('autoload', false);
            }

            if (Elm.get('data-sort')) {
                this.setAttribute('sort', Elm.get('data-sort'));
            }

            var Url    = URI(window.location),
                search = Url.search(true);

            if ("p" in search) {
                this.$productId = parseInt(search.p);
            }

            this.$ButtonDetails = Elm.getElements('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGallery = Elm.getElements('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList    = Elm.getElements('.quiqqer-products-productList-sort-display-list');
            this.$Container     = Elm.getElement('.quiqqer-products-productList-products-container');
            this.$ContainerReal = Elm.getElement('.quiqqer-products-productList-products-container-real');

            this.$FilterFL          = Elm.getElement('.quiqqer-products-productList-fl');
            this.$FilterSort        = Elm.getElement('.quiqqer-products-productList-sort');
            this.$FilterDisplay     = Elm.getElement('.quiqqer-products-productList-filterList');
            this.$FilterMobile      = Elm.getElement('.quiqqer-products-productList-sort-filter-mobile');
            this.$FilterList        = Elm.getElement('.quiqqer-products-productList-filterList-list');
            this.$FilterFieldList   = Elm.getElement('.quiqqer-products-productList-filterList-fields');
            this.$FilterResultInfo  = Elm.getElement('.quiqqer-products-productList-resultInfo-text');
            this.$FilterClearButton = Elm.getElement('.quiqqer-products-productList-resultInfo-clearbtn');

            this.$FreeTextContainer = document.getElement('.quiqqer-products-category-freetextSearch');
            this.$FilterContainer   = document.getElement('.quiqqer-products-productList-filter-container-' + cid);

            if (Elm.get('data-categories') && Elm.get('data-categories') !== '') {
                Elm.get('data-categories').split(',').each(function (categoryId) {
                    this.$categories.push(parseInt(categoryId));
                }.bind(this));
            }

            if (Elm.get('data-tags') && Elm.get('data-tags') !== '') {
                this.$tags = Elm.get('data-tags').split(',');
            }

            this.$ContainerLoader = new Element('div', {
                'class': 'quiqqer-products-productList-loader',
                'html' : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    display  : 'none',
                    marginTop: 20,
                    opacity  : 0
                }
            }).inject(this.$Container);

            this.$FXContainer     = moofx(this.$Container);
            this.$FXContainerReal = moofx(this.$ContainerReal);
            this.$FXLoader        = moofx(this.$ContainerLoader);

            if (this.$FilterMobile) {
                this.$FilterMobile.addEvent('click', this.openFilterMenu);
            }

            // delete noscript tags -> because CSS
            Elm.getElements('noscript').destroy();

            // mobile touch css helper
            if (!!("ontouchstart" in document.documentElement)) {
                Elm.addClass("touch");
            }

            // add product clicks
            Elm.getElements('article').addEvent('click', function (event) {
                event.stop();
                self.openProduct(parseInt(this.get('data-pid')));
            });

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
            this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryGallery-category-more');

            if (!this.$CategoryMore) {
                this.$CategoryMore = Elm.getElement('.quiqqer-products-categoryList-category-more');
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

            // freetext
            if (this.$FreeTextContainer) {
                this.$FreeText = this.$FreeTextContainer.getElement('[type="search"]');
                var Button     = this.$FreeTextContainer.getElement('[type="submit"]');

                if (Button) {
                    Button.setStyle('display', 'none');
                }

                var executeSearch = function () {
                    this.$productId = false;
                    this.$setWindowLocation(true);
                }.bind(this);

                if ("search" in search) {
                    this.$FreeText.value = search.search;
                }

                new QUIButton({
                    icon  : 'fa fa-search',
                    events: {
                        onClick: executeSearch
                    },
                    styles: {
                        padding: 5,
                        width  : 50
                    }
                }).inject(this.$FreeTextContainer);

                this.$FreeText.addEvent('change', executeSearch);
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
                case 'detail':
                    this.$ButtonDetails.addClass('active');
                    break;
                case 'gallery':
                    this.$ButtonGallery.addClass('active');
                    break;
                case 'list':
                    this.$ButtonList.addClass('active');
                    break;
            }

            if (this.getAttribute('view') === 'detail' || this.getAttribute('view') === 'list') {
                Url.addSearch('v', this.getAttribute('view'));
                window.history.pushState({}, "", Url.toString());
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
                        onChange: this.$setWindowLocation
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

                this.$More.removeClass('disabled');
            }

            // more button auto loading
            QUI.addEvent('scroll', function () {
                if (this.$productId) {
                    return;
                }

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
                if (!this.$load) {
                    return;
                }

                this.$readWindowLocation().then(function () {
                    this.$onFilterChange();
                }.bind(this));
            }.bind(this));


            if (typeof Pace !== 'undefined') {
                var loaded   = false;
                var paceDone = function () {
                    if (loaded) {
                        return;
                    }

                    loaded = true;

                    if ("p" in search) {
                        this.$productId = false;
                    }

                    this.$readWindowLocation().then(function () {
                        this.$onFilterChange();
                        this.$load = true;

                        if (this.getAttribute('autoload')) {
                            this.$setWindowLocation();
                        }
                    }.bind(this));
                }.bind(this);

                // pace is already loaded
                if (document.body.hasClass('pace-done')) {
                    paceDone();
                } else {
                    window.Pace.on('done', paceDone);
                    paceDone.delay(1000); // fallback, if pace dont load correct
                }
                return;
            }

            (function () {
                if ("p" in search) {
                    this.$productId = false;
                }

                this.$readWindowLocation().then(function () {
                    this.$onFilterChange();
                    this.$load = true;

                    if (this.getAttribute('autoload')) {
                        this.$setWindowLocation();
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
            var self = this;

            if (this.$readLocationRunning) {
                return new Promise(function (resolve) {
                    var checkRunning = function () {
                        if (self.$readLocationRunning === false) {
                            return Promise.resolve(true);
                        }

                        return new Promise(function (resolve) {
                            (function () {
                                checkRunning().then(resolve);
                            }).delay(200);
                        });
                    }.bind(this);

                    checkRunning().then(resolve);
                });
            }

            this.$readLocationRunning = true;

            return new Promise(function (resolve) {
                var Url    = URI(window.location),
                    search = Url.search(true);

                if (!Object.getLength(search)) {
                    this.$categories          = [];
                    this.$productId           = false;
                    this.$readLocationRunning = false;

                    resolve();
                    return;
                }

                if ("p" in search && Object.getLength(search) === 1) {
                    var productId = parseInt(search.p);

                    if (productId) {
                        this.openProduct(productId).then(function () {
                            self.$readLocationRunning = false;
                            resolve();
                        });

                        return;
                    }
                }

                if ("search" in search && this.$FreeText) {
                    this.$FreeText.value = search.search;
                }

                this.$categories = [];
                this.$productId  = false;

                // fields
                if ("f" in search && this.$FilterContainer) {
                    var fieldList = this.$FilterContainer.getElements(
                        '.quiqqer-products-search-field'
                    ).map(function (Field) {
                        return QUI.Controls.getById(Field.get('data-quiid'));
                    });

                    try {
                        var Field, fieldId;
                        var fieldParams = JSON.decode(search.f);

                        var findFilterById = function (fieldId) {
                            for (var f in fieldList) {
                                if (fieldList.hasOwnProperty(f) &&
                                    fieldList[f].getFieldId() === fieldId) {
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
                var tags = Array.clone(this.$tags);

                if ("t" in search) {
                    tags.combine(search.t.split(','));
                }

                tags.each(this.addFilter.bind(this));

                // sort
                if ("sortBy" in search && "sortOn" in search && !this.$load && this.$Sort) {
                    this.$Sort.setValue(
                        search.sortOn + ' ' + search.sortBy
                    );
                }

                // categories
                if ("c" in search) {
                    this.$categories = search.c.toString().split(',');
                }

                if (this.$productId) {
                    this.$readLocationRunning = false;
                    resolve();
                    return;
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

                this.$readLocationRunning = false;
                resolve();
            }.bind(this));
        },

        /**
         * write a history entry
         *
         * @param {Boolean} [userExecute] - flag for user execution
         */
        $setWindowLocation: function (userExecute) {
            if (!this.$load) {
                return;
            }

            if (typeof userExecute === 'undefined') {
                userExecute = false;
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

            if (this.$FreeText &&
                this.$FreeText.value === '' &&
                userExecute !== false &&
                "search" in history) {
                delete history.search;
            }

            if (searchParams.tags.length) {
                var tags    = [];
                var locTags = searchParams.tags;

                for (var i = 0, len = locTags.length; i < len; i++) {
                    if (!this.$tags.contains(locTags[i])) {
                        tags.push(locTags[i]);
                    }
                }

                if (tags.length) {
                    history.t = tags.join(',');
                }
            }

            switch (this.getAttribute('view')) {
                case 'detail':
                case 'list':
                    history.v = this.getAttribute('view');
                    break;
            }

            if (this.$categories.length) {
                history.c = this.$categories.join(',');
            }

            if (searchParams.fields) {
                var fields = Object.filter(searchParams.fields, function (value) {
                    return value !== '';
                });

                if (Object.getLength(fields)) {
                    history.f = JSON.encode(fields);
                }
            }

            if (searchParams.productId) {
                history   = {};
                history.p = parseInt(searchParams.productId);
            }

            var url = location.pathname;

            if (Object.getLength(history)) {
                url = location.pathname + '?' + Object.toQueryString(history);
            }

            if ("origin" in location) {
                url = location.origin + url;
            }


            if (window.location.toString() === url) {
                return;
            }

            if ("history" in window) {
                window.history.pushState({}, "", url);
                window.fireEvent('popstate');
            } else {
                window.location = url;
            }
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
            if (!this.$sortingEnabled || !this.$load) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonGallery.addClass('active');
            this.setAttribute('view', 'gallery');

            // set view to the session
            QUIAjax.post('ajax_session_set', function () {
            }, {
                key  : 'productView',
                value: 'gallery'
            });

            this.$setWindowLocation();
        },

        /**
         * Change to detail view
         *
         * @return {Promise}
         */
        detailView: function () {
            if (!this.$sortingEnabled || !this.$load) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonDetails.addClass('active');
            this.setAttribute('view', 'detail');

            // set view to the session
            QUIAjax.post('ajax_session_set', function () {
            }, {
                key  : 'productView',
                value: 'detail'
            });

            this.$setWindowLocation();
        },

        /**
         * Change to list view
         *
         * @return {Promise}
         */
        listView: function () {
            if (!this.$sortingEnabled || !this.$load) {
                return Promise.resolve();
            }

            this.resetButtons();
            this.$ButtonList.addClass('active');
            this.setAttribute('view', 'list');

            // set view to the session
            QUIAjax.post('ajax_session_set', function () {
            }, {
                key  : 'productView',
                value: 'list'
            });

            this.$setWindowLocation();
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
                articles      = this.$ContainerReal.getElements('article').length + 1;

            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_controls_categories_productList', function (result) {
                    if (!result) {
                        self.$FilterSort.setStyle('display', 'none');
                        resolve(result);
                        return;
                    }

                    // set list view type as dat-qui attribute
                    self.$Elm.setAttribute('data-qui-options-view', self.getAttribute('view'));

                    if ("more" in result && result.more === false) {
                        self.$hideMoreButton();
                    } else {
                        self.$showMoreButton();
                    }

                    var Ghost = new Element('div', {
                        html: result.html
                    });

                    // button events
                    self.$parsePurchaseButtons(Ghost);
                    self.$parseAddButtons(Ghost);


                    var Prom = Promise.resolve();

                    if (next === false) {
                        Prom = self.$clearContainer();
                    }

                    Prom.then(function () {
                        self.enableSorting();

                        var articles = Ghost.getElements('article');

                        if (result.count === 0) {
                            self.$FilterSort.setStyle('display', 'none');

                            articles = Ghost.getElements(
                                '.quiqqer-products-productList-sort__noProducts'
                            );
                        } else {
                            self.$FilterSort.setStyle('display', null);
                        }

                        // open products in list
                        articles.addEvent('click', function (event) {
                            event.stop();
                            self.openProduct(parseInt(this.get('data-pid')));
                        });

                        articles.inject(ContainerReal);

                        return QUI.parse(ContainerReal).then(function () {
                            return self.$showContainer();
                        });
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
         * Parse the first request, php generated html and set the events
         *
         * @param {HTMLElement} Node
         */
        $parseElements: function (Node) {
            var self     = this,
                Products = Node.getElements('.quiqqer-products-productGallery-products-product'),
                Details  = Node.getElements('.quiqqer-products-productGallery-products-product-details');

            Products.set({
                tabIndex: -1,
                styles  : {
                    outline: 'none'
                },
                events  : {
                    click: function (event) { // open products in list
                        event.stop();
                        self.openProduct(parseInt(this.get('data-pid')));
                    }
                }
            });

            Details.addEvents({
                click: function (event) {
                    event.stop();

                    var Product = event.target.getParent(
                        '.quiqqer-products-productGallery-products-product'
                    );

                    Product.focus();

                    self.openProduct(parseInt(Product.get('data-pid')));
                }
            });


            var Categories      = Node.getElements('.quiqqer-products-categoryGallery-category'),
                CategoryDetails = Node.getElements('.quiqqer-products-categoryGallery-category-details');

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
                        '.quiqqer-products-categoryGallery-category'
                    ).focus();
                }
            });

            this.$parsePurchaseButtons(Node);
            this.$parseAddButtons(Node);
        },

        /**
         * Parse all purchase buttons and set the click events
         *
         * @param {HTMLElement} Node
         */
        $parsePurchaseButtons: function (Node) {
            var self    = this,
                Buttons = Node.getElements('.quiqqer-products-product-button-purchase');

            Buttons.addEvent('click', function (event) {
                event.stop();

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
        },

        /**
         * Parse all add watchlist buttons and set the click events
         *
         * @param {HTMLElement} Node
         */
        $parseAddButtons: function (Node) {
            var self = this;

            Node.getElements('.quiqqer-products-product-button-add').addEvent('click', function (event) {
                event.stop();

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

            Node.getElements('.quiqqer-products-product-button-open').addEvent('click', function (event) {
                event.stop();

                var Target    = event.target,
                    Article   = Target.getParent('article'),
                    productId = Article.get('data-pid');

                self.openProduct(productId);
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
                categories = Array.clone(this.$categories),
                tags       = Array.clone(this.$tags),
                sortOn     = '',
                sortBy     = '',
                freetext   = '',
                productId  = false;

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
                var filterTags = this.$FilterList.getElements('[data-tag]').map(function (Elm) {
                    return Elm.get('data-tag');
                });

                tags.combine(filterTags);
            }

            if (this.$Sort &&
                this.$Sort.getValue() &&
                this.$Sort.getValue() !== '') {
                var sort = this.$Sort.getValue().split(' ');

                sortBy = sort[1];
                sortOn = sort[0];
            } else if (this.getAttribute('sort')) {
                var sortAttr = this.getAttribute('sort').split(' ');

                sortBy = sortAttr[1];
                sortOn = sortAttr[0];
            }

            if (this.$productId) {
                productId = this.$productId;
            }

            if (window.location.search) {
                var Url   = URI(window.location),
                    query = Url.query(true);

                if ("search" in query) {
                    freetext = query.search;
                }
            }

            if (this.$FreeText && this.$FreeText.value !== '') {
                freetext = this.$FreeText.value;
            }

            return {
                tags      : tags,
                freetext  : freetext,
                fields    : fields,
                categories: categories,
                sortOn    : sortOn,
                sortBy    : sortBy,
                productId : productId
            };
        },

        /**
         * Delete all products
         *
         * @returns {Promise}
         */
        $clearContainer: function () {
            var self     = this,
                articles = this.$Container.getElements(
                    'article,.quiqqer-products-productList-sort__noProducts'
                );

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
                    duration: 250,
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
                var height = this.$ContainerReal.getSize().y;

                this.$FXContainer.animate({
                    height : height,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: resolve
                });

                this.$FXContainerReal.animate({
                    opacity: 1
                }, {
                    duration: 250,
                    callback: resolve
                });

                (resolve).delay(250);
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

            var ContainerAnimation = new Promise(function (resolve) {
                self.$FXContainerReal.animate({
                    opacity: 0
                }, {
                    duration: 250,
                    callback: resolve
                });
            });

            return Promise.all([
                LoaderAnimation,
                ContainerAnimation
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
            var project = {
                name: this.getAttribute('project'),
                lang: this.getAttribute('lang')
            };

            var siteId = this.getAttribute('siteId');

            if (!project.name) {
                project = {
                    name: QUIQQER_PROJECT.name,
                    lang: QUIQQER_PROJECT.lang
                };
            }

            if (!siteId) {
                siteId = QUIQQER_SITE.id;
            }

            return Search.search(siteId, project, params);
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
                '.quiqqer-products-category__hide'
            );

            if (!hiddenChildren.length) {
                return;
            }

            var size = Categories.getSize();

            Categories.setStyles({
                height  : size.y,
                overflow: 'hidden'
            });

            hiddenChildren.removeClass('quiqqer-products-category__hide');

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
        $recalculateFilterDimensions: function () {
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

            if (scrollHeight === height) {
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
                            onChange: this.$setWindowLocation
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
                onClose = function (PLF) {
                    PLF.getAttribute('Field').reset();
                },
                onReady = function () {
                    new ProductListField({
                        Field : this,
                        events: {
                            onClose: onClose
                        }
                    }).inject(self.$FilterFieldList);
                };

            this.$FilterFieldList.set('html', '');

            for (var i = 0, len = this.$selectFields.length; i < len; i++) {
                if (this.$selectFields[i].isReady() &&
                    this.$selectFields[i].getSearchValue()) {

                    new ProductListField({
                        Field : this.$selectFields[i],
                        events: {
                            onClose: onClose
                        }
                    }).inject(this.$FilterFieldList);

                    continue;
                }

                this.$selectFields[i].addEvent('ready', onReady.bind(this.$selectFields[i]));
            }

            this.refreshClearFilterButtonStatus();
        },

        /**
         * Show the filter text
         *
         * @returns {Promise}
         */
        showFilterDisplay: function () {
            if (!this.$FilterFL) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                var realHeight = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: 'absolute',
                    height  : null
                });

                var height = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: null,
                    height  : realHeight
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

            if (this.$FilterFieldList) {
                var fields = this.$FilterFieldList.getElements('.quiqqer-products-productList-filter');

                fields.forEach(function (Node) {
                    var Field = QUI.Controls.getById(Node.get('data-quiid'));
                    Field.getAttribute('Field').reset();
                });
            }

            this.$FilterList.set('html', '');

            var i, len;

            var uncheck = function (Child) {
                Child.uncheck();
            };

            for (i = 0, len = this.$selectFilter.length; i < len; i++) {
                this.$selectFilter[i].getChildren().each(uncheck);
            }

            this.refreshClearFilterButtonStatus();
            this.$setWindowLocation();
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

            if (!this.$FilterList) {
                return;
            }

            this.refreshClearFilterButtonStatus();

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

            this.$setWindowLocation();
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

                if (this.$FilterList) {
                    this.$FilterList.getElements('[data-tag="' + filter + '"]').destroy();
                }
            }

            this.$setWindowLocation();
        },

        /**
         * refresh clear filter button status
         * if button is visible or hidden
         */
        refreshClearFilterButtonStatus: function () {
            if (!this.$FilterClearButton) {
                return;
            }

            var empty = false;

            var fieldHTML  = this.$FilterFieldList.innerHTML.trim();
            var filterHTML = this.$FilterList.innerHTML.trim();

            if (fieldHTML === '' && filterHTML === '') {
                empty = true;
            }

            if (fieldHTML !== '') {
                var filters = this.$FilterFieldList.getElements('.quiqqer-products-productList-filter');

                filters = filters.filter(function (Field) {
                    return Field.getStyle('display') !== 'none';
                });

                if (!filters.length) {
                    empty = true;
                }
            }

            if (empty) {
                this.hideClearFilterButton();
                return;
            }

            this.showClearFilterButton();
        },

        /**
         * Hide the clearing filter button
         */
        hideClearFilterButton: function () {
            moofx(this.$FilterClearButton).animate({
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    this.$FilterClearButton.setStyle('display', 'none');
                }.bind(this)
            });
        },

        /**
         * Displays / Show the clearing filter button
         */
        showClearFilterButton: function () {
            this.$FilterClearButton.setStyle('display', null);
            this.$refreshSearchCount();

            moofx(this.$FilterClearButton).animate({
                opacity: 1
            }, {
                duration: 200
            });
        },

        /**
         * event on filter change
         */
        $onFilterChange: function () {
            if (!this.$FilterResultInfo) {
                return;
            }

            if (this.$productId) {
                this.openProduct(this.$productId);
                return;
            }

            this.showList();

            var self         = this,
                searchParams = this.$getSearchParams();

            if (!this.$load) {
                return;
            }

            this.fireEvent('filterChangeBegin');
            this.$hideContainerWithLoader();

            this.$FilterResultInfo.set(
                'html',
                '<span class="fa fa-spinner fa-spin"></span>'
            );


            this.refreshClearFilterButtonStatus();

            // refresh display
            if (typeof this.$refreshTimer !== 'undefined' && this.$refreshTimer) {
                clearTimeout(this.$refreshTimer);
            }

            this.$refreshTimer = (function () {
                this.$refreshSearchCount().then(function () {
                    self.fireEvent('filterChange');

                    return self.$renderSearch();
                });
            }).delay(200, self);
        },

        /**
         * Refresh the search count
         *
         * @return {Promise}
         */
        $refreshSearchCount: function () {
            var self   = this,
                search = this.$getSearchParams();

            search.count = true;

            return this.search(search).then(function (result) {
                self.$FilterResultInfo.set('html', QUILocale.get(lg, 'product.list.result.count', {
                    count: result
                }));
            });
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
            this.$setWindowLocation();
        },

        /**
         * Add an array of categories
         *
         * @param categories
         */
        addCategories: function (categories) {
            if (typeOf(categories) !== 'array') {
                return;
            }

            for (var i = 0, len = categories.length; i < len; i++) {
                if (!this.$categories.contains(categories[i])) {
                    this.$categories.push(categories[i]);
                }
            }

            this.$setWindowLocation();
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
            this.$setWindowLocation();
        },

        /**
         * Remove an array of categories
         *
         * @param categories
         */
        removeCategories: function (categories) {
            if (typeOf(categories) !== 'array') {
                return;
            }

            for (var i = 0, len = categories.length; i < len; i++) {
                this.$categories.erase(categories[i]);
            }

            this.$setWindowLocation();
        },

        /**
         * Removes all categories
         */
        clearCategories: function () {
            if (!this.$categories.length) {
                return;
            }

            this.$categories = [];
            this.$setWindowLocation();
        },

        /**
         * opens the mobile filter menü
         */
        openFilterMenu: function () {
            var self = this;

            require([
                'package/quiqqer/products/bin/controls/frontend/category/FilterWindow'
            ], function (Window) {
                var searchParams = self.$getSearchParams();

                new Window({
                    categories: searchParams.categories,
                    fields    : searchParams.fields,
                    tags      : searchParams.tags,
                    freetext  : searchParams.freetext,
                    events    : {
                        onSubmit: function (Win, filter) {
                            var i, len;
                            var history = {};

                            if (filter.freetext && filter.freetext !== '') {
                                history.search = filter.freetext;
                            }

                            switch (self.getAttribute('view')) {
                                case 'detail':
                                case 'list':
                                    history.v = this.getAttribute('view');
                                    break;
                            }

                            if (filter.categories.length) {
                                history.c = filter.categories.join(',');
                            }

                            if (filter.fields) {
                                var fields = {};

                                var fieldId, value;

                                for (i = 0, len = filter.fields.length; i < len; i++) {
                                    fieldId = filter.fields[i].fieldId;
                                    value   = filter.fields[i].value;

                                    fields[fieldId] = value;
                                }

                                if (Object.getLength(fields)) {
                                    history.f = JSON.encode(fields);
                                }
                            }

                            if (filter.tags && filter.tags.length) {
                                var tags    = [];
                                var locTags = filter.tags;

                                for (i = 0, len = locTags.length; i < len; i++) {
                                    if (!self.$tags.contains(locTags[i])) {
                                        tags.push(locTags[i]);
                                    }
                                }

                                if (tags.length) {
                                    history.t = tags.join(',');
                                }
                            }


                            var url = location.pathname;

                            if (Object.getLength(history)) {
                                url = location.pathname + '?' + Object.toQueryString(history);
                            }

                            if ("origin" in location) {
                                url = location.origin + url;
                            }

                            if (window.location.toString() === url) {
                                return;
                            }

                            if ("history" in window) {
                                window.history.pushState({}, "", url);
                                window.fireEvent('popstate');
                            } else {
                                window.location = url;
                            }

                            self.$readWindowLocation();
                        }
                    }
                }).open();
            });
        },

        /**
         * Open a product in the list
         *
         * @param {Number} productId
         */
        openProduct: function (productId) {
            if (this.$productId === productId) {
                return Promise.resolve();
            }

            QUI.fireEvent('quiqqerProductsOpenProduct', [this, productId]);

            var self = this,
                size = this.$Elm.getSize();

            this.$Elm.setStyles({
                height  : size.y,
                overflow: 'hidden',
                position: 'relative',
                width   : size.x
            });

            var children = this.$Elm.getChildren();

            children.setStyles({
                position: 'relative'
            });

            if (this.$Elm.getPrevious('.content-body')) {
                children.push(this.$Elm.getPrevious('.content-body'));
            }

            if (this.$Elm.getPrevious('.page-content-short')) {
                children.push(this.$Elm.getPrevious('.page-content-short'));
            }

            if (this.$Elm.getPrevious('.page-content-header')) {
                children.push(this.$Elm.getPrevious('.page-content-header'));
            }

            var currentCategories = this.$categories;

            this.$productId  = productId;
            this.$categories = [];

            var Loader         = new QUILoader(),
                scrollPosition = window.document.getScroll();

            return new Promise(function (resolve) {
                moofx(children).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        children.setStyle('display', 'none');
                    }
                });

                self.$closeProductContainer().then(function () {
                    if (!self.$ProductContainer) {
                        self.$ProductContainer = new Element('div', {
                            'class': 'quiqqer-product-container',
                            styles : {
                                minHeight: 600,
                                position : 'relative'
                            }
                        }).inject(self.$Elm, 'before');
                    }

                    Loader.inject(self.$ProductContainer);
                    Loader.getElm().setStyle('background', 'transparent');

                    return Loader.show();
                }).then(function () {
                    return new Promise(function (resolve) {
                        require(['package/quiqqer/products/bin/Products'], function (Products) {
                            Products.getProductControlClass(productId).then(resolve);
                        });
                    });
                }).then(function (controlClass) {
                    require([controlClass], function (Product) {
                        new Fx.Scroll(window).toTop().chain(function () {
                            self.$setWindowLocation();

                            new Product({
                                productId    : productId,
                                closeable    : true,
                                galleryLoader: false,
                                events       : {
                                    onLoad: function () {
                                        moofx(self.$Elm).animate({
                                            height: 0
                                        });

                                        if (self.$ProductContainer) {
                                            moofx(self.$ProductContainer).animate({
                                                opacity: 1
                                            }, {
                                                duration: 200
                                            });
                                        }

                                        Loader.hide().then(function () {
                                            Loader.destroy();
                                            resolve();
                                        });
                                    },

                                    onClose: function () {
                                        self.$productId  = false;
                                        self.$categories = currentCategories;

                                        var Url = URI(window.location);
                                        Url.removeSearch('p');
                                        window.history.pushState({}, "", Url.toString());

                                        QUI.fireEvent('quiqqerProductsCloseProduct', [this, productId]);

                                        self.showList(false).then(function () {
                                            var ProductElm = self.$Elm.getElement('[data-pid="' + productId + '"]');

                                            if (ProductElm) {
                                                new Fx.Scroll(window.document).start(0, scrollPosition.y);
                                            }
                                        });
                                    }
                                }
                            }).inject(self.$ProductContainer);
                        });
                    });
                });
            });
        },

        /**
         * Close all products and shows the list
         */
        showList: function (setLocation) {
            if (!this.$ProductContainer) {
                return Promise.resolve();
            }

            var self = this;

            if (typeof setLocation === 'undefined') {
                setLocation = true;
            }

            this.$productId = false;

            if (setLocation) {
                this.$setWindowLocation();
            }

            return new Promise(function (resolve) {
                self.$closeProductContainer().then(function () {
                    self.$Elm.setStyle('height', null);

                    var children = self.$Elm.getChildren();

                    if (self.$Elm.getPrevious('.content-body')) {
                        children.push(self.$Elm.getPrevious('.content-body'));
                    }

                    if (self.$Elm.getPrevious('.page-content-short')) {
                        children.push(self.$Elm.getPrevious('.page-content-short'));
                    }

                    if (self.$Elm.getPrevious('.page-content-header')) {
                        children.push(self.$Elm.getPrevious('.page-content-header'));
                    }

                    children.setStyles({
                        display: null
                    });

                    moofx(children).animate({
                        opacity: 1
                    }, {
                        duration: 200,
                        callback: function () {
                            children.setStyles({
                                opacity: null
                            });

                            resolve();
                        }
                    });
                });
            });
        },

        /**
         * Close the current product container
         *
         * @returns {Promise}
         */
        $closeProductContainer: function () {
            if (!this.$ProductContainer) {
                return Promise.resolve();
            }

            var self = this;

            return new Promise(function (resolve) {
                moofx(self.$ProductContainer).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        self.$ProductContainer.destroy();
                        self.$ProductContainer = null;
                        resolve();
                    }
                });
            });
        }
    });
});
