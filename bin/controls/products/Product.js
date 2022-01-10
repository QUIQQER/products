/**
 * Edit and manage one product - Product Panel
 *
 * @module package/quiqqer/products/bin/controls/products/Product
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/Product', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'qui/controls/buttons/ButtonSwitch',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'Ajax',
    'Locale',
    'Users',
    'controls/grid/Grid',
    'controls/projects/project/media/FolderViewer',
    'Mustache',
    'Packages',
    'utils/Lock',
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

], function (QUI, QUIPanel, QUIButton, QUISwitch, QUIButtonSwitch, QUIConfirm, QUIFormUtils,
             QUIAjax, QUILocale, Users, Grid, FolderViewer, Mustache, Packages, Locker,
             Products, Product, Categories, Fields, FieldUtils, FieldWindow,
             CategorySelect, FieldTypeSelect, informationTemplate, templateProductData,
             templateProductPrices, templateField) {
    "use strict";

    var lg   = 'quiqqer/products',
        User = Users.getUserBySession();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Product',

        Binds: [
            'refresh',
            'update',
            'save',
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
            '$onCreateMediaFolderClick',
            '$render',
            '$checkUrl',
            '$fieldCategoryClick'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title'),
                icon : 'fa fa-shopping-bag',
                '#id': "productId" in options ? options.productId : false
            });

            this.parent(options);

            this.$FieldContainer = null;
            this.$currentField = null;
            this.$FileViewer = null;
            this.$ImageViewer = null;
            this.$Grid = null;
            this.$FieldAdministration = null;
            this.$AttributeList = null;
            this.$Data = null;
            this.$Media = null;
            this.$Files = null;
            this.$Control = null;
            this.$Information = null;
            this.$CurrentCategory = null;
            this.$FieldHelpContainer = null;

            this.$productFolder = null;

            this.$Product = new Product({
                id: this.getAttribute('productId')
            });

            this.$data = {};
            this.$injected = false;

            this.$executeUnloadForm = true;

            this.addEvents({
                onCreate : this.$onCreate,
                onInject : this.$onInject,
                onDestroy: function () {
                    if (this.$Product) {
                        Locker.unlock('product_' + this.$Product.getId());
                    }
                }.bind(this)
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
                            console.error(err);
                        });
                    }.bind(this)
                }
            });

            this.addButton({
                type: 'separator',
                name: 'actionSeparator'
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


            this.Loader.setAttribute('opacity', 1);
            this.Loader.setAttribute('styles', {
                background: '#fff'
            });

            this.Loader.show();
        },

        /**
         * event : on inject
         *
         * @return {Promise}
         */
        $onInject: function () {
            return this.$render().then(function () {
                var UserLoad = Promise.resolve();

                if (!User.isLoaded()) {
                    UserLoad = User.load();
                }

                return UserLoad;
            }).then(function () {
                var wantedCategory = User.getAttribute(
                    'quiqqer.erp.productPanel.open.category'
                );

                var Category = this.getCategory('information');

                if (wantedCategory && this.getCategory(wantedCategory)) {
                    Category = this.getCategory(wantedCategory);
                }

                if (typeOf(Category) === 'qui/controls/buttons/Button') {
                    Category.click();
                }

                return Locker.isLocked(
                    'product_' + this.$Product.getId()
                ).then(function (isLocked) {
                    if (isLocked) {
                        var message = QUILocale.get(lg, 'products.fields.panel.locked', {
                            username: isLocked.username
                        });

                        var LockContainer = new Element('div', {
                            'class': 'product-update-locked',
                            'html' : '<span class="fa fa-edit"></span>' +
                                '<span>' + message + '</span>' +
                                '<span></span>'
                        }).inject(this.getElm());

                        new QUIButton({
                            text  : QUILocale.get(lg, 'products.fields.panel.locked.btn.equal'),
                            styles: {
                                'float': 'none',
                                display: 'inline-block',
                                margin : '20px 10px'
                            },
                            events: {
                                onClick: function () {
                                    LockContainer.destroy();
                                }
                            }
                        }).inject(LockContainer);

                        new QUIButton({
                            text  : QUILocale.get('quiqqer/system', 'cancel'),
                            styles: {
                                'float': 'none',
                                display: 'inline-block',
                                margin : '20px 10px'
                            },
                            events: {
                                onClick: function () {
                                    this.minimize(function () {
                                        this.destroy();
                                    }.bind(this));
                                }.bind(this)
                            }
                        }).inject(LockContainer);
                        return;
                    }

                    return Locker.lock('product_' + this.$Product.getId());
                }.bind(this));

                // this.Loader.hide();
            }.bind(this));
        },

        /**
         * Render all categories and sheets
         *
         * @return {Promise}
         */
        $render: function () {
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

                if (!self.getHeader().getElement('.fa-shield')) {
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
                }
            }).then(function () {
                return self.loadData();
            }).then(function () {

                // get product data
                return Promise.all([
                    self.$Product.getFields(),
                    self.$Product.getCategories(),
                    self.$Product.isActive()
                ]);

                // render
            }).then(function (data) {
                var fields     = data[0],
                    categories = data[1],
                    isActive   = data[2];

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

                Content.set({
                    html: '<div class="product-update-information sheet"></div>' +
                        '<div class="product-update-data sheet"></div>' +
                        '<div class="product-update-field sheet"></div>' +
                        '<div class="product-update-media sheet"></div>' +
                        '<div class="product-update-files sheet"></div>' +
                        '<div class="product-update-prices sheet"></div>' +
                        '<div class="product-update-fieldadministration sheet"></div>' +
                        '<div class="product-update-attributelist sheet"></div>'
                });

                self.$Data = Content.getElement('.product-update-data');
                self.$Information = Content.getElement('.product-update-information');
                self.$Media = Content.getElement('.product-update-media');
                self.$Files = Content.getElement('.product-update-files');
                self.$Prices = Content.getElement('.product-update-prices');
                self.$Priority = Content.getElement('[name="product-priority"]');

                self.$FieldAdministration = Content.getElement('.product-update-fieldadministration');
                self.$AttributeList = Content.getElement('.product-update-attributelist');

                Content.getElements('.sheet').setStyles({
                    display: 'none'
                });

                self.$FieldContainer = Content.getElement('.product-update-field');

                // fields
                var field;

                // Felderaufbau
                return Promise.all([
                    Categories.getFields(categories),
                    Fields.getSystemFields(),
                    Fields.getStandardFields(),
                    Fields.getFieldTypes(),
                    self.$getFieldCategories()
                ]).then(function (result) {
                    var fieldList        = [],
                        fieldTypes       = {},
                        categoriesFields = result[0],
                        systemFields     = result[1],
                        standardFields   = result[2],
                        fieldCategories  = result[4];

                    var types = result[3];

                    for (i = 0, len = types.length; i < len; i++) {
                        fieldTypes[types[i].name] = types[i];
                    }

                    var complete = [].append(categoriesFields)
                        .append(systemFields)
                        .append(standardFields)
                        .append(fields);

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

                    fieldList = FieldUtils.sortFields(fieldList);
                    diffFields = FieldUtils.sortFields(diffFields);
                    systemFields = FieldUtils.sortFields(systemFields);

                    self.$createCategories(fieldList, fieldCategories, fieldTypes);

                    self.$dataFields = [];
                    self.$systemFields = [];

                    // normal data fields
                    for (i = 0, len = systemFields.length; i < len; i++) {
                        field = systemFields[i];

                        // dont show media folder field
                        if (field.id === Fields.FIELD_FOLDER) {
                            // @todo beachten
                            // new Element('input', {
                            //     type          : 'hidden',
                            //     'data-fieldid': field.id,
                            //     name          : 'field-' + field.id
                            // }).inject(self.getElm().getElement('form'));
                            continue;
                        }

                        if (field.type === 'TextareaMultiLang' ||
                            field.type === 'Textarea' ||
                            field.type === 'Folder' ||
                            field.type === 'Products'
                        ) {
                            continue;
                        }

                        if (typeof fieldTypes[field.type] !== 'undefined' &&
                            fieldTypes[field.type].category) {
                            continue;
                        }

                        self.$dataFields.push(field);
                    }

                    // system fields
                    for (i = 0, len = diffFields.length; i < len; i++) {
                        field = diffFields[i];

                        if (field.type === 'TextareaMultiLang' ||
                            field.type === 'Textarea' ||
                            field.type === 'Folder' ||
                            field.type === 'Products'
                        ) {
                            continue;
                        }

                        // wenn es ein feld ist, welches der kunde ausfüllen muss
                        // nicht anzeigen
                        if (field.custom) {
                            continue;
                        }

                        if (typeof fieldTypes[field.type] !== 'undefined' &&
                            fieldTypes[field.type].category) {
                            continue;
                        }

                        self.$systemFields.push(field);
                    }

                    self.$renderData(self.$Data, self.$Product);
                    self.refresh();
                });
            }).catch(function (err) {
                console.error(err);
                self.destroy();
            });
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            this.parent();

            return this.$Product.isActive().then(function (status) {
                var Button = this.getButtons('status');
                var Save = this.getButtons('update');

                Save.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'save'));

                // product is active
                if (status) {
                    Button.setSilentOn();
                    Button.setAttribute('text', QUILocale.get('quiqqer/products', 'product.panel.status.activate'));
                    Button.enable();
                    return;
                }

                // product is deactivate
                Button.setSilentOff();
                Button.setAttribute('text', QUILocale.get('quiqqer/products', 'product.panel.status.deactivate'));
                Button.enable();
            }.bind(this));
        },

        /**
         * Create panel categories
         *
         * @param {Object} fields
         * @param {Array} fieldCategories
         * @param {Object} fieldTypes - list of the field types data
         */
        $createCategories: function (fields, fieldCategories, fieldTypes) {
            var self = this;

            var fieldClick = function (Btn) {
                self.Loader.show();
                self.openField(Btn.getAttribute('fieldId')).then(function () {
                    self.Loader.hide();
                });
            };

            var imageFolderClick = function (Btn) {
                self.Loader.show();
                self.openMediaFolderField(Btn.getAttribute('fieldId')).then(function () {
                    self.Loader.hide();
                });
            };

            var showCategory = function (type) {
                if (type === 'TextareaMultiLang' ||
                    type === 'Textarea' ||
                    type === 'Products') {
                    return true;
                }

                if (typeof fieldTypes[type] === 'undefined') {
                    return true;
                }

                return !!fieldTypes[type].category;
            };

            this.getCategoryBar().clear();


            this.addCategory({
                name  : 'information',
                text  : QUILocale.get('quiqqer/system', 'information'),
                icon  : 'fa fa-info',
                events: {
                    onClick: function () {
                        self.Loader.show();
                        self.openInformation().then(function () {
                            self.Loader.hide();
                        });
                    }
                }
            });

            this.addCategory({
                name  : 'data',
                text  : QUILocale.get('quiqqer/system', 'data'),
                icon  : 'fa fa-shopping-bag',
                events: {
                    onClick: function () {
                        self.Loader.show();
                        self.openData().then(function () {
                            self.Loader.hide();
                        });
                    }
                }
            });

            this.addCategory({
                name  : 'prices',
                text  : QUILocale.get(lg, 'products.product.panel.category.prices'),
                icon  : 'fa fa-money',
                events: {
                    onClick: function () {
                        self.Loader.show();
                        self.openPrices().then(function () {
                            self.Loader.hide();
                        });
                    }
                }
            });

            var i, len, icon, type, text;

            // API categories
            for (i = 0, len = fieldCategories.length; i < len; i++) {
                text = '';

                if (fieldCategories[i].text.length) {
                    text = QUILocale.get(
                        fieldCategories[i].text[0],
                        fieldCategories[i].text[1]
                    );
                }

                this.addCategory({
                    name  : 'fieldCategory-' + fieldCategories[i].name,
                    text  : text,
                    icon  : fieldCategories[i].icon,
                    events: {
                        onClick: this.$fieldCategoryClick
                    }
                });
            }

            for (i = 0, len = fields.length; i < len; i++) {
                type = fields[i].type;

                if (showCategory(type) === false) {
                    continue;
                }

                icon = 'fa fa-file-text-o';

                if (type === 'Products') {
                    icon = 'fa fa-shopping-bag';
                }

                this.addCategory({
                    name   : 'field-' + fields[i].id,
                    text   : fields[i].workingtitle || fields[i].title,
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
                    onClick: function () {
                        self.Loader.show();
                        self.openImages().then(function () {
                            self.Loader.hide();
                        });
                    }
                }
            });

            this.addCategory({
                name  : 'files',
                text  : QUILocale.get(lg, 'products.product.panel.category.files'),
                icon  : 'fa fa-file-text',
                events: {
                    onClick: function () {
                        self.Loader.show();
                        self.openFiles().then(function () {
                            self.Loader.hide();
                        });
                    }
                }
            });

            for (i = 0, len = fields.length; i < len; i++) {
                if (fields[i].type !== 'Folder') {
                    continue;
                }

                if (fields[i].id === Fields.FIELD_FOLDER) {
                    continue;
                }

                this.addCategory({
                    name   : 'images',
                    text   : fields[i].workingtitle || fields[i].title,
                    icon   : 'fa fa-folder-open',
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
                    onClick: function () {
                        self.Loader.show();
                        self.openAttributeList().then(function () {
                            self.Loader.hide();
                        });
                    }
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
         *
         * @param Container
         * @param Product
         */
        $fillDataToContainer: function (Container, Product) {
            var Form = Container.getElement('form');

            if (!Form) {
                return;
            }

            var self       = this,
                attributes = Product.getAttributes(),
                fields     = attributes.fields;

            // fill field values
            fields.each(function (field) {
                var Input = Form.elements['field-' + field.id];

                if (typeof Input !== 'undefined') {
                    if (typeOf(field.value) !== 'string' &&
                        field.value !== null &&
                        typeOf(field.value) !== 'number'
                    ) {
                        field.value = JSON.encode(field.value);
                    }

                    Input.value = field.value;

                    if (typeof Input.get === 'function' && Input.get('data-quiid')) {
                        var Control = QUI.Controls.getById(Input.get('data-quiid'));

                        if (typeof Control.setData === 'function') {
                            Control.setData(field.value);
                        }
                    }
                }

                if (parseInt(field.id) === parseInt(Fields.FIELD_FOLDER)) {
                    self.$productFolder = field.value;
                }
            });

            // image fields
            var images = Container.getElements(
                '[data-qui="package/quiqqer/products/bin/controls/fields/types/Image"]'
            );

            images.each(function (Input) {
                var quiId   = Input.get('data-quiid'),
                    Control = QUI.Controls.getById(quiId);

                if (Control) {
                    Control.setAttribute('productFolder', self.$productFolder);

                    if (Input.value !== '') {
                        Control.refresh();
                    }
                }
            });

            // events
            var fieldChange = function (Field) {
                if (!("getFieldId" in Field)) {
                    return;
                }

                var fieldId = Field.getFieldId(),
                    value   = Field.getValue();

                Product.getField(fieldId).then(function (Field) {
                    Field.value = value;
                });
            };

            QUI.Controls.getControlsInElement(Container).each(function (Field) {
                if (!Field.getType().match("controls/fields/types/")) {
                    return;
                }

                Field.addEvent('change', fieldChange);
            });
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
                return self.$renderInformation(self.$Information, self.$Product);
            }).then(function () {
                return self.$showCategory(self.$Information);
            });
        },

        /**
         * render the information panel
         *
         * @param Container
         * @param Product
         * @return {Promise}
         */
        $renderInformation: function (Container, Product) {
            var productId = this.getAttribute('productId');

            return Product.getCategories().then(function (data) {
                return Categories.getCategories(data);
            }).then(function (categories) {
                // eigenes bild hohlen, wenn leer, oder nicht existiert, egal
                return new Promise(function (resolve) {
                    Product.getImage().then(resolve).catch(function () {
                        resolve(false);
                    });
                }).then(function (image) {
                    return Promise.all([
                        Product.getTitle(),
                        Product.getDescription(),
                        image,
                        categories,
                        Product.getAttributes()
                    ]);
                });
            }).then(function (data) {
                var categories = data[3].map(function (Category) {
                    return {title: Category.title};
                });

                var image = data[2] ? URL_DIR + data[2] : false;

                Container.set({
                    html: Mustache.render(informationTemplate, {
                        id               : productId,
                        title            : data[0],
                        description      : data[1],
                        image            : image,
                        categories       : categories,
                        fields           : [],
                        c_date           : data[4].c_date || '---',
                        c_user           : data[4].c_user,
                        e_date           : data[4].e_date,
                        e_user           : data[4].e_user,
                        productCategories: QUILocale.get(lg, 'productCategories'),
                        productImage     : QUILocale.get(lg, 'productImage'),
                        productEDate     : QUILocale.get('quiqqer/system', 'editdate'),
                        productEUser     : QUILocale.get('quiqqer/system', 'edituser'),
                        productCDate     : QUILocale.get('quiqqer/system', 'createdate'),
                        productCUser     : QUILocale.get('quiqqer/system', 'createuser')
                    })
                });

                return QUI.parse(Container);
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
                return self.$renderData(self.$Data, self.$Product);
            }).then(function () {
                return self.$showCategory(self.$Data);
            });
        },

        /**
         *
         * @param Container
         * @param Product
         */
        $renderData: function (Container, Product) {
            var self = this;
            var data = {};

            // get product data
            return Promise.all([
                Product.getFields(),
                Product.getCategories(),
                Product.getCategory()
            ]).then(function (result) {
                var fields     = result[0],
                    categories = result[1],
                    category   = result[2];

                if (typeOf(fields) !== 'array') {
                    fields = [];
                }

                if (typeOf(categories) !== 'array') {
                    categories = [];
                }

                // set values
                fields.each(function (Field) {
                    data[Field.id] = Field;
                });

                Container.set('html', Mustache.render(templateProductData, {
                    productCategories: QUILocale.get(lg, 'productCategories'),
                    productCategory  : QUILocale.get(lg, 'productCategory'),
                    productAttributes: QUILocale.get(lg, 'productAttributes'),
                    productMasterData: QUILocale.get(lg, 'productMasterData'),
                    productPriority  : QUILocale.get(lg, 'productPriority')
                }));

                var MainCategoryRow = Container.getElement('.product-mainCategory');
                var MainCategory = Container.getElement('[name="product-category"]');


                // categories
                var Select = new CategorySelect({
                    name  : 'categories',
                    events: {
                        onDelete: function (Select, Item) {
                            var categoryId = Item.getAttribute('categoryId');
                            var Option = MainCategory.getElement('[value="' + categoryId + '"]');

                            if (Option) {
                                Option.destroy();
                            }
                        },
                        onChange: function () {
                            var ids = Select.getValue();

                            if (ids === '') {
                                ids = [];
                            } else {
                                ids = ids.split(',');
                            }

                            ids.each(function (id) {
                                if (MainCategory.getElement('[value="' + id + '"]')) {
                                    return;
                                }
                                new Element('option', {
                                    value: id,
                                    html : QUILocale.get(lg, 'products.category.' + id + '.title')
                                }).inject(MainCategory);
                            });

                            if (ids.length) {
                                MainCategoryRow.setStyle('display', null);
                            } else {
                                MainCategoryRow.setStyle('display', 'none');
                            }
                        }
                    }
                }).inject(
                    Container.getElement('.product-categories')
                );

                if (categories.length) {
                    MainCategoryRow.setStyle('display', null);
                    MainCategory.set('html', '');
                }

                categories.each(function (id) {
                    Select.addCategory(id);

                    if (MainCategory.getElement('[value="' + id + '"]')) {
                        return;
                    }

                    new Element('option', {
                        value: id,
                        html : QUILocale.get(lg, 'products.category.' + id + '.title')
                    }).inject(MainCategory);
                });

                MainCategory.value = category;

                // render data fields
                var i, len;
                var DataContainer = Container.getElement('.product-data tbody');

                for (i = 0, len = self.$dataFields.length; i < len; i++) {
                    self.$renderDataField(self.$dataFields[i]).inject(DataContainer);
                }

                // render system fields
                var SystemContainer = Container.getElement('.product-standardfield tbody');

                for (i = 0, len = self.$systemFields.length; i < len; i++) {
                    self.$renderDataField(self.$systemFields[i]).inject(SystemContainer);
                }

                return QUI.parse(Container);
            }).then(function () {
                self.$fillDataToContainer(Container, Product);

                // change fields button
                if (!Container.getElement('[name="edit-fields"]')) {
                    new QUIButton({
                        text  : QUILocale.get(lg, 'product.fields.administration'),
                        name  : 'edit-fields',
                        styles: {
                            display: 'block',
                            'float': 'none',
                            margin : '0 auto 20px',
                            width  : 200
                        },
                        events: {
                            onClick: self.openFieldAdministration
                        }
                    }).inject(Container);
                }

                QUI.Controls.getControlsInElement(Container).each(function (Field) {
                    if (!("getFieldId" in Field)) {
                        return;
                    }

                    if (!("setValue" in Field)) {
                        return;
                    }

                    var fieldId = Field.getFieldId();

                    if (fieldId in data) {
                        Field.setValue(data[fieldId].value);
                    }
                });


                // set url events
                var UrlRow = Container.getElement('[data-fieldid="19"]');

                if (UrlRow) {
                    var inputs = UrlRow.getElements('input');

                    inputs.removeEvents('blur');
                    inputs.addEvent('blur', self.$checkUrl);
                }
            }).catch(function (err) {
                if (typeof err.getMessage !== 'undefined') {
                    console.error(err.getMessage());
                } else {
                    console.error(err);
                }
            });
        },

        /**
         * Render data fieldsets
         *
         * @param field
         * @return {Element}
         */
        $renderDataField: function (field) {
            var help  = false,
                title = QUILocale.get(lg, 'products.field.' + field.id + '.title');

            if (QUILocale.exists(lg, 'products.field.' + field.id + '.workingtitle')) {
                title = QUILocale.get(lg, 'products.field.' + field.id + '.workingtitle');
            }

            if (QUILocale.exists(lg, 'products.field.' + field.id + '.description')) {
                help = QUILocale.get(lg, 'products.field.' + field.id + '.description');
            } else if (field.help && field.help !== '') {
                help = field.help;
            }

            var FieldElm = new Element('tr', {
                'class'       : 'field',
                html          : Mustache.render(templateField, {
                    fieldTitle: title,
                    fieldHelp : help,
                    fieldName : 'field-' + field.id,
                    control   : field.jsControl
                }),
                'data-fieldid': field.id
            });

            var HelpIcon = FieldElm.getElement('.field-container-item-help');

            if (HelpIcon) {
                HelpIcon.addEvent('click', (event) => {
                    event.stop();
                });
            }

            return FieldElm;
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
                return self.$renderPrices(self.$Prices, self.$Product);
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
         * Render the price html nodes
         *
         * @return {Promise}
         */
        $renderPrices: function (Container, Product) {
            return Promise.all([
                Product.getFieldsByType(Fields.TYPE_PRICE),
                Product.getFieldsByType(Fields.TYPE_PRICE_BY_QUANTITY),
                Product.getFieldsByType(Fields.TYPE_PRICE_BY_TIMEPERIOD)
            ]).then(function (fields) {
                fields = fields.flatten();

                // sort by priority and mein price as first
                fields.sort(function (FieldA, FieldB) {
                    var priorityA = parseInt(FieldA.priority),
                        priorityB = parseInt(FieldB.priority);

                    if (parseInt(FieldA.id) === parseInt(Fields.FIELD_PRICE)) {
                        return -1;
                    }

                    if (parseInt(FieldB.id) === parseInt(Fields.FIELD_PRICE)) {
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

                for (let [k, Field] of Object.entries(fields)) {
                    if (Field.value && typeof Field.value !== 'string') {
                        Field.value = JSON.encode(Field.value);
                    }

                    fields[k] = Field;
                }

                Container.set({
                    html: Mustache.render(templateProductPrices, {
                        title : QUILocale.get(lg, 'products.product.panel.category.prices'),
                        fields: fields
                    })
                });

                return QUI.parse(Container);
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

            return this.$hideCategories().then(() => {
                return this.$renderFolderViewer(this.$Media, this.$Product, ['image']);
            }).then((Viewer) => {
                Viewer.refresh();

                return this.$showCategory(this.$Media);
            });
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

            return this.$hideCategories().then(() => {
                return this.$renderFolderViewer(self.$Files, self.$Product, ['file']);
            }).then((Viewer) => {
                Viewer.refresh();

                return this.$showCategory(this.$Files);
            });
        },

        /**
         *
         * @param Container
         * @param Product
         * @param {Array} types
         * @param {Number} [fileId]
         * @return {Promise}
         */
        $renderFolderViewer: function (Container, Product, types, fileId) {
            var self = this;

            Container.set('html', '');

            if (typeof fileId === 'undefined') {
                fileId = Fields.FIELD_FOLDER;
            }

            return Promise.all([
                Products.getParentFolder(),
                Product.getFields()
            ]).then(function (result) {
                var Folder = result[0];
                var fields = result[1];
                var productFolder = false;

                var folderFields = fields.filter(function (field) {
                    return parseInt(field.id) === parseInt(Fields.FIELD_FOLDER);
                });

                if (folderFields.length) {
                    productFolder = folderFields[0].value;
                }

                var Viewer = new FolderViewer({
                    folderUrl    : productFolder,
                    Parent       : Folder,
                    newFolderName: Product.getId(),
                    filetype     : types,
                    autoactivate : true
                }).inject(Container);

                if (!productFolder) {
                    Viewer.hide();

                    var ButtonContainer = Container.getElement('.folder-missing-container');

                    if (!Container.getElement('.folder-missing-container')) {
                        ButtonContainer = new Element('div', {
                            'class': 'folder-missing-container',
                            html   : QUILocale.get(lg, 'products.product.panel.folder.missing')
                        }).inject(Container);
                    }


                    new QUIButton({
                        text     : QUILocale.get(lg, 'products.product.panel.folder.missing.button'),
                        textimage: 'fa fa-plus',
                        styles   : {
                            clear : 'both',
                            margin: '20px 0 0 0'
                        },
                        events   : {
                            onClick: function (Button) {
                                self.$onCreateMediaFolderClick(Button, Product, Viewer, fileId);
                            }
                        }
                    }).inject(ButtonContainer);
                }

                return Viewer;
            });
        },

        /**
         * Open a textarea field
         *
         * @param {Number} fieldId
         */
        openField: function (fieldId) {
            return this.$hideCategories().then(() => {
                return this.$renderField(this.$FieldContainer, this.$Product, fieldId);
            }).then(() => {
                this.$CurrentCategory = this.$FieldContainer;

                return this.$showCategory(this.$FieldContainer);
            });
        },

        /**
         * Render a field
         *
         * @param Container
         * @param Product
         * @param fieldId
         */
        $renderField: function (Container, Product, fieldId) {
            Container.set('html', '');

            Product.getField(fieldId).then(function (Field) {
                return new Promise(function (resolve) {
                    require([Field.jsControl], function (Control) {
                        var Instance = new Control();

                        if (Field.help !== '') {
                            var HelpContainer = new Element('div', {
                                html   : '<span class="fa fa-question"></span><span>' + Field.help + '</span>',
                                'class': 'product-category-help',
                                styles : {
                                    bottom  : 10,
                                    position: 'absolute'
                                }
                            }).inject(Container, 'after');

                            var height = HelpContainer.getSize().y + 10; // padding

                            HelpContainer.setStyles({
                                height: 'calc(100% - ' + height + 'px)'
                            });
                        }

                        if (Field && "value" in Field) {
                            Instance.setAttribute('value', Field.value);
                        }

                        Instance.setAttribute('field-id', fieldId);
                        Instance.inject(Container);

                        resolve(Instance);
                    });
                });
            });
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
                if (Field.value === '' || !Field.value) {
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

                                self.Loader.show();

                                self.$createMediaFolder(Field.id, self.$Product).then(function () {
                                    self.getElm().getElements('.folder-missing-container').destroy();
                                    self.openMediaFolderField(Field.id);
                                });
                            }
                        }
                    }).inject(Container);

                    return self.$showCategory(self.$FieldContainer);
                }

                new FolderViewer({
                    folderUrl   : Field.value,
                    filetype    : [
                        'image',
                        'file'
                    ],
                    autoactivate: !!parseInt(Field.options.autoActivateItems)
                }).inject(self.$FieldContainer);

                return self.$showCategory(self.$FieldContainer);
            });
        },

        /**
         * opens the field administration
         */
        openFieldAdministration: function () {
            var self = this;

            return self.$hideCategories().then(function () {

                var refresh = function () {
                    var FieldTypes = self.$Grid.getButtons().filter(function (Btn) {
                        return Btn.getAttribute('name') === 'select';
                    })[0];

                    self.$Product.getFields().then(function (fields) {
                        var i, len, entry, typeTitle;

                        var data = [];
                        var fieldType = FieldTypes.getValue();

                        for (i = 0, len = fields.length; i < len; i++) {
                            entry = fields[i];

                            if (fieldType !== '' && fieldType !== entry.type) {
                                continue;
                            }

                            typeTitle = entry.type;

                            if (QUILocale.exists('quiqqer/products', 'fieldtype.' + entry.type)) {
                                typeTitle = QUILocale.get('quiqqer/products', 'fieldtype.' + entry.type);
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
                                fieldtype      : typeTitle,
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
                                })
                            });
                        }

                        self.$Grid.setData({
                            data: data
                        });
                    });
                };

                var GridContainer = new Element('div', {
                    styles: {
                        'float': 'left',
                        height : '100%',
                        width  : '100%'
                    }
                }).inject(self.$FieldAdministration);


                self.$Grid = new Grid(GridContainer, {
                    buttons    : [
                        new FieldTypeSelect({
                            name  : 'select',
                            events: {
                                filterChange: refresh
                            }
                        }),
                        {
                            type: 'separator'
                        },
                        {
                            text     : QUILocale.get(lg, 'product.fields.add.field'),
                            textimage: 'fa fa-plus',
                            events   : {
                                onClick: function () {
                                    self.openAddFieldDialog().then(function () {
                                        self.openFieldAdministration();
                                    }).catch(function (err) {
                                        if (typeOf(err) !==
                                            'package/quiqqer/products/bin/controls/fields/search/Window') {
                                            console.error(err);
                                        }
                                    });
                                }
                            }
                        },
                        {
                            name     : 'remove',
                            text     : QUILocale.get(lg, 'product.fields.remove.field'),
                            disabled : true,
                            textimage: 'fa fa-trash',
                            events   : {
                                onClick: function () {
                                    self.openDeleteFieldDialog(self.$Grid.getSelectedData()[0].id).then(function () {
                                        self.openFieldAdministration();
                                    }).catch(function (err) {
                                        console.log(typeOf(err));
                                        if (typeOf(err) !== 'qui/controls/windows/Confirm') {
                                            console.error(err);
                                        }
                                    });
                                }
                            }
                        }
                    ],
                    columnModel: [
                        {
                            header   : QUILocale.get(lg, 'priority'),
                            dataIndex: 'priority',
                            dataType : 'number',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get(lg, 'product.fields.grid.visible'),
                            dataIndex: 'visible',
                            dataType : 'QUI',
                            width    : 60
                        },
                        {
                            header   : '&nbsp;',
                            dataIndex: 'ownFieldDisplay',
                            dataType : 'node',
                            width    : 30
                        },
                        {
                            header   : QUILocale.get('quiqqer/system', 'id'),
                            dataIndex: 'id',
                            dataType : 'number',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get('quiqqer/system', 'title'),
                            dataIndex: 'title',
                            dataType : 'text',
                            width    : 200
                        },
                        {
                            header   : QUILocale.get(lg, 'workingTitle'),
                            dataIndex: 'workingtitle',
                            dataType : 'text',
                            width    : 200
                        },
                        {
                            header   : QUILocale.get(lg, 'fieldtype'),
                            dataIndex: 'fieldtype',
                            dataType : 'text',
                            width    : 200
                        },
                        {
                            header   : QUILocale.get(lg, 'prefix'),
                            dataIndex: 'prefix',
                            dataType : 'text',
                            width    : 100
                        },
                        {
                            header   : QUILocale.get(lg, 'suffix'),
                            dataIndex: 'suffix',
                            dataType : 'text',
                            width    : 100
                        },
                        {
                            header   : QUILocale.get(lg, 'source'),
                            dataIndex: 'source',
                            dataType : 'text',
                            width    : 400
                        },
                        {
                            dataIndex: 'ownField',
                            hidden   : true
                        }
                    ]
                });

                var switchStatusChange = function (Switch) {
                    var fieldId = Switch.getAttribute('fieldId'),
                        status  = Switch.getStatus();

                    Switch.disable();

                    self.$Product.setPublicStatusFromField(fieldId, status).then(function () {
                        Switch.enable();
                    });
                };


                self.$Grid.addEvents({
                    onRefresh: refresh,
                    onClick  : function () {
                        var selected = self.$Grid.getSelectedData()[0],
                            Remove   = self.$Grid.getButtons().filter(function (Btn) {
                                return Btn.getAttribute('name') === 'remove';
                            })[0];

                        if (selected.ownField) {
                            Remove.enable();
                        } else {
                            Remove.disable();
                        }
                    },

                    onDblClick: function () {
                        self.Loader.show();
                        var selected = self.$Grid.getSelectedData()[0];

                        require([
                            'package/quiqqer/products/bin/controls/fields/windows/Field'
                        ], function (FieldWindow) {
                            new FieldWindow({
                                fieldId: selected.id,
                                events : {
                                    onOpen: function () {
                                        self.Loader.hide();
                                    }
                                }
                            }).open();
                        });
                    }
                });

                var size = self.$FieldAdministration.measure(function () {
                    return this.getSize();
                });

                return self.$Grid.setHeight(size.y - 40).then(function () {
                    self.$Grid.refresh();
                });
            }).then(function () {
                return self.$showCategory(self.$FieldAdministration);
            }).then(function () {
                self.Loader.hide();
                self.getCategory('data').setActive();

                return self.$Grid.resize();
            });
        },

        /**
         * opens the attribute list display
         *
         * @return {Promise}
         */
        openAttributeList: function () {
            var self = this;

            return self.$hideCategories().then(function () {

                var GridContainer = new Element('div', {
                    styles: {
                        'float': 'left',
                        height : '100%',
                        width  : '100%'
                    }
                }).inject(self.$AttributeList);

                self.$Grid = new Grid(GridContainer, {
                    sortOn     : 'calcPriority',
                    buttons    : [
                        {
                            text     : QUILocale.get(lg, 'product.fields.grid.button.addSelectList'),
                            textimage: 'fa fa-plus',
                            events   : {
                                onClick: function () {
                                    self.openAddFieldDialog('ProductAttributeList').then(function () {
                                        self.openAttributeList();
                                    }).catch(function (err) {
                                        if (typeOf(err) !==
                                            'package/quiqqer/products/bin/controls/fields/search/Window') {
                                            console.error(err);
                                        }
                                    });
                                }
                            }
                        },
                        {
                            type: 'separator'
                        },
                        {
                            name     : 'remove',
                            text     : QUILocale.get(lg, 'product.fields.grid.button.removeSelectList'),
                            textimage: 'fa fa-trash',
                            disabled : true,
                            events   : {
                                onClick: function () {
                                    self.openDeleteFieldDialog(self.$Grid.getSelectedData()[0].id).then(function () {
                                        self.openAttributeList();
                                    }).catch(function (err) {
                                        console.log(typeOf(err));
                                        if (typeOf(err) !== 'qui/controls/windows/Confirm') {
                                            console.error(err);
                                        }
                                    });
                                }
                            }
                        }
                    ],
                    columnModel: [
                        {
                            header   : '&nbsp;',
                            dataIndex: 'ownFieldDisplay',
                            dataType : 'node',
                            width    : 30
                        },
                        {
                            header   : QUILocale.get('quiqqer/system', 'id'),
                            dataIndex: 'id',
                            dataType : 'number',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get(lg, 'product.fields.grid.visible'),
                            dataIndex: 'visible',
                            dataType : 'QUI',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get(lg, 'priority'),
                            title    : QUILocale.get(lg, 'priority'),
                            dataIndex: 'sort',
                            dataType : 'string',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get(lg, 'product.fields.grid.calcPriority'),
                            title    : QUILocale.get(lg, 'product.fields.grid.calcPriority.desc'),
                            dataIndex: 'calcPriority',
                            dataType : 'string',
                            width    : 60
                        },
                        {
                            header   : QUILocale.get(lg, 'product.fields.grid.calcBasis'),
                            title    : QUILocale.get(lg, 'product.fields.grid.calcBasis.desc'),
                            dataIndex: 'calcBasis',
                            dataType : 'string',
                            width    : 100
                        },
                        {
                            header   : QUILocale.get('quiqqer/system', 'title'),
                            dataIndex: 'title',
                            dataType : 'text',
                            width    : 200
                        },
                        {
                            header   : QUILocale.get(lg, 'workingTitle'),
                            dataIndex: 'workingtitle',
                            dataType : 'text',
                            width    : 200
                        },
                        {
                            dataIndex: 'ownField',
                            hidden   : true
                        }
                    ]
                });

                var switchStatusChange = function (Switch) {
                    var fieldId = Switch.getAttribute('fieldId'),
                        status  = Switch.getStatus();

                    Switch.disable();

                    self.$Product.setPublicStatusFromField(fieldId, status).then(function () {
                        Switch.enable();
                    });
                };


                var refresh = function () {
                    self.$Product.getFields().then(function (fields) {
                        var i, len, entry,
                            options, calculation_basis, calculation_priority;

                        var data = [];

                        for (i = 0, len = fields.length; i < len; i++) {
                            entry = fields[i];

                            if (entry.type !== 'ProductAttributeList') {
                                continue;
                            }

                            calculation_priority = '';
                            calculation_basis = '';

                            options = entry.options;

                            if ("priority" in options) {
                                calculation_priority = options.priority;
                            }

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

                            data.push({
                                visible        : new QUISwitch({
                                    fieldId: entry.id,
                                    events : {
                                        onChange: switchStatusChange
                                    }
                                }),
                                sort           : entry.priority,
                                calcPriority   : calculation_priority,
                                calcBasis      : calculation_basis,
                                id             : entry.id,
                                title          : entry.title || '',
                                workingtitle   : entry.workingtitle || '',
                                ownField       : entry.ownField,
                                ownFieldDisplay: new Element('div', {
                                    'class': 'fa fa-user',
                                    styles : {
                                        color: entry.ownField ? '' : '#dddddd'
                                    }
                                })
                            });
                        }

                        self.$Grid.setData({
                            data: data
                        });
                    });
                };

                self.$Grid.addEvents({
                    onRefresh: refresh,
                    onClick  : function () {
                        var selected = self.$Grid.getSelectedData()[0],
                            Remove   = self.$Grid.getButtons().filter(function (Btn) {
                                return Btn.getAttribute('name') === 'remove';
                            })[0];

                        if (selected.ownField) {
                            Remove.enable();
                        } else {
                            Remove.disable();
                        }
                    }
                });

                var size = self.$AttributeList.measure(function () {
                    return this.getSize();
                });

                return self.$Grid.setHeight(size.y - 40).then(function () {
                    self.$Grid.refresh();
                    return self.$showCategory(self.$AttributeList);
                }).then(function () {
                    self.$Grid.resize();
                    self.getCategory('attributelist').setActive();
                    self.Loader.hide();
                });
            });
        },

        /**
         * Shows permissions for the product
         */
        openPermissions: function () {
            this.Loader.show();

            require([
                'package/quiqqer/products/bin/controls/products/permissions/Permissions'
            ], function (ProductPermissions) {
                var self = this;
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
         *
         * @returns {Promise}
         */
        update: function () {
            var self = this,
                Elm  = self.getElm();

            this.Loader.show();

            return this.$saveControl(this.$Product).then(function () {
                return self.$Product.getFields().then(function (fields) {
                    var result = {};
                    var i, len, field;

                    for (i = 0, len = fields.length; i < len; i++) {
                        field = fields[i];

                        result['field-' + field.id] = field.value;
                    }

                    return result;
                });
            }).then(function (fields) {
                var Form = Elm.getElement('form');
                var data = QUIFormUtils.getFormData(Form);
                var selfData = self.$data;

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

                if (typeof data.categories === 'undefined' || !data.categories.length) {
                    if (self.$Data.getElement('[name="product-category"]')) {
                        data['product-category'] = self.$Data.getElement('[name="product-category"]').value;
                    }

                    if (self.$Data.getElement('[name="categories"]')) {
                        data.categories = self.$Data.getElement('[name="categories"]').value;
                    } else if (typeof self.$categories !== 'undefined') {
                        data.categories = self.$categories;
                    } else {
                        data.categories = '';

                        if (typeof self.$Product.$data.categories !== 'undefined') {
                            data.categories = self.$Product.$data.categories;
                        }
                    }
                }

                var categories = data.categories.split(',');

                categories = categories.filter(function (item) {
                    return item !== '';
                });

                return Products.updateChild(
                    self.getAttribute('productId'),
                    categories,
                    data['product-category'],
                    fields
                );
            }).then(function () {
                return self.loadData();
            }).then(function () {
                var Active = self.getActiveCategory();

                // refresh category
                self.$executeUnloadForm = false;

                Active.setNormal();
                Active.click();
                Active.setActive();

                self.$executeUnloadForm = true;
            }).catch(function (err) {
                console.error(err);
                self.Loader.hide();
            });
        },

        /**
         * Copy the product
         *
         * @returns {Promise}
         */
        copy: function () {
            var self = this;

            return new Promise(function (resolve, reject) {
                self.$Product.getTitle().then(function (title) {
                    new QUIConfirm({
                        icon       : 'fa fa-copy',
                        title      : QUILocale.get(lg, 'products.window.copy.title', {
                            id   : self.$Product.getId(),
                            title: title
                        }),
                        text       : QUILocale.get(lg, 'products.window.copy.text', {
                            id   : self.$Product.getId(),
                            title: title
                        }),
                        texticon   : false,
                        information: QUILocale.get(lg, 'products.window.copy.information', {
                            id   : self.$Product.getId(),
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

                                Products.copy(self.$Product.getId()).then(function (newProductId) {
                                    require([
                                        'package/quiqqer/products/bin/controls/products/Product'
                                    ], function (ProductPanel) {
                                        new ProductPanel({
                                            productId: newProductId
                                        }).inject(self.getParent());

                                        Win.close();
                                    });
                                }).catch(reject);
                            },

                            onClose: resolve
                        }
                    }).open();
                });
            });
        },

        /**
         * Change the product status - activate / deactivate
         */
        $onActivationStatusChange: function () {
            var self   = this,
                Button = this.getButtons('status');

            Button.disable();

            var Prom = Promise.resolve();

            if (!Button.getStatus()) {
                Prom = this.$Product.deactivate();
            } else {
                Prom = self.$Product.activate();
            }

            Prom.then(function () {
                return self.update();
            }).then(self.refresh).catch(self.refresh);
        },

        /**
         * Add a field to the product
         *
         * @param {Number|Array} fieldId
         * @returns {Promise}
         */
        addField: function (fieldId) {
            return this.$Product.addField(fieldId).then(function () {
                this.$injected = false;
                return this.$render();
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
                return this.$render();
            }.bind(this));
        },

        /**
         * open add field dialog
         *
         * @param {string} [fieldTypeFilter]
         * @return {Promise}
         */
        openAddFieldDialog: function (fieldTypeFilter) {
            return new Promise(function (resolve, reject) {
                new FieldWindow({
                    fieldTypeFilter: fieldTypeFilter,
                    multiple       : true,
                    events         : {
                        onSubmit: function (Win, value) {
                            Win.Loader.show();

                            this.addField(value).then(function () {
                                Win.close();
                                resolve();
                            });
                        }.bind(this),

                        onCancel: reject
                    }
                }).open();
            }.bind(this));
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number} fieldId
         * @return {Promise}
         */
        openDeleteFieldDialog: function (fieldId) {
            return new Promise(function (resolve, reject) {
                new QUIConfirm({
                    icon       : 'fa fa-trash',
                    texticon   : 'fa fa-trash',
                    title      : QUILocale.get(lg, 'product.fields.remove.window.title'),
                    text       : QUILocale.get(lg, 'product.fields.remove.window.text'),
                    information: QUILocale.get(lg, 'product.fields.remove.window.information', {
                        fieldId: fieldId
                    }),
                    maxHeight  : 300,
                    maxWidth   : 600,
                    events     : {
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            this.removeField(fieldId).then(function () {
                                Win.close();
                                resolve();
                            });
                        }.bind(this),

                        onCancel: reject
                    }
                }).open();
            }.bind(this));
        },

        /**
         * Close all categories
         *
         * @returns {Promise}
         */
        $hideCategories: function () {
            var self  = this,
                nodes = this.getContent().getElements('.sheet');

            var done = function () {
                return new Promise(function (resolve) {
                    moofx(nodes).animate({
                        opacity: 0,
                        top    : -20
                    }, {
                        duration: 200,
                        callback: function () {
                            if (self.$Control) {
                                self.$Control.destroy();
                                self.$Control = null;
                            }

                            if (self.$FieldHelpContainer) {
                                self.$FieldHelpContainer.destroy();
                                self.$FieldHelpContainer = null;
                            }

                            if (self.$Grid) {
                                self.$Grid.destroy();
                            }

                            if (self.$FieldAdministration) {
                                self.$FieldAdministration.set('html', '');
                            }

                            // destroy controls
                            QUI.Controls.getControlsInElement(self.getContent()).forEach(function (Control) {
                                Control.destroy();
                            });


                            nodes.setStyles({
                                display: 'none',
                                opacity: 0
                            });

                            resolve();
                        }
                    });
                });
            };

            // no unload for variant sheets
            // otherwise the variant data will be put into the current product
            if (this.$CurrentCategory && this.$CurrentCategory.hasClass('variants-sheet')) {
                return done();
            }

            return this.$unloadCategory(
                this.$CurrentCategory,
                this.$Product
            ).then(function () {
                return done();
            });
        },

        /**
         *
         * @param {Element} Category
         * @param Product
         */
        $unloadCategory: function (Category, Product) {
            if (Category === null || !Category) {
                return Promise.resolve();
            }

            if (this.$executeUnloadForm === false) {
                return Promise.resolve();
            }

            var categorySavePromise = this.$saveControl(Product);

            var Form = Category.getElement('form');

            if (!Form) {
                if (categorySavePromise) {
                    return categorySavePromise;
                } else {
                    return Promise.resolve();
                }
            }

            var i, len, Felm, fieldId;
            var elements = Form.elements;
            var promises = [];

            for (i = 0, len = elements.length; i < len; i++) {
                Felm = elements[i];

                if (Felm.name === 'categories') {
                    this.$categories = Felm.value;
                }

                if (Felm.name.indexOf('field-') === -1) {
                    continue;
                }

                fieldId = Felm.name.replace('field-', '');
                fieldId = parseInt(fieldId);

                Product.setFieldValue(fieldId, Felm.value);
            }

            if (categorySavePromise) {
                promises.push(categorySavePromise);
            }

            return Promise.all(promises);
        },

        /**
         * storage the content fields
         *
         * @param {Object} Product
         * @return {Promise}
         */
        $saveControl: function (Product) {
            if (!this.$CurrentCategory) {
                return Promise.resolve();
            }

            var Control = this.$CurrentCategory.getElement('.qui-control');

            if (!Control) {
                Control = this.$CurrentCategory.getElement('[data-quiid]');
            }

            if (!Control || !Control.get('data-quiid')) {
                return Promise.resolve();
            }

            var QUIControl = QUI.Controls.getById(Control.get('data-quiid'));

            if (QUIControl && typeof QUIControl.save === 'function') {
                var fieldId    = QUIControl.getAttribute('field-id'),
                    fieldValue = QUIControl.save();

                Product.setFieldValue(fieldId, fieldValue);
                this.$data[fieldId] = fieldValue;
            }

            return Promise.resolve();
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
         * @param {Object} Product - Media Folder Field-ID
         * @return {Promise}
         */
        $createMediaFolder: function (fieldId, Product) {
            var self = this;

            this.Loader.hide();

            return Product.createMediaFolder(fieldId).then(function () {
                return Product.getFields();
            }).then(function (productFields) {
                var wantedId = fieldId || Fields.FIELD_FOLDER;

                var folder = productFields.filter(function (field) {
                    return parseInt(field.id) === parseInt(wantedId);
                });

                self.Loader.hide();

                if (folder.length) {
                    return folder[0].value;
                }

                return false;
            });
        },

        /**
         * event: click at create media folder
         *
         * @param {Object} Button - qui button
         * @param {Object} Product
         * @param {Object} Viewer - folder viewer
         * @param {Number} fileId
         */
        $onCreateMediaFolderClick: function (Button, Product, Viewer, fileId) {
            var self = this;

            Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            this.$createMediaFolder(fileId, Product).then(function (folder) {
                if (folder) {
                    Viewer.setAttribute('folderUrl', folder);
                    Viewer.refresh();
                    Viewer.show();
                }

                for (var id in self.$data) {
                    if (!self.$data.hasOwnProperty(id)) {
                        continue;
                    }

                    if (self.$data[id].id === fileId) {
                        self.$data[id].value = folder;
                        break;
                    }
                }

                self.getElm().getElements('.folder-missing-container').destroy();
            });
        },

        /**
         * Checks the url field
         *
         * @param event
         */
        $checkUrl: function (event) {
            var Target = event.target;
            var FieldInput = Target.getParent('tr').getElement('[name="field-19"]');
            var value = FieldInput.value;

            this.$Product.getCategory().then(function (categoryId) {
                QUIAjax.get('package_quiqqer_products_ajax_products_checkUrl', function (result) {
                    if (result.exists) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addError(result.message, Target);
                        });
                    }
                }, {
                    'package': 'quiqqer/products',
                    urls     : value,
                    category : categoryId
                });
            });
        },

        //region field categories

        /**
         *
         * @return {Promise}
         */
        $getFieldCategories: function () {
            return new Promise((resolve) => {
                QUIAjax.get('package_quiqqer_products_ajax_products_getFieldCategories', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getAttribute('productId')
                });
            });
        },

        /**
         * opens a category field list
         *
         * @param Category
         */
        $fieldCategoryClick: function (Category) {
            var self = this;

            return self.$hideCategories().then(function () {
                self.$FieldContainer.set('html', '');

                return new Promise(function (resolve) {
                    QUIAjax.get('package_quiqqer_products_ajax_products_getFieldCategory', function (fields) {
                        var Form = new Element('form', {
                            html: '' +
                                '<table class="data-table data-table-flexbox product-data">' +
                                '   <thead>' +
                                '       <tr>' +
                                '           <th>' +
                                '                ' + Category.getAttribute('text') +
                                '            </th>' +
                                '        </tr>' +
                                '   </thead>' +
                                '   <tbody></tbody>' +
                                '</table>'
                        }).inject(self.$FieldContainer);

                        var Body = Form.getElement('tbody');

                        for (var i = 0, len = fields.length; i < len; i++) {
                            self.$renderDataField(fields[i]).inject(Body);
                        }

                        QUI.parse(Body).then(resolve);
                    }, {
                        'package' : 'quiqqer/products',
                        'category': Category.getAttribute('name'),
                        productId : self.getAttribute('productId')
                    });
                });
            }).then(function () {
                self.$fillDataToContainer(self.$FieldContainer, self.$Product);
                self.Loader.hide();

                return self.$showCategory(self.$FieldContainer);
            });
        }

        //endregion
    });
});
