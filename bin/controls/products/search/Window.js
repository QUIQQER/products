/**
 *
 * @module package/quiqqer/products/bin/controls/products/search/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/Fields
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/products/search/Window.css
 */
define('package/quiqqer/products/bin/controls/products/search/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/products/search/Search',
    'package/quiqqer/products/bin/controls/products/search/Result',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUIConfirm, Search, Result, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Window',

        Binds: [
            'search',
            'submit',
            '$onOpen',
            '$onResize',
            '$onSearch',
            '$onSearchBegin',
            'tableRefresh'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 300,
            icon     : 'fa fa-search',
            title    : 'Produktsuche',
            autoclose: true,
            multiple : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;
            this.$Result = null;

            this.$ButtonCancel = null;
            this.$ButtonSubmit = null;
            this.$ButtonSearch = null;

            this.$ButtonsSearchContainer = null;
            this.$ButtonsResultContainer = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on resize
         *
         * @return {Promise}
         */
        $onResize: function () {
            return this.$Search.resize();
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win) {
            var Content = Win.getContent();

            Content.set({
                html: '<div class="search"></div>' +
                      '<div class="result"></div>'
            });

            this.$SearchContainer = Content.getElement('.search');
            this.$SearchResult    = Content.getElement('.result');

            this.$Search = new Search({
                searchbutton: false,
                events      : {
                    onSearchBegin: this.$onSearchBegin,
                    onSearch     : this.$onSearch
                }
            }).inject(this.$SearchContainer);

            this.$SearchContainer.setStyles({
                'float' : 'left',
                maxWidth: 300
            });

            this.$SearchResult.setStyles({
                display: 'none',
                'float': 'left',
                height : '100%',
                margin : '0 0 0 20px',
                width  : 'calc(100% - 320px)'
            });

            // buttons
            this.$Buttons.set('html', '');

            this.$ButtonsSearchContainer = new Element('div', {
                styles: {
                    'float'  : 'left',
                    maxWidth : 300,
                    textAlign: 'center',
                    width    : '100%'
                }
            }).inject(this.$Buttons);

            this.$ButtonsResultContainer = new Element('div', {
                styles: {
                    display  : 'none',
                    'float'  : 'left',
                    textAlign: 'right',
                    width    : 'calc(100% - 320px)'
                }
            }).inject(this.$Buttons);


            this.$ButtonSearch = new QUIButton({
                text     : QUILocale.get('quiqqer/system', 'search'),
                textimage: 'fa fa-search',
                styles   : {
                    'float': 'none'
                },
                events   : {
                    onClick: this.search
                }
            }).inject(this.$ButtonsSearchContainer);

            this.$ButtonSubmit = new QUIButton({
                text     : QUILocale.get('quiqqer/system', 'accept'),
                textimage: 'fa fa-check',
                styles   : {
                    'float': 'none'
                },
                events   : {
                    onClick: this.submit
                }
            }).inject(this.$ButtonsResultContainer);

            this.$ButtonCancel = new QUIButton({
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove',
                styles   : {
                    'float': 'none'
                },
                events   : {
                    onClick: this.cancel
                }
            }).inject(this.$ButtonsResultContainer);

            this.$ButtonSubmit.hide();
            this.$ButtonCancel.hide();
        },

        /**
         * event: search begin
         */
        $onSearchBegin: function () {
            this.Loader.show();

            if (this.$Result) {
                return;
            }

            this.$Result = new Result({
                styles: {
                    height: '100%'
                },
                events: {
                    onRefresh: function (Result, gridOptions) {

                    },
                    onSubmit : this.submit,
                    onSelect : function (Result, selected) {

                    }
                }
            }).inject(this.$SearchResult);

            this.setAttribute('maxWidth', 900);

            this.resize().then(function () {
                this.$SearchResult.setStyle('opacity', 0);
                this.$SearchResult.setStyle('display', 'block');
                this.$Result.resize();

                this.$ButtonsResultContainer.setStyle('display', null);
                this.$ButtonSubmit.show();
                this.$ButtonCancel.show();

                moofx(this.$SearchResult).animate({
                    opacity: 1
                });
            }.bind(this));
        },

        /**
         * event : on search .. ing
         *
         * @param {Object} Search - Search control
         * @param {Array} result - grid data - product list
         */
        $onSearch: function (Search, result) {
            this.$Result.setData(result);
            this.Loader.hide();
        },

        /**
         * Execute the search
         */
        search: function () {
            this.$Search.search();
        },

        /**
         * Submit
         *
         * @fires onSubmit
         */
        submit: function () {
            var selected = this.$Result.getSelected();

            if (!selected.length) {
                return;
            }

            this.fireEvent('submit', [this, selected]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
