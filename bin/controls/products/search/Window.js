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
    'Locale'

], function (QUI, QUIControl, QUIConfirm, Search, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Window',

        Binds: [
            '$onOpen',
            '$onResize',
            'tableRefresh'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 800,
            icon     : 'fa fa-search',
            title    : 'Produktsuche',
            autoclose: false,
            multiple : false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/system', 'accept'),
                textimage: 'fa fa-search'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;

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
            Win.getContent().set('html', '');

            this.$Search = new Search().inject(Win.getContent());
            this.$onResize();
        },

        /**
         * Submit
         */
        submit: function () {
            var ids = this.$Search.getSelectedData().map(function (Entry) {
                return Entry.id;
            });

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
