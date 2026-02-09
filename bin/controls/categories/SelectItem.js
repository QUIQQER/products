/**
 * Country select item
 *
 * @event onClick
 * @event onDestroy
 * @event onChange [self, value]
 */
define('package/quiqqer/products/bin/controls/categories/SelectItem', [

    'qui/controls/Control',
    'Locale',
    'package/quiqqer/products/bin/controls/categories/search/Window',
    'package/quiqqer/products/bin/classes/Categories',

    'css!package/quiqqer/products/bin/controls/categories/SelectItem.css'

], function (QUIControl, QUILocale, CategorySearch, Handler) {
    "use strict";

    const Categories = new Handler();

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/SelectItem',

        Binds: [
            '$onInject'
        ],

        options: {
            categoryId: false,
            removeable: true,
            editable  : false
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
            const self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'quiqqer-category-selectItem smooth',
                html   : '<span class="quiqqer-category-selectItem-icon fa fa-sitemap"></span>' +
                    '<span class="quiqqer-category-selectItem-text">&nbsp;</span>' +
                    '<span class="quiqqer-category-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon    = Elm.getElement('.quiqqer-category-selectItem-icon');
            this.$Text    = Elm.getElement('.quiqqer-category-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqqer-category-selectItem-destroy');

            if (this.getAttribute('removeable') === false) {
                this.$Destroy.setStyle('display', 'none');
            }

            this.$Destroy.addEvent('click', function (e) {
                e.stop();
                self.destroy();
            });

            if (this.getAttribute('editable')) {
                Elm.setStyle('cursor', 'pointer');
                Elm.addEvent('click', function () {
                    self.openEditDialog();
                });
            }

            Elm.addEvent('click', function () {
                self.fireEvent('click', [self]);
            });

            return Elm;
        },

        loading: function () {
            this.$Text.set({
                html: '<span class="fa fa-spinner fa-spin"></span>'
            });
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            const self = this;

            this.loading();

            if (this.getAttribute('categoryId') === 0) {
                self.$Text.set({
                    html: QUILocale.get(
                        'quiqqer/products',
                        'products.category.0.title'
                    )
                });
                return;
            }

            Categories.getChild(
                this.getAttribute('categoryId')
            ).then(function (data) {

                const locale = QUILocale.get(
                    'quiqqer/products',
                    'products.category.' + data.id + '.title'
                );

                self.$Text.set({
                    html: locale + ' (#' + data.id + ')'
                });

            }).catch(function () {
                self.$Icon.removeClass('fa-percent');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * open the edit dialog - category search
         */
        openEditDialog: function () {
            if (!this.getAttribute('editable')) {
                return;
            }

            const categoryId = this.getAttribute('categoryId');

            new CategorySearch({
                events: {
                    onSubmit: function (Win, values) {
                        if (values[0] == categoryId) {
                            return;
                        }

                        Win.close();

                        if (values[0] === '') {
                            values[0] = 0;
                        }

                        this.setAttribute('categoryId', parseInt(values[0]));
                        this.refresh();

                        this.fireEvent('onChange', [this, parseInt(values[0])]);
                    }.bind(this)
                }
            }).open();
        }
    });
});
