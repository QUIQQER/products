/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/products/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Locale
 * @require Mustache
 * @require package/quiqqer/products/bin/classes/Products
 * @require package/quiqqer/translator/bin/controls/Create
 * @require text!package/quiqqer/products/bin/controls/products/Create.html
 * @require css!package/quiqqer/products/bin/controls/products/Create.css
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/products/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/translator/bin/controls/Create',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/Create.html',
    'css!package/quiqqer/products/bin/controls/products/Create.css'

], function (QUI, QUIControl, QUILocale, Mustache, Handler, Translation, CategoriesSelect, template) {
    "use strict";

    var lg       = 'quiqqer/products',
        Products = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/Create',

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Translation = null;
            this.$Categories  = null;

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
            var self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'product-create',
                html   : Mustache.render(template, {
                    productTitle     : QUILocale.get('quiqqer/system', 'title'),
                    productNo        : QUILocale.get(lg, 'productNo'),
                    productCategories: QUILocale.get(lg, 'productCategories')
                })
            });

            this.$Translation = new Translation({
                group: 'quiqqer/products'
            }).inject(Elm.getElement('.product-title'));

            this.$Categories = new CategoriesSelect().inject(
                Elm.getElement('.product-categories')
            );

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.fireEvent('loaded');
        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {
            var self = this,
                Elm  = this.getElm();

            return new Promise(function (resolve, reject) {
                var categories = self.$Categories.getValue().trim().split(','),
                    fields     = [],
                    ProductNo  = Elm.getElement('[name="productNo"]');

                console.log(self.$Categories.getValue().trim());
                console.log(categories);

                if (!categories.length) {
                    QUI.getMessageHandler().then(function (MH) {
                        MH.addAttention(
                            'Bitte geben Sie dem Produkt eine Produkt-Kategorie',
                            Elm.getElement('.product-categories')
                        );
                    });

                    return reject('No categories');
                }

                Products.createChild(
                    categories,
                    fields,
                    ProductNo.value
                ).then(resolve).catch(reject);
            });
        }
    });
});
