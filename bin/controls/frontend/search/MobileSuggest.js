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
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.html',
    'css!package/quiqqer/products/bin/controls/frontend/search/MobileSuggest.css'

], function (QUI, QUIControl, QUIBackground, QUIAjax, QUILocale, Mustache, template) {
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
            '$search'
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

            this.$Close = null;
            this.$Input = null;
            this.$timer = null;

            this.$created = false;
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

            this.$Background = new QUIBackground();
            this.$Background.inject(document.body);

            this.$Close = this.$Elm.getElement('.quiqqer-products-mobileSuggest-close');
            this.$Input = this.$Elm.getElement('.quiqqer-products-mobileSuggest-search input');

            this.$Close.addEvents({
                click: this.close
            });

            this.$Input.addEvents({
                keyup: this.$keyup
            });

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

        $renderSearch: function () {

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
        }
    });
});
