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
    'qui/utils/Form',
    'controls/grid/Grid',
    'Locale',
    'Mustache',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUIFormUtils, Grid, QUILocale, Mustache,
             ProductHandler, Product, CategoriesHandler, FieldsHandler,
             CategorySelect, templateProductData, templateField) {
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
            'openField',
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

            this.$CategorySelect = null;
            this.$FieldContainer = null;

            this.$Product = new Product({
                id: this.getAttribute('productId')
            });

            this.$data  = {};
            this.$Data  = null;
            this.$Media = null;
            this.$Files = null;

            this.$injected = false;

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
                    onClick: this.update
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
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            if (this.$injected) {
                return;
            }

            this.$injected = true;
            this.Loader.show();

            var i, len;

            var self    = this,
                Content = self.getContent();

            // load product data
            this.loadData().then(function () {

                // get product data
                return Promise.all([
                    self.$Product.getFields(),
                    self.$Product.getCategories()
                ]).then(function (result) {
                    return result;
                });

                // render
            }).then(function (data) {

                var fields     = data[0],
                    categories = data[1];

                Content.addClass('product-update');

                var dataTemplate = Mustache.render(templateProductData, {
                    productCategories   : QUILocale.get(lg, 'productCategories'),
                    productCategory     : QUILocale.get(lg, 'productCategory'),
                    productDefaultFields: QUILocale.get(lg, 'productDefaultFields'),
                    productMasterData   : QUILocale.get(lg, 'productMasterData')
                });

                Content.set({
                    html: '<div class="product-update-data sheet">' + dataTemplate + '</div>' +
                          '<div class="product-update-field sheet"></div>' +
                          '<div class="product-update-media sheet"></div>' +
                          '<div class="product-update-files sheet"></div>'
                });

                this.$Data  = Content.getElement('.product-update-data');
                this.$Media = Content.getElement('.product-update-media');
                this.$Files = Content.getElement('.product-update-files');

                this.$FieldContainer = Content.getElement('.product-update-field');

                // categories
                this.$CategorySelect = new CategorySelect({
                    name: 'categories'
                }).inject(
                    Content.getElement('.product-categories')
                );

                categories.each(function (categoryId) {
                    self.$CategorySelect.addCategory(categoryId);
                });


                // fields
                var field,
                    Data = Content.getElement('.product-data tbody');

                var StandardFields = Content.getElement(
                    '.product-standardfield tbody'
                );


                // Felderaufbau
                Promise.all([
                    Categories.getFields(categories),
                    Fields.getSystemFields(),
                    Fields.getStandardFields()
                ]).then(function (result) {

                    var fieldList        = [],
                        categoriesFields = result[0],
                        systemFields     = result[1],
                        standardFields   = result[2];

                    var complete = [].append(categoriesFields)
                        .append(systemFields)
                        .append(standardFields);

                    for (i = 0, len = complete.length; i < len; i++) {
                        fieldList[complete[i].id] = complete[i];
                    }

                    self.$createCategories(fieldList.clean());

                    var diffFields = standardFields.filter(function (value) {
                        for (var i = 0, len = systemFields.length; i < len; i++) {
                            if (value.id === systemFields[i].id) {
                                return false;
                            }
                        }
                        return true;
                    });


                    // systemfields
                    for (i = 0, len = systemFields.length; i < len; i++) {
                        field = systemFields[i];

                        if (field.type == 'TextareaMultiLang') {
                            continue;
                        }

                        new Element('tr', {
                            'class'       : 'field',
                            html          : Mustache.render(templateField, {
                                fieldTitle: QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                                fieldName : 'field-' + field.id,
                                control   : field.jsControl
                            }),
                            'data-fieldid': field.id
                        }).inject(Data);
                    }

                    // standard felder
                    for (i = 0, len = diffFields.length; i < len; i++) {
                        field = diffFields[i];

                        if (field.type == 'TextareaMultiLang') {
                            continue;
                        }

                        new Element('tr', {
                            'class'       : 'field',
                            html          : Mustache.render(templateField, {
                                fieldTitle: QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                                fieldName : 'field-' + field.id,
                                control   : field.jsControl
                            }),
                            'data-fieldid': field.id
                        }).inject(StandardFields);
                    }

                    // field values
                    var Form = Content.getElement('form');

                    fields.each(function (field) {
                        var Input = Form.elements['field-' + field.id];
                        
                        if (typeof Input !== 'undefined') {
                            Input.value = field.value;
                        }
                    });

                    QUI.parse().then(function () {
                        self.Loader.hide();
                        self.getCategory('data').click();
                    });
                });

            }.bind(this)).catch(function () {
                self.destroy();
            });
        },

        /**
         *
         * @param fields
         */
        $createCategories: function (fields) {
            var self = this;

            var fieldClick = function (Btn) {
                self.openField(Btn.getAttribute('field'));
            };

            for (var i = 0, len = fields.length; i < len; i++) {
                if (fields[i].type != 'TextareaMultiLang') {
                    continue;
                }

                this.addCategory({
                    name   : 'images',
                    text   : fields[i].title,
                    icon   : 'fa fa-picture-o',
                    fieldId: fields[i].id,
                    field  : fields[i],
                    events : {
                        onClick: fieldClick
                    }
                });
            }

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
         * Return the product data
         *
         * @returns {Promise}
         */
        loadData: function () {
            return this.$Product.refresh();
        },

        /**
         * Open the data
         *
         * @return {Promise}
         */
        openData: function () {
            return this.$hideCategories().then(function () {
                return this.$showCategory(this.$Data);
            }.bind(this));
        },

        /**
         * Open the image list
         *
         * @return {Promise}
         */
        openImages: function () {
            return this.$hideCategories().then(function () {
                return this.$showCategory(this.$Media);
            }.bind(this));
        },

        /**
         * Open the file list
         *
         * @return {Promise}
         */
        openFiles: function () {
            return this.$hideCategories().then(function () {
                return this.$showCategory(this.$Files);
            }.bind(this));
        },

        /**
         * Open a textarea field
         *
         * @param {Object} fielddData
         */
        openField: function (fielddData) {
            var self = this;

            self.$FieldContainer.set('html', '');

            return this.$hideCategories().then(function () {
                return self.$showCategory(self.$FieldContainer);
            }).then(function () {
                self.Loader.show();

                require(['Editors'], function (Editors) {
                    Editors.getEditor().then(function (Editor) {

                        self.$FieldContainer.setStyles({
                            height: '100%'
                        });

                        console.log(fielddData);

                        Editor.inject(self.$FieldContainer);
                        self.Loader.hide();
                    });

                });
            });
        },

        /**
         * Saves the product data
         * @returns {Promise}
         */
        update: function () {
            var self = this,
                Elm  = self.getElm();

            self.Loader.show();

            return new Promise(function (resolve, reject) {

                var Form = Elm.getElement('form');
                var data = QUIFormUtils.getFormData(Form);

                // fields
                var fields = Object.filter(data, function (value, key) {
                    return (key.indexOf('field-') >= 0);
                });

                var categories = data.categories.split(',');

                categories = categories.filter(function (item) {
                    return item !== '';
                });


                console.log(data);
                console.log(fields);

                Products.updateChild(
                    self.getAttribute('productId'),
                    categories,
                    data['product-category'],
                    fields
                ).then(function () {
                    self.Loader.hide();
                    resolve();
                }).catch(function () {
                    self.Loader.hide();
                    reject();
                });
            });
        },

        /**
         * Close all categories
         *
         * @returns {Promise}
         */
        $hideCategories: function () {
            var nodes = this.getContent().getElements('.sheet');

            return new Promise(function (resolve) {
                moofx(nodes).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: function () {
                        nodes.setStyles({
                            position: 'absolute',
                            display : 'none',
                            opacity : 0
                        });

                        resolve();
                    }
                });
            }.bind(this));
        },

        /**
         * Show a category
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $showCategory: function (Node) {
            return new Promise(function (resolve) {
                Node.setStyles({
                    position: null,
                    display : null,
                    opacity : 0
                });

                moofx(Node).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            });
        }
    });
});
