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
    'qui/controls/windows/Popup',
    'qui/utils/System',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.html',
    'css!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.css'

], function (QUI, QUIControl, QUIBackground, QUILoader, QUIPopup, QUISystemUtils, QUIAjax, QUILocale, Mustache, template) {
    "use strict";

    var ios     = QUISystemUtils.iOSversion(),
        lg      = 'quiqqer/products',
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

        Extends: QUIPopup,
        Type   : 'package/quiqqer/products/bin/controls/frontend/search/MobileSuggest',

        Binds: [
            '$onOpen',
            '$onOpenBegin',
            '$onClose',
            '$onCreate',
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

            this.setAttributes({
                buttons           : false,
                header            : false,
                backgroundClosable: false
            });

            this.$Background = null;
            this.$Close      = null;
            this.$Input      = null;
            this.$Result     = null;
            this.$ResultCtn  = null;

            this.$created = false;
            this.$timer   = null;

            this.addEvents({
                onOpen      : this.$onOpen,
                onOpenBegin : this.$onOpenBegin,
                onCloseBegin: this.$onClose
            });
        },

        /**
         * Create the domnode element
         */
        $onOpenBegin: function () {
            this.getElm().setStyles({
                display: 'none'
            });

            this.$Container = new Element('div', {
                'class': 'quiqqer-products-mobileSuggest',
                html   : Mustache.render(template, {
                    title: QUILocale.get(lg, 'control.search.suggest.mobile.title')
                })
            }).inject(document.body);

            this.$Container.setStyles({
                opacity: 0,
                top    : -50
            });

            this.Background.setAttribute('opacity', 0.85);
            this.Background.setAttribute('styles', {
                backgroundColor: '#1a1c1d'
            });

            this.$Close     = this.$Container.getElement('.quiqqer-products-mobileSuggest-close');
            this.$Input     = this.$Container.getElement('.quiqqer-products-mobileSuggest-search input');
            this.$Result    = this.$Container.getElement('.quiqqer-products-mobileSuggest-results');
            this.$ResultCtn = this.$Container.getElement('.quiqqer-products-mobileSuggest-results-container');

            this.$Close.addEvents({
                click: function () {
                    this.close();
                }.bind(this)
            });

            this.$Input.addEvents({
                keyup: this.$keyup
            });

            if (!ios) {
                this.$Input.set('autofocus', null);
            } else {
                this.$Container.setStyles({
                    opacity: 0,
                    top    : document.body.getScroll().y
                });
            }

            this.Loader.getElm().setStyle('background', 'transparent');
            this.Loader.inject(this.$ResultCtn);

            this.$created = true;

            return this.$Elm;
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            this.$Container.setStyles({
                zIndex: this.Background.getElm().getStyle('zIndex') + 1
            });

            return new Promise(function (resolve) {
                var top = 0;

                if (ios) {
                    top = this.$Container.getStyle('top');
                }

                moofx(this.$Container).animate({
                    opacity: 1,
                    top    : top
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this)).then(function () {
                if (!ios) {
                    this.$Input.focus();
                }
            }.bind(this));
        },

        /**
         * Close the search
         *
         * @return {Promise}
         */
        $onClose: function () {
            return new Promise(function (resolve) {
                moofx(this.$Container).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 200,
                    callback: function () {
                        this.$Container.destroy();
                        this.$Container = null;
                        resolve();
                    }.bind(this)
                });
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
                    return this.Loader.hide().then(this.$hideResults);
                }

                ShowLoader.then(this.$search).then(this.$renderSearch);
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

                return this.Loader.hide().then(this.$showResults);
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
                this.$ResultCtn.setStyle('height', null);

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
                this.$ResultCtn.setStyle('height', 200);

                return this.Loader.show();
            }.bind(this));
        }
    });
});
