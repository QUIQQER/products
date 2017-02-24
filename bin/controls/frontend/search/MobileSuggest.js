/**
 * @module package/quiqqer/products/bin/controls/frontend/search/MobileSuggest
 * @author www.pcsg.de (Henning Leutz)
 *
 * Suggest search für produkte
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 * @require Mustache
 * @require text!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.html
 * @require css!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.css
 */
define('package/quiqqer/products/bin/controls/frontend/search/MobileSuggest', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/utils/Background',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.html',
    'css!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.css'

], function (QUI, QUIControl, QUIBackground, QUILoader, QUIAjax, QUILocale, Mustache, template) {
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
        Type   : 'package/quiqqer/products/bin/controls/frontend/search/MobileSuggest',

        Binds: [
            'open',
            'close',
            '$keyup',
            '$search',
            '$renderSearch',
            '$hideResults',
            '$showLoader'
        ],

        options: {
            siteid      : siteid,
            project     : project,
            lang        : lang,
            delay       : 500,
            globalsearch: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Background = null;
            this.$Close      = null;
            this.$Input      = null;
            this.$Result     = null;

            this.$created = false;
            this.$timer   = null;
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {

            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-mobileSuggest',
                html   : Mustache.render(template)
            });

            this.$Elm.setStyles({
                opacity: 0,
                top    : -50
            });

            this.$Background = new QUIBackground({
                opacity: 0.85,
                styles : {
                    backgroundColor: '#1a1c1d'
                }
            });

            this.$Background.inject(document.body);

            this.$Close     = this.$Elm.getElement('.quiqqer-products-mobileSuggest-close');
            this.$Input     = this.$Elm.getElement('.quiqqer-products-mobileSuggest-search input');
            this.$Result    = this.$Elm.getElement('.quiqqer-products-mobileSuggest-results');
            this.$ResultCtn = this.$Elm.getElement('.quiqqer-products-mobileSuggest-results-container');

            this.$Close.addEvents({
                click: this.close
            });

            this.$Input.addEvents({
                keyup: this.$keyup
            });


            this.Loader = new QUILoader({
                styles: {
                    background: 'transparent'
                }
            }).inject(this.$ResultCtn);


            this.$created = true;

            return this.$Elm;
        },

        /**
         * Open the search
         *
         * @return {Promise}
         */
        open: function () {
            if (!this.$Elm) {
                this.create().inject(document.body);
            }

            return this.$Background.show().then(function () {
                return new Promise(function (resolve) {
                    moofx(this.$Elm).animate({
                        opacity: 1,
                        top    : 0
                    }, {
                        duration: 250,
                        callback: resolve
                    });
                }.bind(this));
            }.bind(this)).then(function () {
                this.$Input.focus();
            }.bind(this));
        },

        /**
         * Close the search
         *
         * @return {Promise}
         */
        close: function () {
            return new Promise(function (resolve) {
                moofx(this.$Elm).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 200,
                    callback: function () {
                        this.$Elm.destroy();
                        this.$Elm = null;
                        resolve();
                    }.bind(this)
                });
            }.bind(this)).then(function () {
                return this.$Background.hide();
            }.bind(this)).then(function () {
                this.$Background.destroy();
                this.$Background = null;
            }.bind(this));
        },

        /**
         *
         * @param event
         */
        $keyup: function (event) {
            if (event.key === 'enter') {
                return;
            }

            if (this.$timer) {
                clearTimeout(this.$timer);
            }

            var ShowLoader = this.$showLoader();

            this.$timer = (function () {
                if (this.$Input.value === '') {
                    return this.$hideResults();
                }

                ShowLoader.then(this.$search)
                          .then(this.$renderSearch);
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
                this.$Result.set(
                    'html',

                    '<span class="quiqqer-products-mobileSuggest-results-noresult">' +
                    QUILocale.get(lg, 'message.product.search.empty') +
                    '</span>'
                );

                return this.$showResults();
            }

            this.$Result.set('html', data);

            this.$Result.getElements('li').addEvents({
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
         * Show the results dropdown
         *
         * @returns {Promise}
         */
        $showResults: function () {
            return this.Loader.hide().then(function () {
                return new Promise(function (resolve) {
                    this.$Result.setStyle('display', null);

                    moofx(this.$Result).animate({
                        opacity: 1
                    }, {
                        duration: 200,
                        callback: resolve
                    });
                }.bind(this));
            }.bind(this));
        },


        /**
         * Hide the results dropdown
         *
         * @returns {Promise}
         */
        $hideResults: function () {
            return new Promise(function (resolve) {
                moofx(this.$Result).animate({
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        this.$Result.setStyle('display', 'none');
                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * Shows the loader and hide the results
         *
         * @returns {Promise}
         */
        $showLoader: function () {
            return this.$hideResults().then(function () {
                return this.Loader.show();
            }.bind(this));
        }
    });
});
