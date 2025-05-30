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
    'package/quiqqer/productsearch/bin/Search',
    'package/quiqqer/productsearch/bin/controls/search/SearchField',
    'Ajax',
    'Locale',
    'URI',
    'utils/Session',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListField'

], function (QUI, QUIControl, QUISelect, QUIButton, QUILoader, QUIElementUtils,
             Search, SearchField, QUIAjax, QUILocale, URI, Session,
             ProductListFilter, ProductListField
) {
    "use strict";

    const DEBUG = false;
    const lg = 'quiqqer/products';

    let productOpened = false;
    let animationDuration = 300;
    let refreshSearchCountTimeOut = null;

    if (typeof window.QUIQQER_PRODUCTS_FRONTEND_ANIMATION !== 'undefined') {
        animationDuration = window.QUIQQER_PRODUCTS_FRONTEND_ANIMATION;
    }

    // history popstate for mootools
    Element.NativeEvents.popstate = 2;

    window.addEvent('load', function () {
        // browser workaround, to add the first page to the history
        if (window.location.hash === '') {
            history.pushState({}, '', '/#');
        }
    });

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/frontend/category/ProductList',

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
            view: 'gallery',
            sort: false,
            project: false,
            lang: false,
            siteId: false,
            autoload: true,
            autoloadAfter: 3, // After how many clicks are further products loaded automatically? (false | number)
            productLoadNumber: 9,
            openproductasync: false, // true / false
            searchfields: {}
        },

        initialize: function (options) {
            this.parent(options);

            this.$load = false;
            this.$readLocationRunning = false;

            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList = null;
            this.$BarSort = null;
            this.$BarDisplays = null;
            this.$More = null;
            this.$Sort = null;

            this.$FXContainer = null;
            this.$FXContainerReal = null;
            this.$FXLoader = null;
            this.$FXMore = null;

            this.$Container = null;
            this.$ContainerLoader = null;
            this.$ProductContainer = null;

            this.$CategoryMore = null;
            this.$FilterSort = null;
            this.$FilterDisplay = null;
            this.$FilterMobile = null;
            this.$FilterResultInfo = null;
            this.$FilterClearButton = null;
            this.$FilterList = null;
            this.$FilterFieldList = null;

            this.$FreeText = null;
            this.$FreeTextContainer = null;

            this.$fields = {};
            this.$selectFilter = [];
            this.$selectFields = [];
            this.$categories = [];
            this.$tags = [];
            this.$productId = false;

            this.$sortingEnabled = true;
            this.$moreButtonIsVisible = false;
            this.$moreButtonClicked = 0;
            this.$loadingMore = false;
            this.openProductAsync = false;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });

            QUI.addEvent('resize', function () {
                this.$recalculateFilterDimensions().catch(console.error);
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
            const self = this,
                Elm = this.getElm(),
                cid = Elm.get('data-productlist-id');

            if (parseInt(Elm.get('data-autoload')) === 0) {
                this.setAttribute('autoload', false);
            }

            // Search fields
            if (this.getAttribute('searchfields')) {
                this.setAttribute('searchfields', JSON.decode(this.getAttribute('searchfields')));
            }

            this.openProductAsync = parseInt(Elm.get('data-openproductasync'));

            this.$productLoadNumber = parseInt(Elm.get('data-productLoadNumber'));
            this.$autoloadAfter = parseInt(Elm.get('data-autoloadAfter'));

            if (this.$autoloadAfter < 0) {
                this.$autoloadAfter = 0;
            }

            if (this.$autoloadAfter === 0) {
                this.$autoloadAfter = false;
            }

            if (Elm.get('data-productLoadNumber')) {
                this.setAttribute('productLoadNumber', Elm.get('data-productLoadNumber'));
            }

            if (Elm.get('data-sort')) {
                this.setAttribute('sort', Elm.get('data-sort'));
            }

            let Url = URI(window.location),
                search = Url.search(true);

            if ("p" in search) {
                this.$productId = parseInt(search.p);
            }

            this.$ButtonDetails = Elm.getElements('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGallery = Elm.getElements('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList = Elm.getElements('.quiqqer-products-productList-sort-display-list');
            this.$Container = Elm.getElement('.quiqqer-products-productList-products-container');
            this.$ContainerReal = Elm.getElement('.quiqqer-products-productList-products-container-real');

            this.$FilterFL = Elm.getElement('.quiqqer-products-productList-fl');
            this.$FilterSort = Elm.getElement('.quiqqer-products-productList-sort');
            this.$FilterDisplay = Elm.getElement('.quiqqer-products-productList-filterList');
            this.$FilterMobile = Elm.getElement('.quiqqer-products-productList-sort-filter-mobile');
            this.$FilterList = Elm.getElement('.quiqqer-products-productList-filterList-list');
            this.$FilterFieldList = Elm.getElement('.quiqqer-products-productList-filterList-fields');
            this.$FilterResultInfo = Elm.getElement('.quiqqer-products-productList-resultInfo-text');
            this.$FilterClearButton = Elm.getElement('.quiqqer-products-productList-resultInfo-clearbtn');

            this.$FreeTextContainer = document.getElement('.quiqqer-products-category-freetextSearch');
            this.$FilterContainer = document.getElement('.quiqqer-products-productList-filter-container-' + cid);

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
                'html': '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    display: 'none',
                    marginTop: 20,
                    opacity: 0
                }
            }).inject(this.$Container);

            this.$FXContainer = moofx(this.$Container);
            this.$FXContainerReal = moofx(this.$ContainerReal);
            this.$FXLoader = moofx(this.$ContainerLoader);

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
            Elm.getElements('article').addEvent('dblclick', function (event) {
                event.stop();
            });

            if (this.openProductAsync) {
                Elm.getElements('article').addEvent('click', function (event) {
                    event.stop();
                    self.openProduct(parseInt(this.get('data-pid')));
                });
            }

            // filter
            if (this.$FilterContainer) {
                let inner = this.$FilterContainer.get('html');

                this.$FilterContainer.set('html', '');

                new Element('div', {
                    html: inner,
                    styles: {
                        'float': 'left',
                        paddingBottom: 20,
                        width: '100%'
                    }
                }).inject(this.$FilterContainer);
            }

            this.$BarFilter = Elm.getElement('.quiqqer-products-productList-sort-filter');
            this.$BarSort = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays = Elm.getElement('.quiqqer-products-productList-sort-display');
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
            } else {
                if (this.$FilterContainer) {
                    // open filter, if no filter button exists
                    moofx(this.$FilterContainer).animate({
                        background: 'transparent'
                    }, {
                        duration: animationDuration,
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
            }

            // freetext
            if (this.$FreeTextContainer) {
                this.$FreeText = this.$FreeTextContainer.getElement('[type="search"]');
                let Button = this.$FreeTextContainer.getElement('[type="submit"]');

                if (Button) {
                    Button.setStyle('display', 'none');
                }

                let executeSearch = function () {
                    this.$productId = false;
                    this.$setWindowLocation(true);
                }.bind(this);

                if ("search" in search) {
                    this.$FreeText.value = search.search;
                }

                var FreeTextSearchBtn = this.$FreeTextContainer.getElement('button');

                if (FreeTextSearchBtn) {
                    FreeTextSearchBtn.addEvent('click', executeSearch);
                } else {
                    new QUIButton({
                        icon: 'fa fa-search',
                        events: {
                            onClick: executeSearch
                        },
                        styles: {
                            padding: 5,
                            width: 50
                        }
                    }).inject(this.$FreeTextContainer);
                }

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
                let Select = this.$BarSort.getElement('select'),
                    options = Select.getElements('option');

                this.$Sort = new QUISelect({
                    showIcons: false,
                    placeholderText: QUILocale.get(lg, 'product.list.sort.placeholder'),
                    events: {
                        onChange: this.$setWindowLocation
                    }
                });

                for (let i = 0, len = options.length; i < len; i++) {
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

                if (!this.$autoloadAfter) {
                    return;
                }

                if (this.$moreButtonClicked < this.$autoloadAfter) {
                    return;
                }

                if (!this.$moreButtonIsVisible) {
                    return;
                }

                if (this.$loadingMore) {
                    return;
                }

                let isInView = QUIElementUtils.isInViewport(this.$More);

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


            (function () {
                if ("p" in search) {
                    this.$productId = false;
                }

                this.$readWindowLocation().then(function () {
                    this.$load = true;
                    this.$renderFilterFields();
                }.bind(this));
            }).delay(500, this);
        },

        /**
         * read the url params and set the params to the product list
         *
         * @returns {Promise}
         */
        $readWindowLocation: function () {
            const self = this;

            if (DEBUG) {
                console.log('$readWindowLocation', 1);
            }

            if (this.$readLocationRunning) {
                if (DEBUG) {
                    console.log('$readWindowLocation', '1 -> ');
                }

                return new Promise(function (resolve) {
                    const checkRunning = function () {
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

            if (DEBUG) {
                console.log('$readWindowLocation', 2);
            }

            return new Promise(function (resolve) {
                let Close;
                const Url = URI(window.location),
                    search = Url.search(true);

                if (!Object.getLength(search)) {
                    if (DEBUG) {
                        console.log('$readWindowLocation', 3, {
                            productOpened: productOpened,
                            Container: this.$ProductContainer
                        });
                    }

                    if (productOpened && this.$ProductContainer) {
                        Close = this.$ProductContainer.getElement('.product-close-button');

                        if (Close) {
                            this.$readLocationRunning = false;
                            Close.click();
                            return Promise.resolve();
                        }
                    }

                    if (DEBUG) {
                        console.log('$readWindowLocation', 4);
                    }

                    this.$categories = [];
                    this.$productId = false;
                    this.$readLocationRunning = false;

                    resolve();
                    return;
                }

                if (DEBUG) {
                    console.log('$readWindowLocation', 5);
                }

                if ("p" in search && Object.getLength(search) === 1) {
                    if (DEBUG) {
                        console.log('$readWindowLocation', 6);
                    }

                    let productId = parseInt(search.p);

                    if (productOpened) {
                        if (DEBUG) {
                            console.log('$readWindowLocation', 7);
                        }

                        this.$readLocationRunning = false;
                        resolve();
                        return;
                    }

                    if (productId) {
                        if (DEBUG) {
                            console.log('$readWindowLocation', 8);
                        }

                        this.openProduct(productId).then(function () {
                            self.$readLocationRunning = false;
                            resolve();
                        });

                        return;
                    }
                }

                if (DEBUG) {
                    console.log('$readWindowLocation', 9);
                }

                if ("search" in search && this.$FreeText) {
                    this.$FreeText.value = search.search;
                }

                this.$categories = [];
                this.$productId = false;

                // fields
                if ("f" in search && this.$FilterContainer) {
                    if (DEBUG) {
                        console.log('$readWindowLocation', 10);
                    }

                    let fieldList = this.$FilterContainer.getElements(
                        '.quiqqer-products-search-field'
                    ).map(function (Field) {
                        return QUI.Controls.getById(Field.get('data-quiid'));
                    });

                    try {
                        let Field, fieldId;
                        let fieldParams = JSON.decode(search.f);

                        let findFilterById = function (fieldId) {
                            for (let f in fieldList) {
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

                            if (!Field) {
                                continue;
                            }

                            let currentFieldValue = Field.getSearchValue();
                            let newFieldValue = fieldParams[fieldId];

                            if (typeof currentFieldValue === 'object' && currentFieldValue) {
                                currentFieldValue = Object.toQueryString(currentFieldValue);
                            }

                            if (typeof newFieldValue === 'object' && newFieldValue) {
                                newFieldValue = Object.toQueryString(newFieldValue);
                            }

                            if (Field && currentFieldValue !== newFieldValue) {
                                Field.setSearchValue(fieldParams[fieldId]);
                            }

                            let values = Field.getSearchValue();

                            if (typeOf(values) === 'array') {
                                for (let i = 0, len = values.length; i < len; i++) {
                                    this.addFilter(values[i], Field);
                                }
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }

                // tags
                let tags = Array.clone(this.$tags);

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
                    if (DEBUG) {
                        console.log('$readWindowLocation', 11);
                    }

                    this.$readLocationRunning = false;
                    resolve();
                    return;
                }

                if (productOpened && this.$ProductContainer) {
                    if (DEBUG) {
                        console.log('$readWindowLocation', 12);
                    }

                    Close = this.$ProductContainer.getElement('.product-close-button');

                    if (Close) {
                        this.$readLocationRunning = false;
                        Close.click();
                        return Promise.resolve();
                    }
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
            let history = {},
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
                let tags = [];
                let locTags = searchParams.tags;

                for (let i = 0, len = locTags.length; i < len; i++) {
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
                let fields = Object.filter(searchParams.fields, function (value) {
                    return value !== '';
                });

                if (Object.getLength(fields)) {
                    history.f = JSON.encode(fields);
                }
            }

            if (searchParams.productId) {
                history = {};
                history.p = parseInt(searchParams.productId);
            }

            let url = location.pathname;

            if (Object.getLength(history)) {
                url = location.pathname + '?' + Object.toQueryString(history);
            }

            if ("origin" in location) {
                url = location.origin + url;
            }

            this.$renderFilterFields();

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
            let self = this,
                size = this.$More.getSize();

            this.$More.addClass('disabled');
            this.$loadingMore = true;

            this.$More.setStyles({
                height: size.y,
                overflow: 'hidden',
                width: size.x
            });

            return new Promise(function (resolve) {
                let oldButtonText = self.$More.get('text');

                if (self.$More) {
                    self.$More.set('html', '<span class="fa fa-spinner fa-spin"></span>');
                    self.$More.setStyle('color', null);
                    self.$More.addClass('loading');
                }

                self.$renderSearch(true).then(function (data) {
                    if (self.$More) {
                        self.$More.set({
                            html: oldButtonText,
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

            Session.set('productView', 'gallery');

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

            Session.set('productView', 'detail');

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
            Session.set('productView', 'list');

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

            let self = this,
                view = this.getAttribute('view'),
                sort = this.getAttribute('sort'),
                categoryId = this.getAttribute('categoryId'),
                productLoadNumber = this.getAttribute('productLoadNumber'),
                ContainerReal = this.$ContainerReal,
                articles = this.$ContainerReal.getElements('article').length + 1;

            if (sort) {
                sort = sort.replace('Sc_date', 'c_date').replace('Se_date', 'e_date');
            }

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

                    const Ghost = new Element('div', {
                        html: result.html
                    });

                    // button events
                    self.$parsePurchaseButtons(Ghost);
                    self.$parseAddButtons(Ghost);


                    let Prom = Promise.resolve();

                    if (next === false) {
                        Prom = self.$clearContainer();
                    }

                    Prom.then(function () {
                        self.enableSorting();

                        let articles = Ghost.getElements('article');

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

                            if (result.count === 0) {
                                return;
                            }

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
                    'package': 'quiqqer/products',
                    view: view,
                    sort: sort,
                    articles: articles,
                    next: next ? 1 : 0,
                    categoryId: categoryId,
                    productLoadNumber: productLoadNumber,
                    project: JSON.encode(window.QUIQQER_PROJECT),
                    siteId: self.getAttribute('siteId'),
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
            const self = this,
                Products = Node.getElements('.quiqqer-products-productGallery-products-product'),
                Details = Node.getElements('.quiqqer-products-productGallery-products-product-details');

            Products.set({
                tabIndex: -1,
                styles: {
                    outline: 'none'
                },
                events: {
                    click: function (event) { // open products in list
                        event.stop();
                        self.openProduct(parseInt(this.get('data-pid')));
                    }
                }
            });

            Details.addEvents({
                click: function (event) {
                    event.stop();

                    let Product = event.target.getParent(
                        '.quiqqer-products-productGallery-products-product'
                    );

                    Product.focus();

                    self.openProduct(parseInt(Product.get('data-pid')));
                }
            });


            const Categories = Node.getElements('.quiqqer-products-categoryGallery-category'),
                CategoryDetails = Node.getElements('.quiqqer-products-categoryGallery-category-details');

            Categories.set({
                tabIndex: -1,
                styles: {
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
            const self = this,
                Buttons = Node.getElements('.quiqqer-products-product-button-purchase');

            Buttons.addEvent('click', function (event) {
                event.stop();

                const Target = event.target,
                    Article = Target.getParent('article'),
                    productId = Article.get('data-pid');

                Target.removeClass('fa-envelope');
                Target.addClass('fa-spinner fa-spin');

                require([
                    'package/quiqqer/watchlist/bin/controls/frontend/PurchaseWindow',
                    'package/quiqqer/watchlist/bin/classes/Product'
                ], function (Purchase, WatchlistProduct) {
                    const Product = new WatchlistProduct({
                        id: productId,
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
            const self = this;

            Node.getElements('.quiqqer-products-product-button-add').addEvent('click', function (event) {
                event.stop();

                let Target = event.target,
                    Article = Target.getParent('article'),
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

                let Target = event.target,
                    Article = Target.getParent('article'),
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
            let i, len, Field;

            let fields = this.getAttribute('searchfields') || {},
                categories = Array.clone(this.$categories),
                tags = Array.clone(this.$tags),
                sortOn = '',
                sortBy = '',
                freetext = '',
                productId = false;

            if (this.$FilterContainer) {
                let value;
                let fieldNodes = this.$FilterContainer.getElements('.quiqqer-products-search-field');

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
                let filterTags = this.$FilterList.getElements('[data-tag]').map(function (Elm) {
                    return Elm.get('data-tag');
                });

                tags.combine(filterTags);
            }

            if (this.$Sort &&
                this.$Sort.getValue() &&
                this.$Sort.getValue() !== '') {
                let sort = this.$Sort.getValue().split(' ');

                sortBy = sort[1];
                sortOn = sort[0];
            } else {
                if (this.getAttribute('sort')) {
                    let sortAttr = this.getAttribute('sort').split(' ');

                    sortBy = sortAttr[1];
                    sortOn = sortAttr[0];
                }
            }

            if (this.$productId) {
                productId = this.$productId;
            }

            if (window.location.search) {
                let Url = URI(window.location),
                    query = Url.query(true);

                if ("search" in query) {
                    freetext = query.search;
                }
            }

            if (this.$FreeText && this.$FreeText.value !== '') {
                freetext = this.$FreeText.value;
            }

            sortOn = sortOn.replace('Sc_date', 'c_date').replace('Se_date', 'e_date');

            return {
                tags: tags,
                freetext: freetext,
                fields: fields,
                categories: categories,
                sortOn: sortOn,
                sortBy: sortBy,
                productId: productId
            };
        },

        /**
         * Delete all products
         *
         * @returns {Promise}
         */
        $clearContainer: function () {
            let self = this,
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
                duration: animationDuration,
                callback: function () {
                    self.$ContainerLoader.setStyle('display', 'none');
                }
            });

            return new Promise(function (resolve) {
                self.$FXContainer.animate({
                    opacity: 0
                }, {
                    duration: animationDuration,
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
                let height = this.$ContainerReal.getSize().y;

                this.$FXContainer.animate({
                    height: height,
                    opacity: 1
                }, {
                    duration: animationDuration,
                    callback: resolve
                });

                this.$FXContainerReal.animate({
                    opacity: 1
                }, {
                    duration: animationDuration,
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
                    duration: animationDuration,
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
            const self = this;

            let LoaderAnimation = new Promise(function (resolve) {
                self.$ContainerLoader.setStyle('opacity', 0);
                self.$ContainerLoader.setStyle('display', null);
                self.$FXLoader.animate({
                    opacity: 1
                }, {
                    duration: animationDuration,
                    callback: resolve
                });
            });

            let ContainerAnimation = new Promise(function (resolve) {
                self.$FXContainerReal.animate({
                    opacity: 0
                }, {
                    duration: animationDuration,
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
            let Row = this.$Container.getElement('[data-row]:last-child');

            return new Promise(function (resolve) {
                new Fx.Scroll(window.document, {
                    duration: animationDuration
                }).start(
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
                    duration: animationDuration,
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
                    duration: animationDuration,
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
            let project = {
                name: this.getAttribute('project'),
                lang: this.getAttribute('lang')
            };

            let siteId = this.getAttribute('siteId');

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
            let Categories = this.getElm().getElement(
                '.quiqqer-products-productList-categories'
            );

            if (!Categories) {
                return;
            }

            let hiddenChildren = Categories.getElements(
                '.quiqqer-products-category__hide'
            );

            if (!hiddenChildren.length) {
                return;
            }

            let size = Categories.getSize();

            Categories.setStyles({
                height: size.y,
                overflow: 'hidden'
            });

            hiddenChildren.removeClass('quiqqer-products-category__hide');

            let wantedSizes = Categories.getScrollSize();

            if (this.$CategoryMore) {
                moofx(this.$CategoryMore).animate({
                    height: 0,
                    opacity: 0
                }, {
                    duration: animationDuration,
                    callback: function () {
                        this.$CategoryMore.setStyle('display', 'none');
                    }.bind(this)
                });
            }

            moofx(Categories).animate({
                height: wantedSizes.y
            }, {
                duration: animationDuration,
                callback: function () {

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

            let opacity = this.$FilterContainer.getStyle('opacity').toInt();

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

            let opacity = this.$FilterContainer.getStyle('opacity').toInt();

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
                let Opener = this.$BarFilter.getElement(
                    '.quiqqer-products-productList-sort-filter-opener'
                );

                Opener.removeClass('fa-angle-down');
                Opener.addClass('fa-angle-double-down');
            }

            let scrollHeight = this.$FilterContainer.getFirst('div').getComputedSize().totalHeight,
                height = this.$FilterContainer.getSize().y;

            if (scrollHeight === height) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                let padding = '20px 0';

                if (this.$FilterContainer.getParent('.content-headerpage-multible-left')) {
                    padding = 0;
                }

                moofx(this.$FilterContainer).animate({
                    height: scrollHeight,
                    opacity: 1,
                    padding: padding
                }, {
                    duration: animationDuration,
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

            let Opener = this.$BarFilter.getElement(
                '.quiqqer-products-productList-sort-filter-opener'
            );

            Opener.removeClass('fa-angle-double-down');
            Opener.addClass('fa-angle-down');

            return new Promise(function (resolve) {
                moofx(this.$FilterContainer).animate({
                    height: 0,
                    opacity: 0,
                    padding: 0
                }, {
                    duration: animationDuration,
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

            let c, i, len, clen, options, searchdata, Field, Control, Filter, Title, Select;

            // standard
            let filter = this.$FilterContainer.getElements(
                '.quiqqer-products-productList-filter-entry'
            );

            let change = function (values, Select) {
                for (let i = 0, len = values.length; i < len; i++) {
                    this.addFilter(values[i]);
                }

                // removing deleted filter
                let uncheckedFilter = Select.getChildren().filter(function (Item) {
                    return !Item.isChecked();
                }).map(function (Item) {
                    return Item.getAttribute('value');
                });

                for (i = 0, len = uncheckedFilter.length; i < len; i++) {
                    this.removeFilter(uncheckedFilter[i]);
                }

                if (this.$ProductContainer) {
                    this.showList();
                }
            }.bind(this);

            for (i = 0, len = filter.length; i < len; i++) {
                Filter = filter[i];
                Select = Filter.getElement('select');
                Title = Filter.getElement(
                    '.quiqqer-products-productList-filter-entry-title'
                );

                // field
                if (!Select) {
                    // search fields
                    // Title.destroy();
                    Select = Filter.getElement('input');
                    searchdata = null;

                    try {
                        searchdata = JSON.decode(Select.get('data-searchdata'));
                    } catch (e) {
                    }

                    Field = new SearchField({
                        fieldid: Select.get('data-fieldid'),
                        searchtype: Select.get('data-searchtype'),
                        searchdata: searchdata,
                        title: Title.get('text').trim(),
                        events: {
                            onChange: (Instance) => {
                                if (this.$ProductContainer) {
                                    this.showList();
                                }

                                this.$setWindowLocation(Instance);
                            }
                        }
                    }).inject(Filter);

                    this.$selectFields.push(Field);
                    Select.destroy();
                    continue;
                }

                options = Select.getElements('option');

                Control = new QUISelect({
                    placeholderText: QUILocale.get('quiqqer/products', 'controls.productList.filter.select.placeholder'),
                    placeholderSelectable: false,
                    multiple: true,
                    checkable: true,
                    styles: {
                        width: '100%'
                    },
                    events: {
                        onChange: change
                    }
                });

                let v;
                let tagsFromProducts = false;

                if (typeof window.QUIQQER_PRODUCTS_TAGS_FROM_PRODUCTS !== 'undefined') {
                    tagsFromProducts = window.QUIQQER_PRODUCTS_TAGS_FROM_PRODUCTS;
                    tagsFromProducts = Object.values(tagsFromProducts);
                }

                for (c = 0, clen = options.length; c < clen; c++) {
                    v = options[c].get('value').trim();

                    if (tagsFromProducts && tagsFromProducts.indexOf(v) === -1) {
                        continue;
                    }

                    Control.appendChild(
                        options[c].get('html').trim(),
                        v
                    );
                }

                Select.destroy();
                Control.inject(Filter);

                if (this.$tags.length) {
                    Control.setValues(Array.clone(this.$tags), true);
                }

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

            if (DEBUG) {
                console.log('$renderFilterFields');
                console.log(this.$selectFields);
            }

            let self = this,
                onClose = function (PLF) {
                    let Field = PLF.getAttribute('Field');

                    Field.reset();

                    let SearchFields = self.getAttribute('searchfields');

                    if (SearchFields && Field.getFieldId() in SearchFields) {
                        delete SearchFields[Field.getFieldId()];
                    }
                },
                onReady = function () {
                    if (!self.$load) {
                        return;
                    }

                    new ProductListField({
                        Field: this,
                        events: {
                            onClose: onClose
                        }
                    }).inject(self.$FilterFieldList);
                };

            this.$FilterFieldList.set('html', '');

            let i, len, value;

            for (i = 0, len = this.$selectFields.length; i < len; i++) {
                value = this.$selectFields[i].getSearchValue();

                if (this.$selectFields[i].isReady() && value) {
                    if (typeof value !== 'string' && typeof value.length !== 'undefined') {
                        value = value.join(',');
                    } else if (typeof value === 'object') {
                        value = Object.toQueryString(value);
                    }

                    if (this.$FilterFieldList.getElement('[data-field-value="' + value + '"]')) {
                        continue;
                    }

                    new ProductListField({
                        Field: this.$selectFields[i],
                        events: {
                            onClose: onClose
                        }
                    }).inject(this.$FilterFieldList);

                    continue;
                }

                this.$selectFields[i].addEvent('ready', onReady.bind(this.$selectFields[i]));
            }

            if (DEBUG) {
                console.log(this.$FilterFieldList);
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
                let realHeight = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: 'absolute',
                    height: null
                });

                let height = this.$FilterFL.getSize().y;

                this.$FilterFL.setStyles({
                    position: null,
                    height: realHeight
                });

                moofx(this.$FilterFL).animate({
                    height: height,
                    opacity: 1
                }, {
                    duration: animationDuration,
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
                    height: 0,
                    opacity: 0
                }, {
                    duration: animationDuration,
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
                let fields = this.$FilterFieldList.getElements('.quiqqer-products-productList-filter');

                fields.forEach(function (Node) {
                    let Field = QUI.Controls.getById(Node.get('data-quiid'));
                    Field.getAttribute('Field').reset();
                });
            }

            this.$tags = [];
            this.$FilterList.set('html', '');

            let i, len;

            const uncheck = function (Child) {
                Child.uncheck();
            };

            for (i = 0, len = this.$selectFilter.length; i < len; i++) {
                this.$selectFilter[i].getChildren().each(uncheck);
            }

            this.$load = false;

            if ("history" in window) {
                window.history.pushState({}, "", QUIQQER_SITE.url);
                window.fireEvent('popstate');
            } else {
                window.location = QUIQQER_SITE.url;
            }

            this.refreshClearFilterButtonStatus();
            this.$FilterFieldList.set('html', '');

            this.hideClearFilterButton().then(() => {
                return this.$renderSearch();
            }).then(() => {
                return this.$refreshSearchCount();
            }).then(() => {
                this.$load = true;
            });
        },

        /**
         * Add a filter to the list
         *
         * @param {String} filter
         * @param {Object} [Field] - Assigned field
         */
        addFilter: function (filter, Field) {
            if (typeof Field === 'undefined' || typeof Field !== 'object') {
                Field = null;
            }

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

            if (Field) {
                const fieldId = Field.getAttribute('fieldid');
                const FieldNode = this.$FilterFieldList.getElement('[data-field="' + fieldId + '"]');

                if (FieldNode) {
                    const FieldNodeInstance = QUI.Controls.getById(FieldNode.get('data-quiid'));
                    const Field = FieldNodeInstance.getAttribute('Field');

                    let values = Field.getSearchValue();

                    if (values.indexOf(filter)) {
                        return;
                    }

                    FieldNodeInstance.refresh();
                    return;
                }
            }

            new ProductListFilter({
                Field: Field,
                tag: filter,
                events: {
                    onDestroy: (Instance) => {
                        this.removeFilter(
                            Instance.getAttribute('tag'),
                            Instance.getAttribute('Field')
                        );

                        this.$onFilterChange();
                    }
                }
            }).inject(this.$FilterList);

            this.$setWindowLocation();
        },

        /**
         * remove a filter
         *
         * @param {String} filter
         * @param {Object} [Field]
         */
        removeFilter: function (filter, Field) {
            if (!this.$selectFilter) {
                return;
            }

            if (this.$tags && this.$tags.indexOf(filter) !== -1) {
                this.$tags.splice(this.$tags.indexOf(filter), 1);
            }

            if (typeof (Field) !== 'undefined' && Field) {
                if (this.$FilterList) {
                    this.$FilterList.getElements('[data-tag="' + filter + '"]').destroy();
                }

                let currentFieldValues = Field.getSearchValue();

                if (typeOf(currentFieldValues) === 'array') {
                    const index = currentFieldValues.indexOf(filter);

                    if (index > -1) {
                        currentFieldValues.splice(index, 1);
                    }
                }

                Field.setSearchValue(currentFieldValues);
                this.$setWindowLocation();
                return;
            }

            for (let i = 0, len = this.$selectFilter.length; i < len; i++) {
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

            let empty = false;

            let fieldHTML = this.$FilterFieldList.innerHTML.trim();
            let filterHTML = this.$FilterList.innerHTML.trim();

            if (fieldHTML === '' && filterHTML === '') {
                empty = true;
            }

            if (fieldHTML !== '') {
                let filters = this.$FilterFieldList.getElements('.quiqqer-products-productList-filter');

                filters.append(
                    this.$FilterList.getElements('.quiqqer-products-productList-filter')
                );

                filters = filters.filter(function (Field) {
                    return Field.getStyle('display') !== 'none';
                });

                if (!filters.length) {
                    empty = true;
                }
            }

            if (empty) {
                this.hideClearFilterButton().catch(console.error);
                return;
            }

            this.showClearFilterButton().catch(console.error);
        },

        /**
         * Hide the clearing filter button
         *
         * @return {Promise}
         */
        hideClearFilterButton: function () {
            this.$FilterClearButton.setStyle('display', 'none');
            return Promise.resolve();
        },

        /**
         * Displays / Show the clearing filter button
         */
        showClearFilterButton: function () {
            this.$FilterClearButton.setStyle('opacity', null);
            this.$FilterClearButton.setStyle('display', null);
            return this.$refreshSearchCount();
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

            const self = this;

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
            return new Promise((resolve) => {
                let self = this,
                    search = this.$getSearchParams();

                search.count = true;

                if (refreshSearchCountTimeOut) {
                    resolve();
                    return;
                }

                refreshSearchCountTimeOut = (() => {
                    this.search(search).then(function (result) {
                        self.$FilterResultInfo.set('html', QUILocale.get(lg, 'product.list.result.count', {
                            count: result
                        }));

                        refreshSearchCountTimeOut = null;
                    }).then(resolve);
                }).delay(100);
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

            for (let i = 0, len = categories.length; i < len; i++) {
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

            for (let i = 0, len = categories.length; i < len; i++) {
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
            const self = this;

            require([
                'package/quiqqer/products/bin/controls/frontend/category/FilterWindow'
            ], function (Window) {
                let searchParams = self.$getSearchParams();

                new Window({
                    categories: searchParams.categories,
                    fields: searchParams.fields,
                    tags: searchParams.tags,
                    freetext: searchParams.freetext,
                    events: {
                        onSubmit: function (Win, filter) {
                            let i, len;
                            let history = {};

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
                                let fields = {};

                                let fieldId, value;

                                for (i = 0, len = filter.fields.length; i < len; i++) {
                                    fieldId = filter.fields[i].fieldId;
                                    value = filter.fields[i].value;

                                    fields[fieldId] = value;
                                }

                                if (Object.getLength(fields)) {
                                    history.f = JSON.encode(fields);
                                }
                            }

                            if (filter.tags && filter.tags.length) {
                                let tags = [];
                                let locTags = filter.tags;

                                for (i = 0, len = locTags.length; i < len; i++) {
                                    if (!self.$tags.contains(locTags[i])) {
                                        tags.push(locTags[i]);
                                    }
                                }

                                if (tags.length) {
                                    history.t = tags.join(',');
                                }
                            }


                            let url = location.pathname;

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

            let ListContainer = this.getElm().getElement('.quiqqer-products-productList-products');

            if (ListContainer) {
                let height = this.getElm().getSize().y;

                if (height) {
                    this.getElm().setStyle('height', height);
                }
            }

            productOpened = true;
            QUI.fireEvent('quiqqerProductsOpenProduct', [
                this,
                productId
            ]);

            let self = this,
                size = this.$Elm.getSize();

            this.$Elm.setStyles({
                height: size.y,
                overflow: 'hidden',
                position: 'relative',
                width: size.x
            });

            let children = this.$Elm.getChildren();

            children.setStyles({
                position: 'relative'
            });

            this.$Elm.getAllPrevious().forEach(function (node) {
                children.push(node);
            });

            let currentCategories = this.$categories;

            this.$productId = productId;
            this.$categories = [];

            let Loader = new QUILoader(),
                scrollPosition = window.document.getScroll();

            return new Promise(function (resolve) {
                moofx(children).animate({
                    opacity: 0
                }, {
                    duration: animationDuration,
                    callback: function () {
                        children.setStyle('display', 'none');
                    }
                });

                self.$closeProductContainer().then(function () {
                    if (!self.$ProductContainer) {
                        self.$ProductContainer = new Element('div', {
                            'class': 'quiqqer-product-container',
                            styles: {
                                minHeight: 600,
                                position: 'relative'
                            }
                        }).inject(self.$Elm, 'before');
                    }

                    Loader.inject(self.$ProductContainer);
                    Loader.getElm().setStyle('background', 'transparent');

                    return Loader.show();
                }).then(function () {
                    return new Promise(function (resolve) {
                        require(['package/quiqqer/products/bin/Products'], function (Products) {
                            Products.getProductControlClass(productId).then(resolve).catch(function () {
                                self.$productId = false;
                                self.$categories = currentCategories;

                                if (DEBUG) {
                                    console.log('Products.getProductControlClass');
                                }

                                let Url = URI(window.location);
                                Url.removeSearch('p');
                                window.history.pushState({}, "", Url.toString());

                                self.showList(false);
                                resolve(false);

                            });
                        });
                    });
                }).then(function (controlClass) {
                    if (!controlClass) {
                        return;
                    }

                    require([controlClass], function (Product) {
                        new Fx.Scroll(window, {
                            duration: animationDuration
                        }).toTop().chain(function () {
                            self.$setWindowLocation();

                            let Instance = new Product({
                                productId: productId,
                                closeable: true,
                                galleryLoader: false,
                                loadControlSettingsAsync: true,
                                events: {
                                    onLoad: function () {
                                        moofx(self.$Elm).animate({
                                            height: 0
                                        }, {
                                            duration: animationDuration
                                        });

                                        if (self.$ProductContainer) {
                                            moofx(self.$ProductContainer).animate({
                                                opacity: 1
                                            }, {
                                                duration: animationDuration
                                            });
                                        }

                                        Loader.hide().then(function () {
                                            Loader.destroy();
                                            resolve();
                                        });
                                    },

                                    onClose: function () {
                                        self.$productId = false;
                                        self.$categories = currentCategories;

                                        let Url = URI(window.location);
                                        Url.removeSearch('p');
                                        window.history.pushState({}, "", Url.toString());

                                        QUI.fireEvent('quiqqerProductsCloseProduct', [
                                            this,
                                            productId
                                        ]);

                                        self.showList(false).then(function () {
                                            let ProductElm = self.$Elm.getElement('[data-pid="' + productId + '"]');

                                            if (ProductElm) {
                                                new Fx.Scroll(window.document, {
                                                    duration: animationDuration
                                                }).start(0, scrollPosition.y);
                                            }
                                        });
                                    }
                                }
                            }).inject(self.$ProductContainer);

                            if (DEBUG) {
                                console.log('open Product', Instance.getType());
                            }

                            self.getElm().setStyle('height', null);
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

            if (DEBUG) {
                console.log('->showList()');
            }

            let self = this;
            productOpened = false;

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

                    let children = self.$Elm.getChildren();

                    self.$Elm.getAllPrevious().forEach(function (node) {
                        children.push(node);
                    });

                    children.setStyles({
                        display: null
                    });

                    moofx(children).animate({
                        opacity: 1
                    }, {
                        duration: animationDuration,
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

            const self = this;

            return new Promise(function (resolve) {
                moofx(self.$ProductContainer).animate({
                    opacity: 0
                }, {
                    duration: animationDuration,
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
