/**
 * @module package/quiqqer/products/bin/controls/products/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/SelectItem', [

    'qui/controls/Control',
    'package/quiqqer/products/bin/classes/Products',

    'css!package/quiqqer/products/bin/controls/products/SelectItem.css'

], function (QUIControl, Handler) {
    "use strict";

    var Products = new Handler();

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/SelectItem',

        Binds: [
            '$onInject'
        ],

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Icon    = null;
            this.$Text    = null;
            this.$Destroy = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLElement}
         */
        create: function () {
            var self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'quiqqer-products-selectItem smooth',
                html   : '<span class="quiqqer-products-selectItem-icon fa fa-shopping-bag"></span>' +
                    '<span class="quiqqer-products-selectItem-text">&nbsp;</span>' +
                    '<span class="quiqqer-products-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon    = Elm.getElement('.quiqqer-products-selectItem-icon');
            this.$Text    = Elm.getElement('.quiqqer-products-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqqer-products-selectItem-destroy');

            this.$Destroy.addEvent('click', function () {
                self.destroy();
            });

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.$Text.set({
                html: '<span class="fa fa-spinner fa-spin"></span>'
            });

            if (!parseInt(this.getAttribute('id'))) {
                (function () {
                    this.$Destroy.click();
                }).delay(500, this);

                return Promise.resolve();
            }

            Products.getDataForSelectItem(
                this.getAttribute('id')
            ).then(function (data) {
                self.$Text.set('html', '#' + data.id + ' - <b>' + data.articleNo + '</b> (' + data.title + ')');
                self.getElm().set('title', '#' + data.id + ' - ' + data.articleNo + ' (' + data.title + ')');
            }).catch(function () {
                self.$Icon.removeClass('fa-shopping-bag');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        }
    });
});
