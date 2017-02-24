/**
 * @module package/quiqqer/products/bin/controls/frontend/search/Suggest
 * @author www.pcsg.de (Henning Leutz)
 *
 * Suggest search für produkte
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 * @require URI
 */
define('package/quiqqer/products/bin/controls/frontend/search/Suggest', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'URI'

], function (QUI, QUIControl, QUIAjax, QUILocale, URI) {
    "use strict";

    var lg      = 'quiqqer/products',
        project = false,
        lang    = false,
        siteid  = false;

    if (typeof QUIQQER_PROJECT !== 'undefined' && 'name' in QUIQQER_PROJECT) {
        project = QUIQQER_PROJECT.name;
    }

    if (typeof QUIQQER_PROJECT !== 'undefined' && 'lang' in QUIQQER_PROJECT) {
        lang = QUIQQER_PROJECT.lang;
    }

    if (typeof QUIQQER_SITE !== 'undefined' && 'id' in QUIQQER_SITE) {
        siteid = QUIQQER_SITE.id;
    }

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/search/Suggest',

        Binds: [
            '$onImport',
            '$onInject',
            '$keyup',
            '$search',
            '$renderSearch',
            '$hideResults'
        ],

        options: {
            siteid      : siteid,
            project     : project,
            lang        : lang,
            delay       : 500,
            styles      : false,
            globalsearch: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Form   = null;
            this.$loaded = false;

            this.$isMobile   = false;
            this.$isImported = false;

            this.$MobileSuggest = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });

            var isMobile = function () {
                var winX       = QUI.getWindowSize().x;
                this.$isMobile = (winX < 768);
            }.bind(this);

            QUI.addEvent('resize', isMobile);
            isMobile();
        },

        /**
         * Create the domnode
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-search-suggest'
            });

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            // look if template exists in html
            var Node = document.getElement(
                '[data-qui="package/quiqqer/products/bin/controls/frontend/search/Suggest"]'
            );

            if (Node) {
                this.$Elm.set('html', Node.get('html'));
                this.$onImport();
                return;
            }

            // load the html template
            QUIAjax.get('package_quiqqer_products_ajax_controls_search_suggestTemplate', function (result) {
                this.$Elm.set('html', result);
                this.$onImport();
            }.bind(this), {
                'package': 'quiqqer/products',
                project  : JSON.encode(QUIQQER_PROJECT),
                siteId   : QUIQQER_SITE.id
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            if (this.$isImported) {
                return;
            }

            this.$isImported = true;

            this.$Form   = this.$Elm.getElement('form');
            this.$Input  = this.$Form.getElement('[type="search"]');
            this.$Button = this.$Form.getElement('.quiqqer-products-search-suggest-form-button');

            require([
                'package/quiqqer/products/bin/controls/frontend/search/MobileSuggest'
            ], function (MobileSuggest) {
                this.$MobileSuggest = new MobileSuggest({
                    project     : this.getAttribute('project'),
                    lang        : this.getAttribute('lang'),
                    globalsearch: this.getAttribute('globalsearch')
                });
            }.bind(this), function (err) {
                console.error(err);
            });

            this.$Button.addEvent('click', function () {
                if (this.$isMobile && this.$MobileSuggest) {
                    this.$MobileSuggest.open();
                }
            }.bind(this));

            this.$Form.addEvent('submit', function (event) {
                if (this.$isMobile) {
                    event.stop();
                    return;
                }

                var Active = this.$DropDown.getElement('li.active');

                if (Active) {
                    event.stop();
                    return;
                }

                if (!("history" in window)) {
                    return;
                }

                if (QUIQQER_SITE.type !== 'quiqqer/products:types/search' &&
                    QUIQQER_SITE.type !== 'quiqqer/products:types/category') {
                    return;
                }

                event.stop();

                var ProductListNode = document.getElement(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                );

                if (!ProductListNode) {
                    return;
                }

                var ProductList = QUI.Controls.getById(ProductListNode.get('data-quiid'));

                if (!ProductList) {
                    return;
                }

                var Uri = URI(window.location);

                Uri.addSearch('search', this.$Input.value);

                window.history.pushState({}, "", Uri.toString());

                ProductList.$onFilterChange();
            }.bind(this));

            this.$Input.addEvents({
                keyup: this.$keyup,
                blur : this.$hideResults,
                focus: function () {
                    if (this.$Input.value !== '') {
                        this.$resetResults();
                        this.$showResults().then(this.$search).then(this.$renderSearch);
                    }
                }.bind(this)
            });

            try {
                this.$Input.addEventListener('search', function () {
                    if (this.$Input.value === '') {
                        this.$hideResults();
                    }
                }.bind(this));
            } catch (e) {
            }

            this.$DropDown = new Element('div', {
                'class': 'quiqqer-products-search-suggest-dropdown',
                styles : {
                    display: 'none',
                    opacity: 0
                }
            }).inject(this.$Form);
        },

        /**
         * event: keyup  trigger search with delay
         */
        $keyup: function (event) {
            if (this.$timer) {
                clearTimeout(this.$timer);
            }

            switch (event.key) {
                case 'esc':
                    this.$Input.value = '';
                    this.$hideResults();
                    event.stop();
                    break;

                case 'enter':
                    var Active = this.$DropDown.getElement('li.active');

                    if (Active) {
                        Active.fireEvent('click', {
                            target: Active
                        });
                        event.stop();
                        return;
                    }

                    if (QUIQQER_SITE.type === 'quiqqer/products:types/search' ||
                        QUIQQER_SITE.type === 'quiqqer/products:types/category') {
                        this.$hideResults();
                        return;
                    }
                    break;

                case 'up':
                    this.$up();
                    event.stop();
                    return;

                case 'down':
                    this.$down();
                    event.stop();
                    return;
            }

            this.$resetResults();
            this.$showResults();

            this.$timer = (function () {
                if (this.$Input.value === '') {
                    return this.$hideResults();
                }
                this.$search().then(this.$renderSearch);
            }).delay(this.getAttribute('delay'), this);
        },

        /**
         * Execute search
         */
        $search: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_search_frontend_suggestRendered', resolve, {
                    'package'   : 'quiqqer/products',
                    siteId      : this.getAttribute('siteid'),
                    project     : JSON.encode({
                        name: this.getAttribute('project'),
                        lang: this.getAttribute('lang')
                    }),
                    searchParams: JSON.encode({
                        freetext: this.$Input.value
                    }),
                    globalsearch: this.getAttribute('globalsearch')
                });
            }.bind(this));
        },

        /**
         * set the results to the dropdown
         *
         * @param {string} data
         * @return {Promise}
         */
        $renderSearch: function (data) {
            if (data === '') {
                this.$DropDown.set(
                    'html',

                    '<span class="quiqqer-products-search-suggest-dropdown-noproducts">' +
                    QUILocale.get(lg, 'message.product.search.empty') +
                    '</span>'
                );

                return this.$showResults();
            }

            this.$DropDown.set('html', data);

            this.$DropDown.getElements('li').addEvents({
                mousedown: function (event) {
                    event.stop();
                },
                click    : function (event) {
                    var Target = event.target;

                    if (Target.nodeName !== 'LI') {
                        Target = Target.getParent('li');
                    }

                    window.location = Target.get('data-url');
                }
            });

            return this.$showResults();
        },

        /**
         * Reset the results, set a loader to the dropdown
         */
        $resetResults: function () {
            this.$DropDown.set(
                'html',
                '<span class="quiqqer-products-search-suggest-dropdown-loader fa fa-spinner fa-spin"></span>'
            );
        },

        /**
         * Hide the results dropdown
         *
         * @returns {Promise}
         */
        $hideResults: function () {
            return new Promise(function (resolve) {
                moofx(this.$DropDown).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        this.$DropDown.setStyle('display', 'none');
                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * Show the results dropdown
         *
         * @returns {Promise}
         */
        $showResults: function () {
            return new Promise(function (resolve) {
                this.$DropDown.setStyle('display', null);

                moofx(this.$DropDown).animate({
                    opacity: 1
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Move up to next result
         */
        $up: function () {
            var Active = this.$DropDown.getElement('li.active');

            if (!Active) {
                Active = this.$DropDown.getFirst('ul li');
            }

            if (!Active) {
                return;
            }

            var Previous = Active.getPrevious();

            if (!Previous) {
                Previous = this.$DropDown.getLast('ul li');
            }

            Active.removeClass('active');
            Previous.addClass('active');
        },

        /**
         * Move down to next result
         */
        $down: function () {
            var Active = this.$DropDown.getElement('li.active');

            if (!Active) {
                Active = this.$DropDown.getLast('ul li');
            }

            if (!Active) {
                return;
            }

            var Next = Active.getNext();

            if (!Next) {
                Next = this.$DropDown.getFirst('ul li');
            }

            Active.removeClass('active');
            Next.addClass('active');
        }
    });
});
