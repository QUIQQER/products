/**
 * Category panel
 * Edit / update the category
 *
 * @module package/quiqqer/products/bin/controls/categories/Category
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Switch
 * @require qui/controls/windows/Confirm
 * @require Locale
 * @require Mustache
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/Categories
 * @require package/quiqqer/products/bin/Fields
 * @require package/quiqqer/products/bin/controls/categories/Sitemap
 * @require package/quiqqer/translator/bin/controls/Update
 * @require text!package/quiqqer/products/bin/controls/categories/Category.html
 * @require css!package/quiqqer/products/bin/controls/categories/Category.css
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
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/categories/Category.html',
    'css!package/quiqqer/products/bin/controls/categories/Category.css'

], function (QUI, QUIPanel, QUIButton, QUISwitch, QUIConfirm, QUILocale, Mustache, Grid,
             Categories, Fields, CategorySitemap, Translation, template) {
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
            'save',
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

            this.$ContainerData     = null;
            this.$ContainerSites    = null;
            this.$ContainerProducts = null;

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
                name  : 'sites',
                image : 'fa fa-files-o',
                text  : QUILocale.get(lg, 'category.panel.button.sites'),
                events: {
                    onClick: this.openSites
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

            // html
            Content.set({
                html: Mustache.render(template, {
                    textData       : QUILocale.get('quiqqer/system', 'data'),
                    textId         : QUILocale.get('quiqqer/system', 'id'),
                    textTitle      : QUILocale.get('quiqqer/system', 'title'),
                    textDescription: QUILocale.get('quiqqer/system', 'description'),
                    textFields     : QUILocale.get(lg, 'control.category.update.title.fields'),
                    textSites      : QUILocale.get(lg, 'control.category.update.title.sites')
                })
            });

            this.$ContainerData     = Content.getElement('.category-data');
            this.$ContainerSites    = Content.getElement('.category-sites');
            this.$ContainerProducts = Content.getElement('.category-products');

            var Id = Content.getElement('.field-id');

            var FieldContainer = new Element('div', {
                styles: {
                    width: '100%'
                }
            }).inject(
                Content.getElement('.category-update-fields-table')
            );

            this.$grids.Fields = new Grid(FieldContainer, {
                height     : 300,
                buttons    : [{
                    name     : 'add',
                    text     : QUILocale.get(lg, 'category.update.field.grid.button.add'),
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: function () {
                            require([
                                'package/quiqqer/products/bin/controls/fields/Window'
                            ], function (Win) {
                                new Win({
                                    title : QUILocale.get(lg, 'category.update.window.addField.title'),
                                    events: {
                                        onSubmit: function (Win, value) {
                                            self.addField(value);
                                        }
                                    }
                                }).open();
                            });
                        }
                    }
                }, {
                    type: 'seperator'
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
                    header   : QUILocale.get(lg, 'category.update.field.grid.publicStatus'),
                    dataIndex: 'publicStatus',
                    dataType : 'QUI',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'category.update.field.grid.searchStatus'),
                    dataIndex: 'searchStatus',
                    dataType : 'QUI',
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
            var self                = this,
                categoryId          = this.getAttribute('categoryId'),
                Content             = this.getContent(),
                TranslateTitles     = Content.getElement('.category-title'),
                TranslateCategories = Content.getElement('.category-description');

            this.refresh().then(function () {

                // translations
                this.$TitlesTranslation = new Translation({
                    'group': 'quiqqer/products',
                    'var'  : 'products.category.' + categoryId + '.title'
                }).inject(TranslateTitles);

                this.$CategoriesTranslation = new Translation({
                    'group': 'quiqqer/products',
                    'var'  : 'products.category.' + categoryId + '.description'
                }).inject(TranslateCategories);


                // fields
                var field;
                var fieldGridData = [];

                for (var i = 0, len = this.$data.fields.length; i < len; i++) {
                    field = this.$data.fields[i];

                    fieldGridData.push({
                        id          : field.id,
                        title       : QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                        publicStatus: new QUISwitch({
                            status: field.publicStatus
                        }),
                        searchStatus: new QUISwitch({
                            status: field.searchStatus
                        })
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
                    pagination : true,
                    buttons    : [{
                        text     : QUILocale.get(lg, 'category.panel.button.products.add'),
                        textimage: 'fa fa-plus'
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


                var refreshGrid = function () {
                    return new Promise(function (resolve, reject) {
                        Categories.getProductList(categoryId, {
                            perPage: self.$grids.Products.options.perPage,
                            page   : self.$grids.Products.options.page
                        }).then(function (result) {
                            self.$grids.Products.setData(result);
                        }).then(resolve, reject);
                    });
                };

                this.$grids.Products.addEvents({
                    refresh: refreshGrid
                });


            }.bind(this)).then(this.openData).then(function () {
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
            });
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
                    proms.push(
                        this.$grids.Fields.setWidth(size.x - 80)
                    );
                }

                if (this.$grids.Products) {
                    proms.push(this.$grids.Products.setWidth(size.x - 40));
                    proms.push(this.$grids.Products.setHeight(size.y - 40));
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

            return new Promise(function (resolve) {
                Categories.getChild(categoryId).then(function (data) {
                    this.$data = data;

                    this.setAttribute('title', QUILocale.get(lg, 'category.panel.title', {
                        title: data.title
                    }));

                    resolve(data);

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
                return this.$showContainer(this.$ContainerSites);
            }.bind(this));
        },

        /**
         * Show the category sites
         */
        openProducts: function () {
            this.getCategory('products').setActive();

            return this.$hideContainer()
                .then(this.resize)
                .then(function () {
                    this.$grids.Products.refresh();
                    return this.$showContainer(this.$ContainerProducts);
                }.bind(this));
        },

        /**
         * opens the field removing dialog
         */
        openRemoveFieldDialog: function () {
            var self = this;

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
                    }
                }
            }).open();
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

                    text: QUILocale.get(lg, 'category.panel.window.recursiveFields.text', {
                        category: categoryId
                    }),

                    maxHeight: 400,
                    maxWidth : 600,
                    autoclose: false,
                    events   : {
                        onSubmit: function (Win) {
                            Win.Loader.show();
                            Categories.setFieldsToAllProducts(categoryId).then(function () {
                                Win.close();
                                resolve();
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
                    this.$ContainerProducts
                ]).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 200,
                    callback: function () {
                        this.$ContainerData.setStyle('display', 'none');
                        this.$ContainerSites.setStyle('display', 'none');
                        this.$ContainerProducts.setStyle('display', 'none');

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
                    id          : field.id,
                    publicStatus: field.publicStatus.getStatus(),
                    searchStatus: field.searchStatus.getStatus()
                });
            }

            return new Promise(function (resolve, reject) {
                Promise.all([
                    self.$TitlesTranslation.save(),
                    self.$CategoriesTranslation.save()
                ]).then(function () {

                    Categories.updateChild(categoryId, {
                        fields: fields
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
                        self.openRecursiveDialog();
                    }, reject);
                });
            });
        },

        /**
         * Remove a field from the category
         *
         * @param {Array} fields
         * @return {Promise}
         */
        removeFields: function (fields) {
            this.$grids.Fields.deleteRows(fields);
            console.log(this.$grids.Fields.getData());
            return this.save().then(function () {
                this.openRecursiveDialog();
            }.bind(this));
        }
    });
});
