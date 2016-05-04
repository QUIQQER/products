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
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/products/search/Search',
    'package/quiqqer/products/bin/controls/products/search/Result',
    'Locale'

], function (QUI, QUIControl, QUIConfirm, Search, Result, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Window',

        Binds: [
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
            autoclose: false,
            multiple : false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/system', 'search'),
                textimage: 'fa fa-search'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;
            this.$Result = null;

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
                }
            }).inject(this.$SearchResult);

            this.setAttribute('maxWidth', 800);

            this.resize().then(function () {
                this.$SearchResult.setStyle('opacity', 0);
                this.$SearchResult.setStyle('display', 'block');
                this.$Result.resize();

                moofx(this.$SearchResult).animate({
                    opacity: 1
                });
            }.bind(this));
        },

        /**
         * event : on search .. ing
         */
        $onSearch: function (result) {


            this.Loader.hide();
        },

        /**
         * Submit
         */
        submit: function () {
            this.$Search.search();
            // var ids = this.$Search.getSelectedData().map(function (Entry) {
            //     return Entry.id;
            // });
            //
            // if (!ids.length) {
            //     return;
            // }
            //
            // this.fireEvent('submit', [this, ids]);
            //
            // if (this.getAttribute('autoclose')) {
            //     this.close();
            // }
        }
    });
});
