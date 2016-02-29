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
 * @require qui/utils/Form
 * @require Locale
 * @require controls/grid/Grid
 * @require controls/projects/project/media/FolderViewer
 * @require Mustache
 * @require package/quiqqer/products/bin/classes/Products
 * @require package/quiqqer/products/bin/classes/Product
 * @require package/quiqqer/products/bin/classes/Categories
 * @require package/quiqqer/products/bin/classes/Fields
 * @require package/quiqqer/products/bin/controls/categories/Select
 * @require text!package/quiqqer/products/bin/controls/products/ProductData.html
 * @require text!package/quiqqer/products/bin/controls/products/CreateField.html
 * @require css!package/quiqqer/products/bin/controls/products/Product.css
 */
define('package/quiqqer/products/bin/controls/products/Product', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'Locale',
    'controls/grid/Grid',
    'controls/projects/project/media/FolderViewer',
    'Mustache',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUIFormUtils, QUILocale,
             Grid, FolderViewer, Mustache,
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
            '$onInject',
            '$onFolderCreated'
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
            this.$currentField   = null;

            this.$FileViewer  = null;
            this.$ImageViewer = null;

            this.$Product = new Product({
                id: this.getAttribute('productId')
            });

            this.$data   = {};
            this.$Data   = null;
            this.$Media  = null;
            this.$Files  = null;
            this.$Editor = null;

            this.$injected = false;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * unserialize import
         *
         * @param {Object} data
         * @return {Object} this (package/quiqqer/products/bin/controls/products/Product)
         */
        unserialize: function (data) {
            this.setAttributes(data.attributes);

            this.$Product = new Product({
                id: this.getAttribute('productId')
            });

            return this;
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

                if (typeOf(fields) !== 'array') {
                    fields = [];
                }

                if (typeOf(categories) !== 'array') {
                    categories = [];
                }

                fields.each(function (Field) {
                    self.$data[Field.id] = Field;
                });

                // DOM
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

                this.$MainCategoryRow = Content.getElement('.product-mainCategory');
                this.$MainCategory    = Content.getElement('[name="product-category"]');

                Content.getElements('.sheet').setStyles({
                    display: 'none'
                });

                this.$FieldContainer = Content.getElement('.product-update-field');

                // viewer
                this.$FileViewer = new FolderViewer({
                    folderId: false,
                    filetype: ['file'],
                    events  : {
                        onFolderCreated: self.$onFolderCreated
                    }
                }).inject(this.$Files);

                this.$ImageViewer = new FolderViewer({
                    folderId: false,
                    filetype: ['image'],
                    events  : {
                        onFolderCreated: self.$onFolderCreated
                    }
                }).inject(this.$Media);


                // categories
                this.$CategorySelect = new CategorySelect({
                    name  : 'categories',
                    events: {
                        onChange: function () {

                        }
                    }
                }).inject(
                    Content.getElement('.product-categories')
                );

                if (categories.length) {
                    this.$MainCategoryRow.setStyle('display', null);
                    this.$MainCategory.set('html', '');
                }

                categories.each(function (categoryId) {
                    self.$CategorySelect.addCategory(categoryId);

                    new Element('option', {
                        value: categoryId,
                        html : QUILocale.get(lg, 'products.category.' + categoryId + '.title')
                    }).inject(self.$MainCategory);
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

                        // dont show media folder field
                        if (field.id === 10) {
                            new Element('input', {
                                type          : 'hidden',
                                'data-fieldid': field.id,
                                name          : 'field-' + field.id
                            }).inject(self.getElm().getElement('form'));
                            continue;
                        }

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
                            if (typeOf(field.value) !== 'string') {
                                field.value = JSON.encode(field.value);
                            }

                            Input.value = field.value;

                            if (field.id == 10) {
                                self.$FileViewer.setAttribute('folderUrl', field.value);
                                self.$ImageViewer.setAttribute('folderUrl', field.value);
                            }
                        }
                    });

                    QUI.parse().then(function () {
                        self.getCategory('data').click();
                        self.Loader.hide();
                    });
                });

            }.bind(this)).catch(function (err) {
                console.error(err);
                self.destroy();
            });
        },

        /**
         * Create panel categories
         *
         * @param {Object} fields
         */
        $createCategories: function (fields) {
            var self = this;

            var fieldClick = function (Btn) {
                self.openField(Btn.getAttribute('fieldId'));
            };

            for (var i = 0, len = fields.length; i < len; i++) {
                if (fields[i].type != 'TextareaMultiLang') {
                    continue;
                }

                this.addCategory({
                    name   : 'field-' + fields[i].id,
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
            return this.$Product.refresh().then(function () {
                return this.$Product.getFieldValue(4); // title

            }.bind(this)).then(function (field) {

                var title   = '',
                    current = QUILocale.getCurrent();

                if (current in field) {
                    title = field[current];
                }

                this.setAttributes({
                    title: QUILocale.get(lg, 'products.product.panel.title', {
                        product: title
                    })
                });

                this.refresh();

            }.bind(this));
        },

        /**
         * Open the data
         *
         * @return {Promise}
         */
        openData: function () {
            if (this.getCategory('data').isActive()) {
                return Promise.resolve();
            }

            return this.$hideCategories().then(function () {

                if (this.$Editor) {
                    this.$Editor.destroy();
                    this.$Editor = null;
                }

                return this.$showCategory(this.$Data);
            }.bind(this));
        },

        /**
         * Open the image list
         *
         * @return {Promise}
         */
        openImages: function () {
            if (this.getCategory('images').isActive()) {
                return Promise.resolve();
            }

            return this.$hideCategories().then(function () {

                if (this.$Editor) {
                    this.$Editor.destroy();
                    this.$Editor = null;
                }

                this.$ImageViewer.refresh();

                return this.$showCategory(this.$Media);
            }.bind(this));
        },

        /**
         * Open the file list
         *
         * @return {Promise}
         */
        openFiles: function () {
            if (this.getCategory('files').isActive()) {
                return Promise.resolve();
            }

            return this.$hideCategories().then(function () {

                if (this.$Editor) {
                    this.$Editor.destroy();
                    this.$Editor = null;
                }

                this.$FileViewer.refresh();

                return this.$showCategory(this.$Files);
            }.bind(this));
        },

        /**
         * Open a textarea field
         *
         * @param {Object} fieldId
         */
        openField: function (fieldId) {
            var self  = this,
                Field = this.$data[fieldId];

            self.$FieldContainer.set('html', '');

            return this.$hideCategories().then(function () {
                return self.$showCategory(self.$FieldContainer);
            }).then(function () {
                self.$currentField = fieldId;

                require(['Editors'], function (Editors) {
                    Editors.getEditor().then(function (Editor) {

                        self.$Editor = Editor;

                        self.$FieldContainer.setStyles({
                            height: '100%'
                        });

                        if (Field && "value" in Field) {
                            Editor.setContent(Field.value);
                        }

                        Editor.inject(self.$FieldContainer);
                    });
                });
            });
        },

        /**
         * Alias for update
         *
         * @returns {Promise}
         */
        save: function () {
            return this.update();
        },

        /**
         * Saves the product data
         * @returns {Promise}
         */
        update: function () {
            var self     = this,
                Elm      = self.getElm(),
                selfData = this.$data;

            this.Loader.show();
            this.$saveEditorContent();

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

                // content fields
                Object.each(selfData, function (entry) {
                    if (entry.type == 'TextareaMultiLang') {
                        fields['field-' + entry.id] = entry.value;
                    }
                });

                Products.updateChild(
                    self.getAttribute('productId'),
                    categories,
                    data['product-category'],
                    fields
                ).then(function () {
                    self.Loader.hide();
                    resolve();
                }).catch(function (err) {
                    self.Loader.hide();
                    reject(err);
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

            //  storage content fields
            this.$saveEditorContent();

            return new Promise(function (resolve) {
                moofx(nodes).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 200,
                    callback: function () {
                        nodes.setStyles({
                            display: 'none',
                            opacity: 0
                        });

                        resolve();
                    }
                });
            }.bind(this));
        },

        /**
         * storage the content fields
         */
        $saveEditorContent: function () {
            if (this.$Editor) {
                var currentField = this.$currentField;

                this.$data[currentField].value = this.$Editor.getContent();
            }
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
        },

        /**
         * event : on folder created, if the product hadnt a media folder
         *
         * @param {Object} Viewer
         * @param {Object} Folder
         */
        $onFolderCreated: function (Viewer, Folder) {
            var self = this;

            this.Loader.show();

            var Form  = this.getContent().getElement('form'),
                Input = Form.elements['field-10'];

            Input.value = Folder.getUrl();

            this.update().then(function () {
                self.$ImageViewer.refresh();
                self.$FileViewer.refresh();
                self.Loader.hide();
            });
        }
    });
});
