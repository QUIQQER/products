/**
 * Category panel
 * Edit / update the category
 *
 * @module package/quiqqer/products/bin/controls/categories/Category
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/categories/Category', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'qui/controls/windows/Confirm',
    'Locale',
    'Mustache',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/controls/products/Product',
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/productsearch/bin/controls/products/search/Window',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/categories/Category.html',
    'css!package/quiqqer/products/bin/controls/categories/Category.css'

], function (QUI, QUIPanel, QUIButton, QUISwitch, QUIConfirm, QUILocale, Mustache, Grid,
             ProductPanel, Categories, Fields, ProductSearchWindow, CategorySitemap, Translation, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/categories/Category',

        Binds: [
            'resize',
            'openData',
            'openSites',
            'openProducts',
            'openFields',
            'openAddProductDialog',
            'openPriceFieldFactors',
            'openRemoveProductDialog',
            'save',
            'addField',
            'addFields',
            '$onCreate',
            '$onInject'
        ],

        options: {
            categoryId: false,
            icon      : 'fa fa-sitemap'
        },

        initialize: function (options) {
            this.parent(options);

            this.$grids = {
                Fields  : null,
                Products: null
            };

            this.$injected     = false;
            this.$informations = {};

            this.$ContainerData              = null;
            this.$ContainerSites             = null;
            this.$ContainerProducts          = null;
            this.$ContainerFields            = null;
            this.$ContainerPriceFieldFactors = null;

            this.$data = {};

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self    = this,
                Content = this.getContent();

            Content.setStyles({
                height : 'calc(100% - 30px)',
                padding: 0
            });

            // buttons
            this.addButton({
                name     : 'save',
                textimage: 'fa fa-save',
                text     : QUILocale.get('quiqqer/system', 'save'),
                events   : {
                    onClick: this.save
                }
            });

            // category
            this.addCategory({
                name  : 'data',
                image : 'fa fa-file-o',
                text  : QUILocale.get(lg, 'category.panel.button.data'),
                events: {
                    onClick: this.openData
                }
            });

            this.addCategory({
                name  : 'products',
                image : 'fa fa-shopping-bag',
                text  : QUILocale.get(lg, 'category.panel.button.products'),
                events: {
                    onClick: this.openProducts
                }
            });

            this.addCategory({
                name  : 'sites',
                image : 'fa fa-file-text-o',
                text  : QUILocale.get(lg, 'category.panel.button.sites'),
                events: {
                    onClick: this.openSites
                }
            });

            this.addCategory({
                name  : 'fields',
                image : 'fa fa-files-o',
                text  : QUILocale.get(lg, 'category.panel.button.fields'),
                events: {
                    onClick: this.openFields
                }
            });

            this.addCategory({
                name  : 'priceFieldFactors',
                image : 'fa fa-money',
                text  : QUILocale.get(lg, 'category.panel.button.priceFieldFactors'),
                events: {
                    onClick: this.openPriceFieldFactors
                }
            });

            // html
            Content.set({
                html: Mustache.render(template, {
                    textData             : QUILocale.get('quiqqer/system', 'data'),
                    textId               : QUILocale.get('quiqqer/system', 'id'),
                    textTitle            : QUILocale.get('quiqqer/system', 'title'),
                    textDescription      : QUILocale.get('quiqqer/system', 'description'),
                    textParent           : QUILocale.get(lg, 'control.category.update.title.parent'),
                    textFields           : QUILocale.get(lg, 'control.category.update.title.fields'),
                    textSites            : QUILocale.get(lg, 'control.category.update.title.sites'),
                    textInformation      : QUILocale.get(lg, 'control.category.update.title.information'),
                    textProductCount     : QUILocale.get(lg, 'control.category.update.title.countProducts'),
                    textFieldCount       : QUILocale.get(lg, 'control.category.update.title.countFields'),
                    textCategoriesCount  : QUILocale.get(lg, 'control.category.update.title.countCategories'),
                    infoPriceFieldFactors: QUILocale.get(lg, 'control.category.update.textCategoriesCount'),
                    categoryId           : this.getAttribute('categoryId')
                })
            });

            this.$ContainerData              = Content.getElement('.category-data');
            this.$ContainerSites             = Content.getElement('.category-sites');
            this.$ContainerProducts          = Content.getElement('.category-products');
            this.$ContainerFields            = Content.getElement('.category-fields');
            this.$ContainerPriceFieldFactors = Content.getElement('.category-pricefieldfactors');

            var Id = Content.getElement('.field-id');

            var FieldContainer = new Element('div', {
                styles: {
                    width: '100%'
                }
            }).inject(this.$ContainerFields);

            this.$grids.Fields = new Grid(FieldContainer, {
                perPage    : 150,
                buttons    : [{
                    name     : 'add',
                    text     : QUILocale.get(lg, 'category.update.field.grid.button.add'),
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: function () {
                            require([
                                'package/quiqqer/products/bin/controls/fields/search/Window'
                            ], function (Win) {
                                new Win({
                                    title   : QUILocale.get(lg, 'category.update.window.addField.title'),
                                    multiple: true,
                                    events  : {
                                        onSubmit: function (Win, value) {
                                            self.addFields(value);
                                        }
                                    }
                                }).open();
                            });
                        }
                    }
                }, {
                    type: 'separator'
                }, {
                    name     : 'delete',
                    text     : QUILocale.get(lg, 'category.update.field.grid.button.delete'),
                    textimage: 'fa fa-trash',
                    disabled : true,
                    events   : {
                        onClick: function () {
                            self.openRemoveFieldDialog();
                        }
                    }
                }],
                columnModel: [{
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
                    dataType : 'text',
                    width    : 100
                }]
            });

            this.$grids.Fields.addEvents({
                onClick: function () {
                    var selected = this.$grids.Fields.getSelectedIndices(),
                        Delete   = this.$grids.Fields.getButtons().filter(function (Btn) {
                            return Btn.getAttribute('name') == 'delete';
                        })[0];

                    if (selected.length) {
                        Delete.enable();
                    } else {
                        Delete.disable();
                    }
                }.bind(this)
            });

            Id.set('html', this.getAttribute('categoryId'));
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            if (this.$injected) {
                return;
            }

            this.$injected = true;

            var self                = this,
                categoryId          = this.getAttribute('categoryId'),
                Content             = this.getContent(),
                TranslateTitles     = Content.getElement('.category-title'),
                TranslateCategories = Content.getElement('.category-description');

            this.refresh().then(function () {

                // translations
                this.$TitlesTranslation = new Translation({
                    'group'          : 'quiqqer/products',
                    'var'            : 'products.category.' + categoryId + '.title',
                    'package'        : 'quiqqer/products',
                    createIfNotExists: true
                }).inject(TranslateTitles);

                this.$CategoriesTranslation = new Translation({
                    'group'          : 'quiqqer/products',
                    'var'            : 'products.category.' + categoryId + '.description',
                    'package'        : 'quiqqer/products',
                    createIfNotExists: true
                }).inject(TranslateCategories);


                Content.getElement('.category-count-products').set('html', this.$informations.products);
                Content.getElement('.category-count-fields').set('html', this.$informations.fields);
                Content.getElement('.category-count-categories').set('html', this.$informations.categories);

                // parent
                require([
                    'package/quiqqer/products/bin/controls/categories/SelectItem'
                ], function (SelectItem) {

                    new SelectItem({
                        categoryId: this.$data.parent || 0,
                        removeable: false,
                        editable  : true,
                        events    : {
                            onChange: function (Itm, value) {
                                Itm.loading();
                                Categories.setParent(categoryId, value).then(function () {
                                    Itm.refresh();
                                });
                            }
                        }
                    }).inject(Content.getElement('.category-parent'));

                }.bind(this));

                // fields
                var field;
                var fieldGridData = [];

                for (var i = 0, len = this.$data.fields.length; i < len; i++) {
                    field = this.$data.fields[i];

                    fieldGridData.push({
                        id          : field.id,
                        title       : field.title || QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                        workingtitle: field.workingtitle || '',
                        fieldtype   : QUILocale.get(lg, 'fieldtype.' + field.type),
                        priority    : field.priority
                    });
                }

                this.$grids.Fields.setData({
                    data: fieldGridData
                });


                // products
                var ProductContainer = new Element('div', {
                    styles: {
                        height: '100%',
                        width : '100%'
                    }
                }).inject(this.$ContainerProducts);

                this.$grids.Products = new Grid(ProductContainer, {
                    perPage    : 150,
                    pagination : true,
                    buttons    : [{
                        text     : QUILocale.get(lg, 'category.panel.button.products.add'),
                        textimage: 'fa fa-plus',
                        events   : {
                            onClick: this.openAddProductDialog
                        }
                    }, {
                        type: 'separator'
                    }, {
                        name     : 'remove',
                        text     : QUILocale.get(lg, 'category.panel.button.products.remove'),
                        title    : QUILocale.get(lg, 'category.panel.button.products.remove.title'),
                        textimage: 'fa fa-chain-broken',
                        disabled : true,
                        events   : {
                            onClick: this.openRemoveProductDialog
                        }
                    }],
                    columnModel: [{
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
                        header   : QUILocale.get('quiqqer/system', 'description'),
                        dataIndex: 'description',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'products.product.panel.grid.nettoprice'),
                        dataIndex: 'price',
                        dataType : 'text',
                        width    : 100
                    }]
                });

                var RemoveButton = self.$grids.Products.getButtons().filter(function (Btn) {
                    return Btn.getAttribute('name') === 'remove';
                })[0];


                var refreshGrid = function () {
                    self.Loader.show();

                    return new Promise(function (resolve, reject) {
                        Categories.getProductList(categoryId, {
                            perPage: self.$grids.Products.options.perPage,
                            page   : self.$grids.Products.options.page
                        }).then(function (result) {
                            self.$grids.Products.setData(result);
                            RemoveButton.disable();
                            self.Loader.hide();
                        }).then(resolve, reject);
                    });
                };

                this.$grids.Products.addEvents({
                    refresh   : refreshGrid,
                    onDblClick: function () {
                        new ProductPanel({
                            productId: self.$grids.Products.getSelectedData()[0].id
                        }).inject(self.getParent());
                    },
                    onClick   : function () {
                        RemoveButton.enable();
                    }
                });

                // sites
                var refreshSiteGrid = function () {
                    return new Promise(function (resolve, reject) {
                        Categories.getSites(categoryId).then(function (result) {
                            self.$grids.Sites.setData({
                                data: result
                            });
                        }).then(resolve, reject);
                    });
                };

                var SitesContainer = new Element('div', {
                    styles: {
                        height: '100%',
                        width : '100%'
                    }
                }).inject(this.$ContainerSites);

                this.$grids.Sites = new Grid(SitesContainer, {
                    perPage    : 150,
                    columnModel: [{
                        header   : QUILocale.get('quiqqer/system', 'id'),
                        dataIndex: 'id',
                        dataType : 'number',
                        width    : 60
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'project'),
                        dataIndex: 'project',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'language'),
                        dataIndex: 'lang',
                        dataType : 'text',
                        width    : 100
                    }]
                });

                this.$grids.Sites.addEvents({
                    refresh   : refreshSiteGrid,
                    onDblClick: function () {
                        var data    = self.$grids.Sites.getSelectedData()[0],
                            project = data.project,
                            lang    = data.lang,
                            id      = data.id;

                        require(['utils/Panels'], function (Utils) {
                            Utils.openSitePanel(project, lang, id);
                        });
                    }
                });

                if ('priceFieldFactors' in self.$data.custom_data) {
                    self.$ContainerPriceFieldFactors.getElement('input[name="priceFieldFactors"]').value = JSON.encode(
                        self.$data.custom_data.priceFieldFactors
                    );
                }

                return QUI.parse(this.$ContainerPriceFieldFactors);
            }.bind(this)).then(this.openData).then(function () {
                // Grid controls
                var controls = QUI.Controls.getControlsInElement(
                    self.$grids.Fields.container
                );

                var switches = controls.filter(function (Control) {
                    return Control.getType() === 'qui/controls/buttons/Switch';
                });

                switches.each(function (Switch) {
                    Switch.resize();
                });

                self.resize();
            }).catch(function (err) {
                console.error(err);
                this.close();
            }.bind(this));
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        resize: function () {
            this.parent();

            return new Promise(function (resolve, reject) {
                var proms = [],
                    size  = this.getContent().getSize();

                if (this.$grids.Fields) {
                    proms.push(this.$grids.Fields.setWidth(size.x - 40));
                    proms.push(this.$grids.Fields.setHeight(size.y - 40));
                }

                if (this.$grids.Products) {
                    proms.push(this.$grids.Products.setWidth(size.x - 40));
                    proms.push(this.$grids.Products.setHeight(size.y - 40));
                }

                if (this.$grids.Sites) {
                    proms.push(this.$grids.Sites.setWidth(size.x - 40));
                    proms.push(this.$grids.Sites.setHeight(size.y - 40));
                }

                if (!proms.length) {
                    resolve();
                    return;
                }

                Promise.all(proms).then(resolve, reject);
            }.bind(this));
        },

        /**
         * refresh the data
         *
         * @return {Promise}
         */
        refresh: function () {
            var categoryId = this.getAttribute('categoryId');

            return new Promise(function (resolve, reject) {
                Categories.getChild(categoryId).then(function (data) {
                    this.$data = data;

                    this.setAttribute('title', QUILocale.get(lg, 'category.panel.title', {
                        title: data.title
                    }));

                    Categories.getInformation(categoryId).then(function (informations) {
                        this.$informations = informations;

                        if (this.$grids.Fields) {
                            var field;
                            var fieldGridData = [];

                            for (var i = 0, len = this.$data.fields.length; i < len; i++) {
                                field = this.$data.fields[i];

                                fieldGridData.push({
                                    id   : field.id,
                                    title: field.title || QUILocale.get(lg, 'products.field.' + field.id + '.title')
                                });
                            }

                            this.$grids.Fields.setData({
                                data: fieldGridData
                            });
                        }

                        // parent refresh for title
                        this.$refresh();

                        resolve(data);
                    }.bind(this));

                }.bind(this), function (err) {
                    console.error(err);
                    reject();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Show the category data
         *
         * @return {Promise}
         */
        openData: function () {
            this.getCategory('data').setActive();

            return this.$hideContainer().then(function () {
                return this.$showContainer(this.$ContainerData);
            }.bind(this));
        },

        /**
         * Show the category sites
         */
        openSites: function () {
            this.getCategory('sites').setActive();

            return this.$hideContainer().then(function () {
                this.$grids.Sites.refresh();
                return this.$showContainer(this.$ContainerSites);
            }.bind(this));
        },

        /**
         * Show the category sites
         *
         * @return {Promise}
         */
        openProducts: function () {
            var self = this;

            this.getCategory('products').setActive();

            return this.$hideContainer()
                .then(this.resize)
                .then(function () {
                    self.$grids.Products.refresh();
                    return self.$showContainer(self.$ContainerProducts);
                })
                .then(function () {
                    self.$grids.Products.resize();
                });
        },

        /**
         * Show the category fields
         *
         * @return {Promise}
         */
        openFields: function () {
            this.getCategory('fields').setActive();

            return this.$hideContainer()
                .then(this.resize)

                .then(function () {
                    this.$grids.Fields.refresh();
                    return this.$showContainer(this.$ContainerFields);
                }.bind(this))

                .then(function () {
                    this.$grids.Fields.resize();
                }.bind(this));
        },

        /**
         * Show the category price field factors
         *
         * @return {Promise}
         */
        openPriceFieldFactors: function () {
            this.getCategory('priceFieldFactors').setActive();

            return this.$hideContainer()
                .then(this.resize)

                .then(function () {
                    this.$grids.Fields.refresh();
                    return this.$showContainer(this.$ContainerPriceFieldFactors);
                }.bind(this))

                .then(function () {
                    this.$grids.Fields.resize();
                }.bind(this));
        },

        /**
         * opens the field removing dialog
         *
         * @return {Promise}
         */
        openRemoveFieldDialog: function () {
            var self = this;

            return new Promise(function (resolve) {
                new QUIConfirm({
                    icon       : 'fa fa-trash',
                    texticon   : 'fa fa-trash',
                    title      : QUILocale.get(lg, 'category.update.field.window.delete.title'),
                    text       : QUILocale.get(lg, 'category.update.field.window.delete.text'),
                    information: QUILocale.get(lg, 'category.update.field.window.delete.information'),
                    maxHeight  : 300,
                    maxWidth   : 450,
                    events     : {
                        onSubmit: function () {
                            self.removeFields(self.$grids.Fields.getSelectedIndices());
                            resolve();
                        }
                    }
                }).open();
            });
        },

        /**
         * Update all product fields with the category id fields
         *
         * @returns {Promise}
         */
        openRecursiveDialog: function () {
            var categoryId = this.getAttribute('categoryId');

            return new Promise(function (resolve) {
                new QUIConfirm({
                    icon       : 'fa fa-object-group',
                    texticon   : false,
                    title      : QUILocale.get(lg, 'category.panel.window.recursiveFields.title'),
                    information: QUILocale.get(lg, 'category.panel.window.recursiveFields.information'),
                    text       : QUILocale.get(lg, 'category.panel.window.recursiveFields.text', {
                        category: categoryId
                    }),

                    maxHeight: 400,
                    maxWidth : 600,
                    autoclose: false,
                    events   : {
                        onSubmit: function (Win) {
                            Win.Loader.setAttribute('closetime', 100000);
                            Win.Loader.show();
                            Categories.setFieldsToAllProducts(categoryId).then(function () {
                                Win.close();
                                resolve();
                            }).catch(function (Exception) {
                                Win.Loader.hide();
                                QUI.getMessageHandler().then(function (MH) {
                                    MH.addError(Exception.getMessage());
                                });
                            });
                        },
                        onCancel: function () {
                            resolve();
                        }
                    }
                }).open();
            });
        },

        /**
         * Show the container
         *
         * @param {HTMLDivElement} Container
         * @return {Promise}
         */
        $showContainer: function (Container) {
            return new Promise(function (resolve) {
                Container.setStyles({
                    display: 'inline',
                    opacity: 0
                });

                moofx(Container).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            }.bind(this));
        },

        /**
         * Hide the container
         *
         * @return {Promise}
         */
        $hideContainer: function () {
            return new Promise(function (resolve) {
                moofx([
                    this.$ContainerData,
                    this.$ContainerSites,
                    this.$ContainerProducts,
                    this.$ContainerFields,
                    this.$ContainerPriceFieldFactors
                ]).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 200,
                    callback: function () {
                        this.$ContainerData.setStyle('display', 'none');
                        this.$ContainerSites.setStyle('display', 'none');
                        this.$ContainerProducts.setStyle('display', 'none');
                        this.$ContainerFields.setStyle('display', 'none');
                        this.$ContainerPriceFieldFactors.setStyle('display', 'none');

                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * save the category
         *
         * @return {Promise}
         */
        save: function () {
            this.Loader.show();

            var self       = this,
                categoryId = this.getAttribute('categoryId'),
                data       = this.$grids.Fields.getData();

            // fields
            var i, len, field;
            var fields = [];

            for (i = 0, len = data.length; i < len; i++) {
                field = data[i];

                fields.push({
                    id: field.id
                });
            }

            return new Promise(function (resolve, reject) {
                Promise.all([
                    self.$TitlesTranslation.save(),
                    self.$CategoriesTranslation.save()
                ]).then(function () {
                    Categories.updateChild(categoryId, {
                        fields     : fields,
                        custom_data: {
                            priceFieldFactors: JSON.decode(self.$ContainerPriceFieldFactors.getElement(
                                'input[name="priceFieldFactors"]'
                            ).value)
                        }
                    }).then(function () {
                        return self.refresh();
                    }).then(function () {
                        self.Loader.hide();
                        resolve();
                    }, function () {
                        self.Loader.hide();
                        reject();
                    });
                });
            });
        },

        /**
         * Add a field to the category
         *
         * @param {Number} fieldId - Field-ID
         * @return {Promise}
         */
        addField: function (fieldId) {
            var self = this;

            return new Promise(function (resolve, reject) {
                Fields.getChild(fieldId).then(function () {
                    self.$grids.Fields.addRow({
                        id          : fieldId,
                        title       : QUILocale.get(lg, 'products.field.' + fieldId + '.title'),
                        publicStatus: new QUISwitch(),
                        searchStatus: new QUISwitch()
                    });

                    self.save().then(function () {
                        resolve();

                        self.openRecursiveDialog().catch(function (err) {
                            console.error(err);
                        });
                    }, reject);
                });
            });
        },

        /**
         * Add a field list to the category
         *
         * @param {Number} fieldIds - Field-IDs
         * @return {Promise}
         */
        addFields: function (fieldIds) {
            var self = this;

            return new Promise(function (resolve, reject) {
                var promises = [];

                for (var i = 0, len = fieldIds.length; i < len; i++) {
                    promises.push(
                        Fields.getChild(fieldIds[i])
                    );
                }

                Promise.all(promises).then(function () {
                    for (var i = 0, len = fieldIds.length; i < len; i++) {
                        self.$grids.Fields.addRow({
                            id          : fieldIds[i],
                            title       : QUILocale.get(lg, 'products.field.' + fieldIds[i] + '.title'),
                            publicStatus: new QUISwitch(),
                            searchStatus: new QUISwitch()
                        });
                    }

                    self.save().then(function () {
                        resolve();

                        self.openRecursiveDialog().catch(function (err) {
                            console.error(err);
                        });
                    }, reject);
                }).catch(reject);
            });
        },

        /**
         * Add products to the category
         *
         * @param {Array} productIds
         * @return {Promise}
         */
        addProducts: function (productIds) {
            this.Loader.show();

            return Categories.addProducts(
                this.getAttribute('categoryId'),
                productIds
            ).then(function () {
                if (this.getCategory('products').isActive()) {
                    this.$grids.Products.refresh();
                }

                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Remove products from the category
         *
         * @param {Array} productIds
         * @return {Promise}
         */
        removeProducts: function (productIds) {
            this.Loader.show();

            return Categories.removeProducts(
                this.getAttribute('categoryId'),
                productIds
            ).then(function () {
                if (this.getCategory('products').isActive()) {
                    this.$grids.Products.refresh();
                }

                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Remove a field from the category
         *
         * @param {Array} fields
         * @return {Promise}
         */
        removeFields: function (fields) {
            this.$grids.Fields.deleteRows(fields);

            return this.save().then(function () {
                this.openRecursiveDialog();
            }.bind(this));
        },

        /**
         * Opens the product search to add a product or multiple products to the category
         */
        openAddProductDialog: function () {
            new ProductSearchWindow({
                events: {
                    onSubmit: function (Win, selected) {
                        this.addProducts(selected);
                        Win.hide();
                    }.bind(this)
                }
            }).open();
        },

        /**
         * Opens the remove dialog for a product
         *
         * @return {Promise}
         */
        openRemoveProductDialog: function () {
            var self        = this;
            var productsIds = this.$grids.Products.getSelectedData().map(function (Entry) {
                return Entry.id;
            });

            if (!productsIds.length) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                new QUIConfirm({
                    icon       : 'fa fa-chain-broken',
                    texticon   : false,
                    title      : QUILocale.get(lg, 'category.panel.window.remove.title'),
                    information: QUILocale.get(lg, 'category.panel.window.remove.information'),
                    text       : QUILocale.get(lg, 'category.panel.window.remove.text', {
                        products: productsIds.join(', ')
                    }),
                    maxHeight  : 400,
                    maxWidth   : 600,
                    autoclose  : false,
                    ok_button  : {
                        textimage: 'fa fa-chain-broken',
                        text     : QUILocale.get('quiqqer/system', 'remove')
                    },
                    events     : {
                        onSubmit: function (Win) {
                            Win.Loader.show();
                            self.removeProducts(productsIds).then(function () {
                                Win.close();
                                resolve();
                            }).catch(function (Exception) {
                                Win.Loader.hide();
                                QUI.getMessageHandler().then(function (MH) {
                                    MH.addError(Exception.getMessage());
                                });
                            });
                        },
                        onCancel: function () {
                            resolve();
                        }
                    }
                }).open();
            });
        }
    });
});
