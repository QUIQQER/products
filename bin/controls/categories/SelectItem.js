/**
 * Country select item
 */
define('package/quiqqer/products/bin/controls/categories/SelectItem', [

    'qui/controls/Control',
    'Locale',
    'package/quiqqer/products/bin/classes/Categories',

    'css!package/quiqqer/products/bin/controls/categories/SelectItem.css'

], function (QUIControl, QUILocale, Handler) {
    "use strict";

    var Categories = new Handler();

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/SelectItem',

        Binds: [
            '$onInject'
        ],

        options: {
            categoryId: false
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
                'class': 'quiqqer-category-selectItem smooth',
                html   : '<span class="quiqqer-category-selectItem-icon fa fa-percent"></span>' +
                         '<span class="quiqqer-category-selectItem-text">&nbsp;</span>' +
                         '<span class="quiqqer-category-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon    = Elm.getElement('.quiqqer-category-selectItem-icon');
            this.$Text    = Elm.getElement('.quiqqer-category-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqqer-category-selectItem-destroy');

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

            Categories.getChild(
                this.getAttribute('categoryId')
            ).then(function (data) {

                var locale = QUILocale.get(
                    'quiqqer/products',
                    'products.category.' + data.id + '.title'
                );

                self.$Text.set({
                    html: '#' + data.id + ' - ' + locale
                });

            }).catch(function () {
                self.$Icon.removeClass('fa-percent');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        }
    });
});
