/**
 * Product management
 *
 * @module package/quiqqer/products/bin/controls/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require Locale
 * @require Mustache
 * @require package/quiqqer/products/bin/classes/Products
 * @require text!package/quiqqer/products/bin/controls/products/Product.html
 */
define('package/quiqqer/products/bin/controls/products/Product', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'Mustache',
    'package/quiqqer/translator/bin/controls/Update',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/classes/Fields',

    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale, Mustache,
             Translation, ProductHandler, CategoriesHandler, FieldsHandler,
             templateProductData, templateField) {
    "use strict";

    var lg = 'quiqqer/products';

    var Products   = new ProductHandler(),
        Categories = new CategoriesHandler(),
        Fields     = new FieldsHandler();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Product',

        Binds: [
            'update',
            'openData',
            'openImages',
            'openFiles',
            '$onCreate',
            '$onInject'
        ],

        options: {
            productId: false
        },


        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title'),
                icon : 'fa fa-shopping-bag'
            });

            this.parent(options);

            this.$data        = {};
            this.$Translation = null;

            this.$Data  = null;
            this.$Media = null;
            this.$Files = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        refresh: function () {
            this.parent();
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            this.addButton({
                name     : 'update',
                textimage: 'fa fa-save',
                text     : QUILocale.get('quiqqer/system', 'save'),
                events   : {
                    onClick: function () {

                    }
                }
            });

            // categories
            this.addCategory({
                name  : 'data',
                text  : QUILocale.get('quiqqer/system', 'data'),
                icon  : 'fa fa-shopping-bag',
                events: {
                    onClick: this.openData
                }
            });

            this.addCategory({
                name  : 'images',
                text  : QUILocale.get(lg, 'products.product.panel.category.images'),
                icon  : 'fa fa-picture-o',
                events: {
                    onClick: this.openImages
                }
            });

            this.addCategory({
                name  : 'files',
                text  : QUILocale.get(lg, 'products.product.panel.category.files'),
                icon  : 'fa fa-file-text',
                events: {
                    onClick: this.openFiles
                }
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self      = this,
                productId = this.getAttribute('productId');

            this.Loader.show();

            this.loadData().then(function (productData) {
                self.$data = productData;

                var Content = self.getContent();

                Content.addClass('product-update');

                var dataTemplate = Mustache.render(templateProductData, {
                    productTitle        : QUILocale.get('quiqqer/system', 'title'),
                    productNo           : QUILocale.get(lg, 'productNo'),
                    productCategories   : QUILocale.get(lg, 'productCategories'),
                    productCategory     : QUILocale.get(lg, 'productCategory'),
                    productDefaultFields: QUILocale.get(lg, 'productDefaultFields'),
                    productMasterData   : QUILocale.get(lg, 'productMasterData')
                });

                Content.set({
                    html: '<div class="product-update-data">' + dataTemplate + '</div>' +
                          '<div class="product-update-media"></div>' +
                          '<div class="product-update-files"></div>'
                });

                self.$Data  = Content.getElement('.product-update-data');
                self.$Media = Content.getElement('.product-update-media');
                self.$Files = Content.getElement('.product-update-files');


                self.$Translation = new Translation({
                    'group': 'quiqqer/products',
                    'var'  : 'products.product.' + productId + '.title'
                }).inject(Content.getElement('.product-title'));


                var Data = Content.getElement('.product-data tbody');

                var StandardFields = Content.getElement(
                    '.product-standardfield tbody'
                );


                var categories = self.$data.categories.split(',');

                categories.push(parseInt(self.$data.category));
                categories = categories.filter(function (item) {
                    return item !== '';
                });

                // Felderaufbau
                Promise.all([
                    Categories.getFields(categories),
                    Fields.getSystemFields(),
                    Fields.getStandardFields()
                ]).then(function (result) {
                    var i, len;

                    var categoriesFields = result[0],
                        systemFields     = result[1],
                        standardFields   = result[2];

                    for (i = 0, len = systemFields.length; i < len; i++) {
                        new Element('tr', {
                            'class'       : 'field',
                            html          : Mustache.render(templateField, {
                                fieldTitle: QUILocale.get(lg, 'products.field.' + systemFields[i].id + '.title'),
                                fieldName : 'field-' + systemFields[i].id
                            }),
                            'data-fieldid': systemFields[i].id
                        }).inject(Data);
                    }

                    self.Loader.hide();
                    self.getCategory('data').click();
                });
            });
        },

        /**
         * Return the product data
         *
         * @returns {Promise}
         */
        loadData: function () {
            return Products.getChild(this.getAttribute('productId'));
        },

        /**
         * Open the data
         *
         * @return {Promise}
         */
        openData: function () {
            var self = this;

            return new Promise(function (resolve) {

                moofx([self.$Media, self.$Files]).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: function () {
                        moofx(self.$Data).animate({
                            opacity: 1,
                            top    : 0
                        }, {
                            duration: 200,
                            callback: resolve
                        });
                    }
                });
            });
        },

        /**
         * Open the image list
         *
         * @return {Promise}
         */
        openImages: function () {
            var self = this;

            return new Promise(function (resolve) {

                moofx([self.$Data, self.$Files]).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: function () {
                        moofx(self.$Media).animate({
                            opacity: 1,
                            top    : 0
                        }, {
                            duration: 200,
                            callback: resolve
                        });
                    }
                });
            });
        },

        /**
         * Open the file list
         *
         * @return {Promise}
         */
        openFiles: function () {
            var self = this;

            return new Promise(function (resolve) {

                moofx([self.$Data, self.$Media]).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: function () {
                        moofx(self.$Files).animate({
                            opacity: 1,
                            top    : 0
                        }, {
                            duration: 200,
                            callback: resolve
                        });
                    }
                });
            });
        },

        /**
         * Saves the product data
         * @returns {Promise}
         */
        update: function () {
            return new Promise(function () {

            });
        }
    });
});
