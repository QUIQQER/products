/**
 * @module package/quiqqer/products/bin/controls/fields/search/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * Felder suche
 */
define('package/quiqqer/products/bin/controls/fields/search/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'Locale',
    'package/quiqqer/products/bin/controls/fields/search/Search',

    'css!package/quiqqer/products/bin/controls/fields/search/Window.css'

], function (QUI, QUIControl, QUIConfirm, Grid, Fields, QUILocale, Search) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/fields/search/Window',

        Binds: [
            'submit',
            '$onOpen',
            '$onResize'
        ],

        options: {
            maxHeight      : 600,
            maxWidth       : 800,
            icon           : 'fa fa-file-text-o',
            title          : QUILocale.get(lg, 'fields.window.search.title'),
            autoclose      : true,
            multiple       : false,
            fieldTypeFilter: false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/system', 'accept'),
                textimage: 'fa fa-search'
            },

            showsearchableonly: false // only list searchable fields
        },

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;
            this.$Grid   = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on resize
         */
        $onResize: function () {
            this.$Search.resize();
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win) {
            var Content = Win.getContent();
            Content.set('html', '');

            Win.Loader.show();

            this.$Search = new Search({
                fieldTypeFilter   : this.getAttribute('fieldTypeFilter'),
                multiple          : this.getAttribute('multiple'),
                showsearchableonly: this.getAttribute('showsearchableonly'),
                events            : {
                    onSubmit: this.submit,
                    onLoad  : function () {
                        Win.Loader.hide();
                    }
                }
            }).inject(Content);

            this.$onResize();
        },

        /**
         * Submit
         */
        submit: function () {
            var ids = this.$Search.getSelected();

            if (!ids.length) {
                return;
            }

            this.fireEvent('submit', [this, ids]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
