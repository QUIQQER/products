/**
 * Edit and manage one product
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
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/controls/fields/Window',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, QUIFormUtils, QUILocale,
             Grid, FolderViewer, Mustache,
             Products, Product, Categories, Fields, FieldWindow,
             CategorySelect, templateProductData, templateField) {
    "use strict";

    var lg = 'quiqqer/products';

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
            '$onFolderCreated',
            'openAddFieldDialog'
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

            this.addButton({
                name  : 'fieldAdd',
                icon  : 'fa fa-file-text-o',
                title : 'Feld hinzufügen',
                events: {
                    onClick: this.openAddFieldDialog
                },
                styles: {
                    'float': 'right'
                }
            });
        },

        /**
         * event : on inject
         *
         * @return {Promise}
         */
        $onInject: function () {
            if (this.$injected) {
                return Promise.resolve();
            }

            this.$injected = true;
            this.Loader.show();

            var i, len;

            var self    = this,
                Content = self.getContent();

            // load product data
            return this.loadData().then(function () {

                // get product data
                return Promise.all([
                    self.$Product.getFields(),
                    self.$Product.getCategories(),
                    self.$Product.getCategory(),
                    Products.getParentFolder()
                ]).then(function (result) {
                    return result;
                });

                // render
            }).then(function (data) {

                var fields     = data[0],
                    categories = data[1],
                    category   = data[2],
                    Folder     = data[3];

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
                    folderId     : false,
                    Parent       : Folder,
                    newFolderName: this.$Product.getId(),
                    filetype     : ['file'],
                    events       : {
                        onFolderCreated: self.$onFolderCreated
                    }
                }).inject(this.$Files);

                this.$ImageViewer = new FolderViewer({
                    folderId     : false,
                    Parent       : Folder,
                    newFolderName: this.$Product.getId(),
                    filetype     : ['image'],
                    events       : {
                        onFolderCreated: self.$onFolderCreated
                    }
                }).inject(this.$Media);


                // categories
                this.$CategorySelect = new CategorySelect({
                    name  : 'categories',
                    events: {
                        onDelete: function (Select, Item) {
                            var categoryId = Item.getAttribute('categoryId');
                            var Option     = self.$MainCategory.getElement(
                                '[value="' + categoryId + '"]'
                            );

                            if (Option) {
                                Option.destroy();
                            }
                        },
                        onChange: function () {
                            var ids = self.$CategorySelect.getValue();

                            if (ids === '') {
                                ids = [];
                            } else {
                                ids = ids.split(',');
                            }

                            ids.each(function (id) {
                                if (self.$MainCategory.getElement('[value="' + id + '"]')) {
                                    return;
                                }
                                new Element('option', {
                                    value: id,
                                    html : QUILocale.get(lg, 'products.category.' + id + '.title')
                                }).inject(self.$MainCategory);
                            });
                        }
                    }
                }).inject(
                    Content.getElement('.product-categories')
                );

                if (categories.length) {
                    this.$MainCategoryRow.setStyle('display', null);
                    this.$MainCategory.set('html', '');
                }

                categories.each(function (id) {
                    self.$CategorySelect.addCategory(id);

                    if (self.$MainCategory.getElement('[value="' + id + '"]')) {
                        return;
                    }

                    new Element('option', {
                        value: id,
                        html : QUILocale.get(lg, 'products.category.' + id + '.title')
                    }).inject(self.$MainCategory);
                });

                self.$MainCategory.value = category;


                // fields
                var field, title,
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
console.log(fieldList);
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
                        if (field.id == Fields.FIELD_FOLDER) {
                            new Element('input', {
                                type          : 'hidden',
                                'data-fieldid': field.id,
                                name          : 'field-' + field.id
                            }).inject(self.getElm().getElement('form'));
                            continue;
                        }

                        if (field.type == 'TextareaMultiLang' ||
                            field.type == 'Textarea') {
                            continue;
                        }


                        title = QUILocale.get(lg, 'products.field.' + field.id + '.title');

                        if (QUILocale.exists(lg, 'products.field.' + field.id + '.workingtitle')) {
                            title = QUILocale.get(lg, 'products.field.' + field.id + '.workingtitle');
                        }

                        new Element('tr', {
                            'class'       : 'field',
                            html          : Mustache.render(templateField, {
                                fieldTitle: title,
                                fieldName : 'field-' + field.id,
                                control   : field.jsControl
                            }),
                            'data-fieldid': field.id
                        }).inject(Data);
                    }

                    // standard felder
                    for (i = 0, len = diffFields.length; i < len; i++) {
                        field = diffFields[i];

                        if (field.type == 'TextareaMultiLang' ||
                            field.type == 'Textarea') {
                            continue;
                        }


                        title = QUILocale.get(lg, 'products.field.' + field.id + '.title');

                        if (QUILocale.exists(lg, 'products.field.' + field.id + '.workingtitle')) {
                            title = QUILocale.get(lg, 'products.field.' + field.id + '.workingtitle');
                        }

                        new Element('tr', {
                            'class'       : 'field',
                            html          : Mustache.render(templateField, {
                                fieldTitle: title,
                                fieldName : 'field-' + field.id,
                                control   : field.jsControl
                            }),
                            'data-fieldid': field.id
                        }).inject(StandardFields);
                    }

                    // field values
                    var Form          = Content.getElement('form'),
                        productFolder = false;

                    fields.each(function (field) {
                        var Input = Form.elements['field-' + field.id];

                        if (typeof Input !== 'undefined') {
                            if (typeOf(field.value) !== 'string') {
                                field.value = JSON.encode(field.value);
                            }

                            Input.value = field.value;

                            if (field.id == Fields.FIELD_FOLDER) {
                                self.$FileViewer.setAttribute('folderUrl', field.value);
                                self.$ImageViewer.setAttribute('folderUrl', field.value);

                                productFolder = field.value;
                            }
                        }
                    });

                    QUI.parse().then(function () {
                        self.getCategory('data').click();

                        // image fields
                        var images = self.getElm().getElements(
                            '[data-qui="package/quiqqer/products/bin/controls/fields/types/Image"]'
                        );

                        images.each(function (Input) {
                            var quiId   = Input.get('data-quiid'),
                                Control = QUI.Controls.getById(quiId);

                            if (Control) {
                                Control.setAttribute('productFolder', productFolder);
                            }
                        });


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

            this.getCategoryBar().clear();

            this.addCategory({
                name  : 'data',
                text  : QUILocale.get('quiqqer/system', 'data'),
                icon  : 'fa fa-shopping-bag',
                events: {
                    onClick: this.openData
                }
            });

            for (var i = 0, len = fields.length; i < len; i++) {
                if (fields[i].type != 'TextareaMultiLang' &&
                    fields[i].type != 'Textarea') {
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
                    if (entry.type == 'TextareaMultiLang' || entry.type == 'Textarea') {
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
         * Add a field to the product
         *
         * @param {Number} fieldId
         * @returns {*|Promise|Object}
         */
        addField: function (fieldId) {
            return this.$Product.addField(fieldId).then(function () {
                this.$injected = false;
                return this.$onInject();
            }.bind(this));
        },

        /**
         * open add field dialog
         */
        openAddFieldDialog: function () {
            new FieldWindow({
                events: {
                    onSubmit: function (Win, value) {
                        Win.Loader.show();

                        this.addField(value).then(function () {
                            Win.close();
                        });
                    }.bind(this)
                }
            }).open();
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

                if (!(currentField in this.$data)) {
                    this.$data[currentField] = {};
                }

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
                return self.$Product.refresh();
            }).then(function () {
                return self.$Product.getFields();
            }).then(function (productField) {

                var folder = productField.filter(function (field) {
                    return field.id == Fields.FIELD_FOLDER;
                });

                if (!folder.length) {
                    return self.Loader.hide();
                }

                self.$FileViewer.setAttribute('folderUrl', folder[0].value);
                self.$ImageViewer.setAttribute('folderUrl', folder[0].value);

                self.$ImageViewer.refresh();
                self.$FileViewer.refresh();

                // image fields
                var images = self.getElm().getElements(
                    '[data-qui="package/quiqqer/products/bin/controls/fields/types/Image"]'
                );

                images.each(function (Input) {
                    var quiId   = Input.get('data-quiid'),
                        Control = QUI.Controls.getById(quiId);

                    if (Control) {
                        Control.setAttribute('productFolder', folder[0].value);
                    }
                });

                self.Loader.hide();
            });
        }
    });
});
