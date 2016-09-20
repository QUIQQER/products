/**
 * Edit and manage one product - Product Panel
 *
 * @module package/quiqqer/products/bin/controls/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Switch
 * @require qui/controls/buttons/ButtonSwitch
 * @require qui/controls/windows/Confirm
 * @require qui/utils/Form
 * @require Locale
 * @require controls/grid/Grid
 * @require controls/projects/project/media/FolderViewer
 * @require Mustache
 * @require Packages
 * @require package/quiqqer/products/bin/Products
 * @require package/quiqqer/products/bin/classes/Product
 * @require package/quiqqer/products/bin/Categories
 * @require package/quiqqer/products/bin/Fields
 * @require package/quiqqer/products/bin/utils/Fields
 * @require package/quiqqer/products/bin/controls/fields/search/Window
 * @require package/quiqqer/products/bin/controls/categories/Select
 * @require package/quiqqer/products/bin/controls/fields/FieldTypeSelect
 * @require text!package/quiqqer/products/bin/controls/products/ProductData.html
 * @require text!package/quiqqer/products/bin/controls/products/CreateField.html
 * @require css!package/quiqqer/products/bin/controls/products/Product.css
 */
define('package/quiqqer/products/bin/controls/products/Product', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'qui/controls/buttons/ButtonSwitch',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'Locale',
    'Users',
    'controls/grid/Grid',
    'controls/projects/project/media/FolderViewer',
    'Mustache',
    'Packages',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/utils/Fields',
    'package/quiqqer/products/bin/controls/fields/search/Window',
    'package/quiqqer/products/bin/controls/categories/Select',
    'package/quiqqer/products/bin/controls/fields/FieldTypeSelect',

    'text!package/quiqqer/products/bin/controls/products/ProductInformation.html',
    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/ProductPrices.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, QUIPanel, QUIButton, QUISwitch, QUIButtonSwitch, QUIConfirm, QUIFormUtils, QUILocale,
             Users, Grid, FolderViewer, Mustache, Packages,
             Products, Product, Categories, Fields, FieldUtils, FieldWindow,
             CategorySelect, FieldTypeSelect,
             informationTemplate, templateProductData, templateProductPrices, templateField) {
    "use strict";

    var lg   = 'quiqqer/products',
        User = Users.getUserBySession();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Product',

        Binds: [
            'refresh',
            'update',
            'copy',
            '$onActivationStatusChange',
            'openInformation',
            'openData',
            'openPrices',
            'openImages',
            'openFiles',
            'openField',
            'openPermissions',
            '$onCreate',
            '$onInject',
            'openAddFieldDialog',
            'openAttributeList',
            'openFieldAdministration',
            '$onCreateMediaFolderClick'
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

            this.$CategorySelect      = null;
            this.$FieldContainer      = null;
            this.$currentField        = null;
            this.$FileViewer          = null;
            this.$ImageViewer         = null;
            this.$Grid                = null;
            this.$FieldAdministration = null;
            this.$AttributeList       = null;
            this.$Data                = null;
            this.$Media               = null;
            this.$Files               = null;
            this.$Control             = null;
            this.$Information         = null;
            this.$CurrentCategory     = null;

            this.$Product = new Product({
                id: this.getAttribute('productId')
            });

            this.$data     = {};
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
                    onClick: function () {
                        this.update().catch(function (err) {
                            QUI.getMessageHandler().then(function (MH) {
                                MH.addError(QUILocale.get(lg, 'message.product.error.saving', {
                                    error: err
                                }));
                            });
                        });
                    }.bind(this)
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton(
                new QUIButtonSwitch({
                    name    : 'status',
                    text    : '---',
                    disabled: true,
                    events  : {
                        onChange: this.$onActivationStatusChange
                    }
                })
            );

            this.addButton({
                name  : 'copy',
                icon  : 'fa fa-copy',
                title : QUILocale.get('quiqqer/system', 'copy'),
                events: {
                    onClick: this.copy
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
            return Packages.getConfig('quiqqer/products').then(function (config) {
                if (!("products" in config)) {
                    return;
                }

                if (!("usePermissions" in config.products)) {
                    return;
                }

                if (!parseInt(config.products.usePermissions) ||
                    config.products.usePermissions === '') {
                    return;
                }

                new QUIButton({
                    image : 'fa fa-shield',
                    alt   : QUILocale.get(lg, 'products.product.panel.btn.permissions'),
                    title : QUILocale.get(lg, 'products.product.panel.btn.permissions'),
                    styles: {
                        'border-left-width' : 1,
                        'border-right-width': 1,
                        'float'             : 'right',
                        width               : 40
                    },
                    events: {
                        onClick: self.openPermissions
                    }
                }).inject(self.getHeader());


            }).then(function () {
                return self.loadData();

            }).then(function () {

                // get product data
                return Promise.all([
                    self.$Product.getFields(),
                    self.$Product.getCategories(),
                    self.$Product.getCategory(),
                    Products.getParentFolder(),
                    self.$Product.isActive()
                ]).then(function (result) {
                    return result;
                });

                // render
            }).then(function (data) {

                var fields     = data[0],
                    categories = data[1],
                    category   = data[2],
                    Folder     = data[3],
                    isActive   = data[4];

                if (typeOf(fields) !== 'array') {
                    fields = [];
                }

                if (isActive) {
                    self.getButtons('status').setSilentOn();
                } else {
                    self.getButtons('status').setSilentOff();
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
                    html: '<div class="product-update-information sheet"></div>' +
                          '<div class="product-update-data sheet">' + dataTemplate + '</div>' +
                          '<div class="product-update-field sheet"></div>' +
                          '<div class="product-update-media sheet"></div>' +
                          '<div class="product-update-files sheet"></div>' +
                          '<div class="product-update-prices sheet"></div>' +
                          '<div class="product-update-fieldadministration sheet"></div>' +
                          '<div class="product-update-attributelist sheet"></div>'
                });

                this.$Information     = Content.getElement('.product-update-information');
                this.$Data            = Content.getElement('.product-update-data');
                this.$Media           = Content.getElement('.product-update-media');
                this.$Files           = Content.getElement('.product-update-files');
                this.$Prices          = Content.getElement('.product-update-prices');
                this.$MainCategoryRow = Content.getElement('.product-mainCategory');
                this.$MainCategory    = Content.getElement('[name="product-category"]');

                this.$FieldAdministration = Content.getElement('.product-update-fieldadministration');
                this.$AttributeList       = Content.getElement('.product-update-attributelist');

                Content.getElements('.sheet').setStyles({
                    display: 'none'
                });

                this.$FieldContainer = Content.getElement('.product-update-field');

                // data
                new QUIButton({
                    text  : QUILocale.get(lg, 'product.fields.administration'),
                    styles: {
                        display: 'block',
                        'float': 'none',
                        margin : '0 auto 20px',
                        width  : 200
                    },
                    events: {
                        onClick: this.openFieldAdministration
                    }
                }).inject(this.$Data);


                // viewer
                this.$FileViewer = new FolderViewer({
                    folderId     : false,
                    Parent       : Folder,
                    newFolderName: this.$Product.getId(),
                    filetype     : ['file'],
                    autoactivate : true
                }).inject(this.$Files);

                this.$ImageViewer = new FolderViewer({
                    folderId     : false,
                    Parent       : Folder,
                    newFolderName: this.$Product.getId(),
                    filetype     : ['image'],
                    autoactivate : true
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

                            if (ids.length) {
                                self.$MainCategoryRow.setStyle('display', null);
                            } else {
                                self.$MainCategoryRow.setStyle('display', 'none');
                            }
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
                    Fields.getStandardFields(),
                    Fields.getFieldTypes()
                ]).then(function (result) {

                    var fieldList        = [],
                        fieldTypes       = {},
                        categoriesFields = result[0],
                        systemFields     = result[1],
                        standardFields   = result[2];

                    var types = result[3];

                    for (i = 0, len = types.length; i < len; i++) {
                        fieldTypes[types[i].name] = types[i];
                    }

                    var complete = [].append(categoriesFields)
                        .append(systemFields)
                        .append(standardFields);

                    // cleanup complete list
                    var completeIds = {};

                    complete = complete.filter(function (entry) {
                        if (entry.id in completeIds) {
                            return false;
                        }
                        completeIds[entry.id] = true;
                        return true;
                    });

                    for (i = 0, len = complete.length; i < len; i++) {
                        fieldList[complete[i].id] = complete[i];
                    }

                    var diffFields = complete.filter(function (value) {
                        for (var i = 0, len = systemFields.length; i < len; i++) {
                            if (value.id === systemFields[i].id) {
                                return false;
                            }
                        }
                        return true;
                    });

                    fieldList    = FieldUtils.sortFields(fieldList);
                    diffFields   = FieldUtils.sortFields(diffFields);
                    systemFields = FieldUtils.sortFields(systemFields);

                    self.$createCategories(fieldList, fieldTypes);

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
                            field.type == 'Textarea' ||
                            field.type == 'Folder' ||
                            field.type == 'Products'
                        ) {
                            continue;
                        }

                        if (typeof fieldTypes[field.type] !== 'undefined' &&
                            fieldTypes[field.type].category) {
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
                            field.type == 'Textarea' ||
                            field.type == 'Folder' ||
                            field.type == 'Products'
                        ) {
                            continue;
                        }

                        // wenn es ein feld ist, welcher der kunde ausfüllen muss
                        // nicht anzeigen
                        if (field.custom) {
                            continue;
                        }

                        if (typeof fieldTypes[field.type] !== 'undefined' &&
                            fieldTypes[field.type].category) {
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
                            if (typeOf(field.value) !== 'string' && field.value !== null) {
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

                    // no media folder exists
                    // display create message
                    if (!productFolder) {
                        self.$FileViewer.hide();
                        self.$ImageViewer.hide();

                        var Container = new Element('div', {
                            'class': 'folder-missing-container',
                            html   : QUILocale.get(lg, 'products.product.panel.folder.missing')
                        }).inject(self.$Media);

                        new QUIButton({
                            text     : QUILocale.get(lg, 'products.product.panel.folder.missing.button'),
                            textimage: 'fa fa-plus',
                            styles   : {
                                clear : 'both',
                                margin: '20px 0 0 0'
                            },
                            events   : {
                                onClick: self.$onCreateMediaFolderClick
                            }
                        }).inject(Container);

                        var Container2 = new Element('div', {
                            'class': 'folder-missing-container',
                            html   : QUILocale.get(lg, 'products.product.panel.folder.missing')
                        }).inject(self.$Files);

                        new QUIButton({
                            text     : QUILocale.get(lg, 'products.product.panel.folder.missing.button'),
                            textimage: 'fa fa-plus',
                            styles   : {
                                clear : 'both',
                                margin: '20px 0 0 0'
                            },
                            events   : {
                                onClick: self.$onCreateMediaFolderClick
                            }
                        }).inject(Container2);
                    }

                    // parse qui controls
                    QUI.parse().then(function () {
                        // field change events
                        var fieldChange = function (Field) {
                            if (!("getFieldId" in Field)) {
                                return;
                            }

                            var fieldId = Field.getFieldId();

                            // set product field value
                            if (fieldId in self.$data) {
                                self.$data[fieldId].value = Field.getValue();
                            }
                        };

                        QUI.Controls.getControlsInElement(self.$Data).each(function (Field) {
                            if (!Field.getType().match("controls/fields/types/")) {
                                return;
                            }

                            Field.addEvent('change', fieldChange);
                        });

                        var wantedCategory = User.getAttribute(
                            'quiqqer.erp.productPanel.open.category'
                        );

                        if (wantedCategory && self.getCategory(wantedCategory)) {
                            self.getCategory(wantedCategory).click();
                        } else {
                            self.getCategory('information').click();
                        }


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
         * Refresh the panel
         */
        refresh: function () {
            this.parent();

            this.$Product.isActive().then(function (status) {
                var Button = this.getButtons('status');

                // product is active
                if (status) {
                    Button.setSilentOn();
                    Button.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isActivate'));
                    Button.enable();
                    return;
                }

                // product is deactivate
                Button.setSilentOff();
                Button.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isDeactivate'));
                Button.enable();

            }.bind(this));
        },

        /**
         * Create panel categories
         *
         * @param {Object} fields
         * @param {Object} fieldtypes - list of the fieldtypes data
         */
        $createCategories: function (fields, fieldtypes) {
            var self = this;

            var fieldClick = function (Btn) {
                self.openField(Btn.getAttribute('fieldId'));
            };

            var imageFolderClick = function (Btn) {
                self.openMediaFolderField(Btn.getAttribute('fieldId'));
            };

            var showCategory = function (type) {
                if (type == 'TextareaMultiLang' ||
                    type == 'Textarea' ||
                    type == 'Products') {
                    return true;
                }

                if (typeof fieldtypes[type] === 'undefined') {
                    return true;
                }

                return fieldtypes[type].category ? true : false;
            };

            this.getCategoryBar().clear();


            this.addCategory({
                name  : 'information',
                text  : QUILocale.get('quiqqer/system', 'information'),
                icon  : 'fa fa-info',
                events: {
                    onClick: this.openInformation
                }
            });

            this.addCategory({
                name  : 'data',
                text  : QUILocale.get('quiqqer/system', 'data'),
                icon  : 'fa fa-shopping-bag',
                events: {
                    onClick: this.openData
                }
            });

            this.addCategory({
                name  : 'prices',
                text  : QUILocale.get(lg, 'products.product.panel.category.prices'),
                icon  : 'fa fa-money',
                events: {
                    onClick: this.openPrices
                }
            });

            var i, len, icon, type;

            for (i = 0, len = fields.length; i < len; i++) {
                type = fields[i].type;

                if (showCategory(type) === false) {
                    continue;
                }

                icon = 'fa fa-file-text-o';

                if (type == 'Products') {
                    icon = 'fa fa-shopping-bag';
                }

                this.addCategory({
                    name   : 'field-' + fields[i].id,
                    text   : fields[i].workingtitle,
                    icon   : icon,
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

            for (i = 0, len = fields.length; i < len; i++) {
                if (fields[i].type != 'Folder') {
                    continue;
                }

                if (fields[i].id == Fields.FIELD_FOLDER) {
                    continue;
                }

                this.addCategory({
                    name   : 'images',
                    text   : fields[i].workingtitle,
                    icon   : 'fa fa-picture-o',
                    fieldId: fields[i].id,
                    field  : fields[i],
                    events : {
                        onClick: imageFolderClick
                    }
                });
            }

            this.addCategory({
                name  : 'attributelist',
                text  : QUILocale.get(lg, 'products.product.panel.category.attributelist'),
                icon  : 'fa fa-file-text-o',
                events: {
                    onClick: this.openAttributeList
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
                return this.$Product.getTitle();

            }.bind(this)).then(function (title) {

                this.setAttributes({
                    title: QUILocale.get(lg, 'products.product.panel.title', {
                        product: title
                    })
                });

                this.refresh();

            }.bind(this));
        },

        /**
         * Open the information category tab
         *
         * @return {Promise}
         */
        openInformation: function () {
            if (this.getCategory('information').isActive()) {
                return Promise.resolve();
            }

            var self = this;

            return self.$hideCategories().then(function () {
                return self.$Product.getCategories();

            }).then(function (data) {
                return Categories.getCategories(data);

            }).then(function (categories) {

                // eigenes bild hohlen, wenn leer, oder nicht existiert, egal
                return new Promise(function (resolve) {
                    self.$Product.getImage().then(resolve).catch(function () {
                        resolve(false);
                    });
                }).then(function (image) {
                    return Promise.all([
                        self.$Product.getTitle(),
                        self.$Product.getDescription(),
                        image,
                        categories
                    ]);
                });

            }).then(function (data) {
                var categories = data[3].map(function (Category) {
                    return {title: Category.title};
                });

                var image = data[2] ? URL_DIR + data[2] : false;

                self.$Information.set({
                    html: Mustache.render(informationTemplate, {
                        title            : data[0],
                        description      : data[1],
                        image            : image,
                        categories       : categories,
                        fields           : [],
                        productCategories: QUILocale.get(lg, 'productCategories'),
                        productImage     : QUILocale.get(lg, 'productImage')
                    })
                });

                return self.$showCategory(self.$Information);
            });
        },

        /**
         * Open the data
         *
         * @return {Promise}
         */
        openData: function () {
            if (this.getCategory('data').isActive() && !this.$FieldAdministration.getStyle('opacity')) {
                return Promise.resolve();
            }

            var self = this;

            return self.$hideCategories().then(function () {
                // set values
                QUI.Controls.getControlsInElement(self.$Data)
                    .each(function (Field) {
                        if (!("getFieldId" in Field)) {
                            return;
                        }

                        if (!("setValue" in Field)) {
                            return;
                        }

                        var fieldId = Field.getFieldId();

                        if (fieldId in self.$data) {
                            Field.setValue(self.$data[fieldId].value);
                        }
                    });

                return self.$showCategory(self.$Data);
            });
        },

        /**
         * Opens the price fields
         */
        openPrices: function () {
            if (this.getCategory('prices').isActive()) {
                return Promise.resolve();
            }

            var self = this;

            return self.$hideCategories().then(function () {
                return Promise.all([
                    self.$Product.getFieldsByType(Fields.TYPE_PRICE),
                    self.$Product.getFieldsByType(Fields.TYPE_PRICE_BY_QUANTITY)
                ]);

            }).then(function (fields) {
                fields = fields.flatten();

                // sort by priority and mein price as first
                fields.sort(function (FieldA, FieldB) {
                    var priorityA = parseInt(FieldA.priority),
                        priorityB = parseInt(FieldB.priority);

                    if (FieldA.id == Fields.FIELD_PRICE) {
                        return -1;
                    }

                    if (FieldB.id == Fields.FIELD_PRICE) {
                        return 1;
                    }

                    if (priorityA === 0) {
                        return 1;
                    }

                    if (priorityB === 0) {
                        return -1;
                    }

                    if (priorityA < priorityB) {
                        return -1;
                    }

                    if (priorityA > priorityB) {
                        return 1;
                    }

                    return 0;
                });

                self.$Prices.set({
                    html: Mustache.render(templateProductPrices, {
                        title : QUILocale.get(lg, 'products.product.panel.category.prices'),
                        fields: fields
                    })
                });

                return QUI.parse(self.$Prices);

            }).then(function () {

                // change events
                var prices   = QUI.Controls.getControlsInElement(self.$Prices),

                    onChange = function (Price) {
                        var fieldId = Price.getFieldId();

                        // set product field value
                        if (fieldId in self.$data) {
                            self.$data[fieldId].value = Price.getValue();
                        }
                    };

                for (var i = 0, len = prices.length; i < len; i++) {
                    prices[i].addEvent('change', onChange);
                }

                return self.$showCategory(self.$Prices);
            });
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
                this.$FileViewer.refresh();

                return this.$showCategory(this.$Files);
            }.bind(this));
        },

        /**
         * Open a textarea field
         *
         * @param {Number} fieldId
         */
        openField: function (fieldId) {
            var self  = this,
                Field = this.$data[fieldId];

            self.$FieldContainer.set('html', '');

            return this.$hideCategories().then(function () {
                return self.$showCategory(self.$FieldContainer);

            }).then(function () {
                this.$currentField = fieldId;

                require([Field.jsControl], function (Control) {
                    this.$Control = new Control();

                    this.$FieldContainer.setStyles({
                        height: '100%'
                    });

                    if (Field && "value" in Field) {
                        this.$Control.setAttribute('value', Field.value);
                    }

                    this.$Control.inject(this.$FieldContainer);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Opens a media folder field
         *
         * @param {Number} fieldId
         */
        openMediaFolderField: function (fieldId) {
            var self  = this,
                Field = this.$data[fieldId];

            self.$FieldContainer.set('html', '');

            return this.$hideCategories().then(function () {

                // no images or files exists
                if (Field.value === '') {
                    var Container = new Element('div', {
                        'class': 'folder-missing-container',
                        html   : QUILocale.get(lg, 'products.product.panel.folder.missing.for.field')
                    }).inject(self.$FieldContainer);

                    new QUIButton({
                        text     : QUILocale.get(lg, 'products.product.panel.folder.missing.button'),
                        textimage: 'fa fa-plus',
                        styles   : {
                            clear : 'both',
                            margin: '20px 0 0 0'
                        },
                        events   : {
                            onClick: function (Btn) {
                                Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                                self.$createMediaFolder(Field.id).then(function () {
                                });
                            }
                        }
                    }).inject(Container);

                    new QUIButton({
                        icon  : 'fa fa-picture',
                        styles: {
                            clear : 'both',
                            margin: '20px 0 0 0'
                        },
                        alt   : 'Bestehenden Media-Ordner auswählen',
                        events: {
                            onClick: function (Btn) {

                            }
                        }
                    }).inject(Container);

                    return self.$showCategory(self.$FieldContainer);
                }

                new FolderViewer({
                    folderUrl   : Field.value,
                    filetype    : ['image', 'file'],
                    autoactivate: true
                }).inject(self.$FieldContainer);

                return self.$showCategory(self.$FieldContainer);
            });
        },

        /**
         * opens the field administration
         */
        openFieldAdministration: function () {

            return this.$hideCategories().then(function () {

                var refresh = function () {
                    var FieldTypes = this.$Grid.getButtons().filter(function (Btn) {
                        return Btn.getAttribute('name') == 'select';
                    })[0];

                    this.$Product.getFields().then(function (fields) {
                        var i, len, entry;
                        var data = [];

                        var fieldType = FieldTypes.getValue();

                        for (i = 0, len = fields.length; i < len; i++) {
                            entry = fields[i];

                            if (fieldType !== '' && fieldType !== entry.type) {
                                continue;
                            }

                            data.push({
                                visible        : new QUISwitch({
                                    fieldId: entry.id,
                                    status : entry.isPublic,
                                    events : {
                                        onChange: switchStatusChange
                                    }
                                }),
                                id             : entry.id,
                                title          : entry.title || '',
                                workingtitle   : entry.workingtitle || '',
                                fieldtype      : entry.type,
                                priority       : entry.priority,
                                suffix         : entry.suffix,
                                prefix         : entry.prefix,
                                source         : entry.source.join(', '),
                                ownField       : entry.ownField,
                                ownFieldDisplay: new Element('div', {
                                    'class': 'fa fa-user',
                                    styles : {
                                        color: entry.ownField ? '' : '#dddddd'
                                    }
                                }),

                            });
                        }

                        this.$Grid.setData({
                            data: data
                        });

                    }.bind(this));
                }.bind(this);


                var GridContainer = new Element('div', {
                    styles: {
                        'float': 'left',
                        height : '100%',
                        width  : '100%'
                    }
                }).inject(this.$FieldAdministration);

                this.$Grid = new Grid(GridContainer, {
                    buttons    : [
                        new FieldTypeSelect({
                            name  : 'select',
                            events: {
                                filterChange: refresh
                            }
                        }), {
                            type: 'seperator'
                        }, {
                            text     : QUILocale.get(lg, 'product.fields.add.field'),
                            textimage: 'fa fa-plus',
                            events   : {
                                onClick: this.openAddFieldDialog
                            }
                        }, {
                            name     : 'remove',
                            text     : QUILocale.get(lg, 'product.fields.remove.field'),
                            disabled : true,
                            textimage: 'fa fa-trash',
                            events   : {
                                onClick: function () {
                                    this.openDeleteFieldDialog(
                                        this.$Grid.getSelectedData()[0].id
                                    );
                                }.bind(this)
                            }
                        }],
                    columnModel: [{
                        header   : QUILocale.get(lg, 'product.fields.grid.visible'),
                        dataIndex: 'visible',
                        dataType : 'QUI',
                        width    : 60
                    }, {
                        header   : '&nbsp;',
                        dataIndex: 'ownFieldDisplay',
                        dataType : 'node',
                        width    : 30
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'id'),
                        dataIndex: 'id',
                        dataType : 'number',
                        width    : 60
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'title'),
                        dataIndex: 'title',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'workingTitle'),
                        dataIndex: 'workingtitle',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'fieldtype'),
                        dataIndex: 'fieldtype',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'priority'),
                        dataIndex: 'priority',
                        dataType : 'number',
                        width    : 100
                    }, {
                        header   : QUILocale.get(lg, 'prefix'),
                        dataIndex: 'prefix',
                        dataType : 'text',
                        width    : 100
                    }, {
                        header   : QUILocale.get(lg, 'suffix'),
                        dataIndex: 'suffix',
                        dataType : 'text',
                        width    : 100
                    }, {
                        header   : QUILocale.get(lg, 'source'),
                        dataIndex: 'source',
                        dataType : 'text',
                        width    : 400
                    }, {
                        dataIndex: 'ownField',
                        hidden   : true
                    }]
                });

                var switchStatusChange = function (Switch) {
                    var fieldId = Switch.getAttribute('fieldId'),
                        status  = Switch.getStatus();

                    Switch.disable();

                    this.$Product.setPublicStatusFromField(fieldId, status).then(function () {
                        Switch.enable();
                    });

                }.bind(this);


                this.$Grid.addEvents({
                    onRefresh: refresh,
                    onClick  : function () {
                        var selected = this.$Grid.getSelectedData()[0],
                            Remove   = this.$Grid.getButtons().filter(function (Btn) {
                                return Btn.getAttribute('name') == 'remove';
                            })[0];

                        if (selected.ownField) {
                            Remove.enable();
                        } else {
                            Remove.disable();
                        }
                    }.bind(this)
                });

                var size = this.$FieldAdministration.measure(function () {
                    return this.getSize();
                });

                return this.$Grid.setHeight(size.y - 40).then(function () {
                    this.$Grid.refresh();
                    return this.$showCategory(this.$FieldAdministration);

                }.bind(this)).then(function () {
                    this.$Grid.resize();

                }.bind(this));

            }.bind(this));
        },

        /**
         * opens the attribute list display
         *
         * @return {Promise}
         */
        openAttributeList: function () {
            return this.$hideCategories().then(function () {

                var GridContainer = new Element('div', {
                    styles: {
                        'float': 'left',
                        height : '100%',
                        width  : '100%'
                    }
                }).inject(this.$AttributeList);

                this.$Grid = new Grid(GridContainer, {
                    sortOn     : 'calcPriority',
                    buttons    : [{
                        text     : QUILocale.get(lg, 'product.fields.grid.button.addSelectList'),
                        textimage: 'fa fa-plus',
                        events   : {
                            onClick: function () {
                                this.openAddFieldDialog('ProductAttributeList');
                            }.bind(this)
                        }
                    }],
                    columnModel: [{
                        header   : QUILocale.get(lg, 'product.fields.grid.visible'),
                        dataIndex: 'visible',
                        dataType : 'QUI',
                        width    : 60
                    }, {
                        header   : QUILocale.get(lg, 'product.fields.grid.calcPriority'),
                        title    : QUILocale.get(lg, 'product.fields.grid.calcPriority.desc'),
                        dataIndex: 'calcPriority',
                        dataType : 'string',
                        width    : 60
                    }, {
                        header   : QUILocale.get(lg, 'product.fields.grid.calcBasis'),
                        title    : QUILocale.get(lg, 'product.fields.grid.calcBasis.desc'),
                        dataIndex: 'calcBasis',
                        dataType : 'string',
                        width    : 100
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'id'),
                        dataIndex: 'id',
                        dataType : 'number',
                        width    : 60
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'title'),
                        dataIndex: 'title',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'workingTitle'),
                        dataIndex: 'workingtitle',
                        dataType : 'text',
                        width    : 200
                    }]
                });

                var switchStatusChange = function (Switch) {
                    var fieldId = Switch.getAttribute('fieldId'),
                        status  = Switch.getStatus();

                    Switch.disable();

                    this.$Product.setPublicStatusFromField(fieldId, status).then(function () {
                        Switch.enable();
                    });
                }.bind(this);


                var refresh = function () {
                    this.$Product.getFields().then(function (fields) {
                        var i, len, entry,
                            options, calculation_basis, priority;
                        var data = [];

                        for (i = 0, len = fields.length; i < len; i++) {
                            entry = fields[i];

                            if (entry.type != 'ProductAttributeList') {
                                continue;
                            }

                            calculation_basis = '';
                            priority          = '';

                            options = entry.options;

                            if ("calculation_basis" in options) {
                                switch (options.calculation_basis) {
                                    case 'netto':
                                        calculation_basis = QUILocale.get(
                                            lg,
                                            'fieldtype.ProductAttributeList.calcBasis.netto'
                                        );
                                        break;

                                    case 'calculated_price':
                                        calculation_basis = QUILocale.get(
                                            lg,
                                            'fieldtype.ProductAttributeList.calcBasis.calculated_price'
                                        );
                                        break;
                                }
                            }

                            if ("priority" in options) {
                                priority = options.priority;
                            }

                            data.push({
                                visible     : new QUISwitch({
                                    fieldId: entry.id,
                                    events : {
                                        onChange: switchStatusChange
                                    }
                                }),
                                calcPriority: priority,
                                calcBasis   : calculation_basis,
                                id          : entry.id,
                                title       : entry.title || '',
                                workingtitle: entry.workingtitle || ''
                            });
                        }

                        this.$Grid.setData({
                            data: data
                        });

                    }.bind(this));
                }.bind(this);

                this.$Grid.addEvents({
                    onRefresh: refresh
                });

                var size = this.$AttributeList.measure(function () {
                    return this.getSize();
                });

                return this.$Grid.setHeight(size.y - 40).then(function () {
                    this.$Grid.refresh();
                    return this.$showCategory(this.$AttributeList);
                }.bind(this)).then(function () {
                    this.$Grid.resize();
                }.bind(this));

            }.bind(this));
        },

        /**
         * Shows permissions for the product
         */
        openPermissions: function () {
            this.Loader.show();

            require([
                'package/quiqqer/products/bin/controls/products/permissions/Permissions'
            ], function (ProductPermissions) {
                var self  = this;
                var Sheet = this.createSheet({
                    icon : 'fa fa-shopping-bag',
                    title: this.getAttribute('title')
                });

                Sheet.addEvents({
                    onOpen: function (Sheet) {
                        var Permissions = new ProductPermissions({
                            productId: self.getAttribute('productId')
                        }).inject(Sheet.getContent());

                        Sheet.addButton({
                            text     : QUILocale.get('quiqqer/system', 'save'),
                            textimage: 'fa fa-save',
                            events   : {
                                onClick: function () {
                                    self.Loader.show();
                                    Permissions.save().then(function () {
                                        Sheet.hide();
                                        self.Loader.hide();
                                    });
                                }
                            }
                        });
                    }
                });

                Sheet.show();
                this.Loader.hide();

            }.bind(this));
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

                var fields = {};
                var Form   = Elm.getElement('form');
                var data   = QUIFormUtils.getFormData(Form);

                Object.each(selfData, function (entry) {
                    fields['field-' + entry.id] = entry.value;
                });

                // fields
                var formfields = Object.filter(data, function (value, key) {
                    return (key.indexOf('field-') >= 0);
                });

                for (var field in formfields) {
                    if (formfields.hasOwnProperty(field)) {
                        fields[field] = formfields[field];
                    }
                }

                // current fields
                if (self.$CurrentCategory) {
                    Form = self.$CurrentCategory;
                    data = {};

                    if (Form.nodeName !== 'FORM') {
                        Form = self.$CurrentCategory.getElement('form');
                    }

                    if (Form && Form.nodeName === 'FORM') {
                        data = QUIFormUtils.getFormData(Form);
                    }

                    formfields = Object.filter(data, function (value, key) {
                        return (key.indexOf('field-') >= 0);
                    });

                    for (field in formfields) {
                        if (formfields.hasOwnProperty(field)) {
                            fields[field] = formfields[field];
                        }
                    }
                }

                if (typeof data.categories === 'undefined') {
                    data.categories = '';
                }

                var categories = data.categories.split(',');

                categories = categories.filter(function (item) {
                    return item !== '';
                });

                Products.updateChild(
                    self.getAttribute('productId'),
                    categories,
                    data['product-category'],
                    fields
                ).then(function () {
                    self.Loader.hide();
                    return self.loadData().then(resolve);

                }, function (err) {
                    self.Loader.hide();
                    reject(err);
                });
            });
        },

        /**
         * Copy the product
         *
         * @returns {Promise}
         */
        copy: function () {
            return new Promise(function (resolve, reject) {
                this.$Product.getTitle().then(function (title) {
                    new QUIConfirm({
                        icon       : 'fa fa-copy',
                        title      : QUILocale.get(lg, 'products.window.copy.title', {
                            id   : this.$Product.getId(),
                            title: title
                        }),
                        text       : QUILocale.get(lg, 'products.window.copy.text', {
                            id   : this.$Product.getId(),
                            title: title
                        }),
                        texticon   : false,
                        information: QUILocale.get(lg, 'products.window.copy.information', {
                            id   : this.$Product.getId(),
                            title: title
                        }),
                        autoclose  : false,
                        maxHeight  : 300,
                        maxWidth   : 450,

                        ok_button: {
                            text     : QUILocale.get('quiqqer/system', 'copy'),
                            textimage: 'fa fa-copy'
                        },

                        events: {
                            onSubmit: function (Win) {
                                Win.Loader.show();

                                Products.copy(this.$Product.getId())
                                    .then(function (newProductId) {

                                        require([
                                            'package/quiqqer/products/bin/controls/products/Product'
                                        ], function (ProductPanel) {

                                            new ProductPanel({
                                                productId: newProductId
                                            }).inject(this.getParent());

                                            Win.close();

                                        }.bind(this));

                                    }.bind(this)).catch(reject);

                            }.bind(this),

                            onClose: resolve
                        }
                    }).open();

                }.bind(this));
            }.bind(this));
        },

        /**
         * Change the product status - activate / deactivate
         */
        $onActivationStatusChange: function () {
            var Button = this.getButtons('status');
            Button.disable();

            this.update().then(function () {
                if (Button.getStatus()) {
                    this.$Product.activate().then(this.refresh, this.refresh);
                } else {
                    this.$Product.deactivate().then(this.refresh, this.refresh);
                }
            }.bind(this));
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
         * Add a field to the product
         *
         * @param {Number} fieldId
         * @returns {*|Promise|Object}
         */
        removeField: function (fieldId) {
            return this.$Product.removeField(fieldId).then(function () {
                this.$injected = false;
                return this.$onInject();
            }.bind(this));
        },

        /**
         * open add field dialog
         *
         * @param {string} [fieldTypeFilter]
         * @return {Promise}
         */
        openAddFieldDialog: function (fieldTypeFilter) {
            return new Promise(function (resolve) {

                new FieldWindow({
                    fieldTypeFilter: fieldTypeFilter,
                    events         : {
                        onSubmit: function (Win, value) {
                            Win.Loader.show();

                            this.addField(value[0]).then(function () {
                                Win.close();
                            });
                        }.bind(this),

                        onClose: resolve
                    }
                }).open();

            }.bind(this));
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number} fieldId
         */
        openDeleteFieldDialog: function (fieldId) {
            new QUIConfirm({
                icon       : 'fa fa-trash',
                texticon   : 'fa fa-trash',
                title      : QUILocale.get(lg, 'product.fields.remove.window.title'),
                text       : QUILocale.get(lg, 'product.fields.remove.window.text'),
                information: QUILocale.get(lg, 'product.fields.remove.window.information', {
                    fieldId: fieldId
                }),
                maxHeight  : 300,
                maxWidth   : 450,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        this.removeField(fieldId).then(function () {
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

                        if (this.$Control) {
                            this.$Control.destroy();
                            this.$Control = null;
                        }

                        if (this.$Grid) {
                            this.$Grid.destroy();
                        }

                        if (this.$FieldAdministration) {
                            this.$FieldAdministration.set('html', '');
                        }

                        nodes.setStyles({
                            display: 'none',
                            opacity: 0
                        });

                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * storage the content fields
         */
        $saveEditorContent: function () {
            if (this.$Control) {
                var currentField = this.$currentField;

                if (!(currentField in this.$data)) {
                    this.$data[currentField] = {};
                }

                this.$data[currentField].value = this.$Control.save();
            }
        },

        /**
         * Show a category
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $showCategory: function (Node) {
            this.$CurrentCategory = Node;

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
         * Create the media folder for the product
         *
         * @param {Number|Boolean} [fieldId] - Media Folder Field-ID
         * @return {Promise}
         */
        $createMediaFolder: function (fieldId) {
            var self = this;

            this.Loader.hide();

            return this.$Product.createMediaFolder(fieldId).then(function () {
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
                self.$ImageViewer.show();

                self.$FileViewer.refresh();
                self.$FileViewer.show();

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
        },

        /**
         * event: click at create media folder
         *
         * @param {Object} Button - qui button
         */
        $onCreateMediaFolderClick: function (Button) {
            var self = this;

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            this.$createMediaFolder().then(function () {
                self.getElm().getElements('.folder-missing-container').destroy();
            });
        }
    });
});
