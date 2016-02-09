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
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/translator/bin/controls/Create',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/Create.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Create.css'

], function (QUI, QUIControl, QUILocale, Mustache, Handler, FieldHandler, Translation, CategoriesSelect,
             template, templateField) {
    "use strict";

    var lg       = 'quiqqer/products',
        Products = new Handler(),
        Fields   = new FieldHandler();

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
                    productCategories: QUILocale.get(lg, 'productCategories'),
                    productCategory  : QUILocale.get(lg, 'productCategory')
                })
            });

            var ProductCategory = Elm.getElement('[name="product-category"]');

            this.$Translation = new Translation({
                group: 'quiqqer/products'
            }).inject(Elm.getElement('.product-title'));

            this.$Categories = new CategoriesSelect({
                events: {
                    onChange: function () {
                        var ids = self.$Categories.getValue();
                        var Row = ProductCategory.getParent('tr');

                        if (ids === '') {
                            ids = [];
                        } else {
                            ids = ids.split(',');
                        }

                        if (!ids.length) {
                            Row.setStyles({
                                display : 'inline-block',
                                overflow: 'hidden',
                                opacity : 1,
                                position: 'relative'
                            });

                            moofx(Row).animate({
                                height : 0,
                                opacity: 0
                            }, {
                                duration: 200,
                                callback: function () {
                                    Row.setStyle('display', 'none');
                                    ProductCategory.set('html', '');
                                }
                            });

                            return;
                        }

                        Row.setStyles({
                            display : 'inline',
                            'float' : 'left',
                            height  : 0,
                            overflow: 'hidden',
                            position: 'relative'
                        });

                        var i, len, id;

                        for (i = 0, len = ids.length; i < len; i++) {
                            id = ids[i];
                            if (ProductCategory.getElement('[value="' + id + '"]')) {
                                continue;
                            }

                            new Element('option', {
                                value: id,
                                html : QUILocale.get(lg, 'products.category.' + id + '.title')
                            }).inject(ProductCategory);
                        }

                        moofx(Row).animate({
                            height : 50,
                            opacity: 1
                        }, {
                            duration: 200,
                            callback: function () {
                                Row.setStyles({
                                    display: 'table-row',
                                    'float': null

                                });
                            }
                        });
                    }
                }
            }).inject(
                Elm.getElement('.product-categories')
            );

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this,
                Elm  = this.getElm();

            var StandardFields = Elm.getElement('.product-standardfield tbody');
            var Data           = Elm.getElement('.product-data tbody');

            Promise.all([
                Fields.getSystemFields(),
                Fields.getStandardFields()
            ]).then(function (result) {
                var i, len;
                var systemFields   = result[0],
                    standardFields = result[1];

                var diffFields = standardFields.filter(function (value) {
                    for (var i = 0, len = systemFields.length; i < len; i++) {
                        if (value.id === systemFields[i].id) {
                            return false;
                        }
                    }
                    return true;
                });

                // standard felder
                for (i = 0, len = diffFields.length; i < len; i++) {
                    new Element('tr', {
                        html: Mustache.render(templateField, {
                            fieldTitle: QUILocale.get(lg, 'products.field.' + diffFields[i].id + '.title')
                        })
                    }).inject(StandardFields);
                }

                // systemfields
                for (i = 0, len = systemFields.length; i < len; i++) {
                    new Element('tr', {
                        html: Mustache.render(templateField, {
                            fieldTitle: QUILocale.get(lg, 'products.field.' + systemFields[i].id + '.title')
                        })
                    }).inject(Data);
                }

                self.fireEvent('loaded');
            });
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
