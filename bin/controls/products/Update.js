/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/products/Update
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Locale
 * @require Mustache
 * @require package/quiqqer/products/bin/classes/Products
 * @require package/quiqqer/translator/bin/controls/Update
 * @require text!package/quiqqer/products/bin/controls/products/Update.html
 * @require css!package/quiqqer/products/bin/controls/products/Update.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/products/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/products/Update.html',
    'css!package/quiqqer/products/bin/controls/products/Update.css'

], function (QUI, QUIControl, QUILocale, Mustache, Handler, Translation, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/Update',

        Binds: [
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Translation = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var self = this;


            self.fireEvent('loaded');
        },

        /**
         * event : on inject
         */
        $onInject: function () {

        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {

        }
    });
});
