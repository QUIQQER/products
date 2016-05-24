/**
 * @module package/quiqqer/products/bin/controls/products/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Produkte Suche
 * - Zeigt eine Suchmaske an
 * - Zeigt die Ergebnisse an
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require Locale
 * @require package/quiqqer/products/bin/controls/products/search/Form
 * @require package/quiqqer/products/bin/controls/products/search/Result
 * @require css!package/quiqqer/products/bin/controls/products/search/Search.css
 *
 * @events onSearch [this]
 * @events onSearchBegin [this]
 * @events onClick [this, selected]
 * @events onDblClick [this, selected]
 */
define('package/quiqqer/products/bin/controls/products/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Locale',
    'package/quiqqer/products/bin/controls/products/search/Form',
    'package/quiqqer/products/bin/controls/products/search/Result',

    'css!package/quiqqer/products/bin/controls/products/search/Search.css'

], function (QUI, QUIControl, QUIButton, QUILocale, Form, Result) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            '$onInject',
            'toggleSearch'
        ],

        options: {
            sortOn: false,
            sortBy: false,
            limit : false,
            sheet : 1,

            injectShow: true
        },

        /**
         * construct
         *
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.$Elm    = null;
            this.$Result = null;
            this.$Form   = null;

            this.$FxResult = null;
            this.$FxForm   = null;

            this.$searchHide      = false;
            this.$FormContainer   = null;
            this.$CloserContainer = null;
            this.$ResultContainer = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-search',
                styles : {
                    opacity: 0
                }
            });

            // search form
            this.$FormContainer = new Element('div', {
                'class': 'products-search-form--form-container'
            }).inject(this.$Elm);

            this.$CloserContainer = new Element('div', {
                'class': 'products-search-form--closer-container',
                html   : '<span class="fa fa-arrow-left"></span>',
                events : {
                    click: this.toggleSearch
                }
            }).inject(this.$Elm);

            this.$ResultContainer = new Element('div', {
                'class': 'products-search-form--result-container'
            }).inject(this.$Elm);

            this.$Form = new Form({
                events: {
                    onSearchBegin: function () {
                        self.fireEvent('searchBegin', [self]);
                    },

                    onSearch: function (SF, result) {
                        this.$Result.setData(result);
                        self.fireEvent('search', [self]);
                    }.bind(this)
                }
            });

            this.$Result = new Result({
                events: {
                    onRefresh: function (Result, options) {
                        self.$Form.setAttribute('sheet', options.page);
                        self.$Form.setAttribute('limit', options.perPage);
                        self.$Form.setAttribute('sortOn', options.sortOn);
                        self.$Form.setAttribute('sortBy', options.sortBy);

                        self.$Form.search();
                    },

                    onClick: function () {
                        self.fireEvent('click', [self, self.$Result.getSelected()]);
                    },

                    onDblClick: function () {
                        self.fireEvent('dblClick', [self, self.$Result.getSelected()]);
                    }
                }
            });

            this.$FxForm   = QUI.fx(this.$FormContainer);
            this.$FxResult = QUI.fx(this.$ResultContainer);

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Result.inject(this.$ResultContainer);
            this.$Form.inject(this.$FormContainer);

            if (this.getAttribute('injectShow')) {
                this.show();
            }
        },

        /**
         * Show the control
         *
         * @returns {Promise}
         */
        show: function () {
            return new Promise(function (resolve) {
                moofx(this.$Elm).animate({
                    opacity: 1
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Hide the control
         *
         * @returns {Promise}
         */
        hide: function () {
            return new Promise(function (resolve) {
                moofx(this.$Elm).animate({
                    opacity: 1
                }, {
                    duration: 250,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * resize the control
         *
         * @return {Promise}
         */
        resize: function () {
            return Promise.all([
                this.$Result.resize(),
                this.$Form.resize()
            ]);
        },

        /**
         * Execute the search
         *
         * @returns {Promise}
         */
        search: function () {
            return this.$Form.search();
        },

        /**
         * Return the selected data
         *
         * @returns {Array}
         */
        getSelected: function () {
            return this.$Result.getSelected();
        },

        /**
         * Toggle search
         *
         * @return {Promise}
         */
        toggleSearch: function () {
            if (this.$searchHide) {
                return this.showSearch();
            }

            return this.hideSearch();
        },

        /**
         * Hide the search
         *
         * @return {Promise}
         */
        hideSearch: function () {
            var size  = this.getElm().getSize(),
                Arrow = this.$CloserContainer.getElement('.fa');

            return Promise.all([
                this.$FxForm.animate({
                    opacity: 0,
                    padding: 0,
                    width  : 0
                }),
                this.$FxResult.animate({
                    width: size.x - 20
                })
            ]).then(function () {
                this.$searchHide = true;

                Arrow.removeClass('fa-arrow-left');
                Arrow.addClass('fa-search');

                return this.$Result.resize();
            }.bind(this));
        },

        /**
         * Show the search
         *
         * @return {Promise}
         */
        showSearch: function () {
            var size  = this.getElm().getSize(),
                Arrow = this.$CloserContainer.getElement('.fa');

            return Promise.all([
                this.$FxForm.animate({
                    opacity: 1,
                    padding: '0 20px 0 0',
                    width  : 280
                }),
                this.$FxResult.animate({
                    width: size.x - 300
                })
            ]).then(function () {
                this.$searchHide = false;

                Arrow.removeClass('fa-search');
                Arrow.addClass('fa-arrow-left');

                return this.$Result.resize();
            }.bind(this));
        }
    });
});
