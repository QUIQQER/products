/**
 *
 * @module package/quiqqer/products/bin/controls/categories/search/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require package/quiqqer/discount/bin/classes/Handler
 */
define('package/quiqqer/products/bin/controls/categories/search/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'package/quiqqer/discount/bin/classes/Handler',
    'Locale',
    'package/quiqqer/products/bin/controls/categories/Sitemap',

    'css!package/quiqqer/products/bin/controls/categories/search/Window.css'

], function (QUI, QUIControl, QUIConfirm, Handler, QUILocale, Sitemap) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/categories/search/Window',

        Binds: [
            '$onOpen'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 400,
            icon     : 'fa fa-shopping-basket',
            title    : 'Kategorie-Auswahl',
            autoclose: false,
            multiple : false,
            message  : false,

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

            this.$Sitemap = null;

            this.$ButtonCancel = null;
            this.$ButtonSubmit = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win) {
            var Content = Win.getContent();

            Content.set('html', '');
            Content.addClass('discount-search');

            if (this.getAttribute('message')) {
                new Element('div', {
                    html: this.getAttribute('message')
                }).inject(Content);
            }

            this.$Sitemap = new Sitemap({
                multiple: this.getAttribute('multiple')
            }).inject(Content);
        },

        /**
         * Submit
         */
        submit: function () {
            if (!this.$Sitemap.getSelected().length) {
                return;
            }

            var values = this.$Sitemap.getSelected().map(function (Item) {
                return Item.getAttribute('value');
            });

            this.fireEvent('submit', [this, values]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
