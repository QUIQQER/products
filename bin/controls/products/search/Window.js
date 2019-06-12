/**
 * @module package/quiqqer/products/bin/controls/products/search/Window
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/search/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/products/search/Search',
    'Locale'

], function (QUI, QUIControl, QUIButton, QUIConfirm, Search, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Window',

        Binds: [
            'search',
            'submit',
            '$onOpen',
            '$onOpenBegin',
            '$onResize',
            '$onSearch',
            '$onSearchBegin',
            'tableRefresh'
        ],

        options: {
            maxHeight   : 600,
            maxWidth    : 800,
            icon        : 'fa fa-search',
            title       : 'Produktsuche',
            autoclose   : true,
            multiple    : false,
            productTypes: []
        },

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onOpen     : this.$onOpen,
                onOpenBegin: this.$onOpenBegin
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
         * event: on open begin
         */
        $onOpenBegin: function () {
            var size = document.body.getSize();

            var width  = size.x - 100;
            var height = size.y - 100;

            if (width > 1400) {
                width = 1400;
            }

            if (height > 1200) {
                height = 1200;
            }

            this.setAttribute('maxWidth', width);
            this.setAttribute('maxHeight', height);
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win) {
            var self    = this,
                Content = Win.getContent();

            this.setAttribute('maxWidth', 1400);

            Content.set('html', '');

            this.$Search = new Search({
                productTypes: this.getAttribute('productTypes'),
                searchbutton: false,
                events      : {
                    onDblClick: function () {
                        self.submit();
                    },

                    onSearchBegin: function () {
                        self.Loader.show();
                    },

                    onSearch: function () {
                        self.Loader.hide();
                    }
                }
            }).inject(Content);

            this.$Search.resize();
            this.$Search.search();
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
            var selected = this.$Search.getSelected();

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
