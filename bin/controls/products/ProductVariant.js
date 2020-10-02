/**
 * Variant Panel
 * this panel shows additional variant stuff
 *
 * @module package/quiqqer/products/bin/controls/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/ProductVariant', [

    'qui/QUI',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/controls/products/Product',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/Products',
    'qui/controls/buttons/Select',
    'qui/controls/toolbar/Bar',
    'qui/controls/toolbar/Tab',
    'qui/controls/contextmenu/Menu',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator',
    'qui/controls/buttons/Switch',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/ProductVariant.html',

    'css!package/quiqqer/products/bin/controls/products/ProductVariant.css'

], function (QUI, QUIButton, ProductPanel, Product, Fields, Products,
             QUISelect, QUIBar, QUITab, QUIContextMenu, QUIContextMenuItem, QUIContextMenuSeparator, QUISwitch,
             Grid, QUIAjax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: ProductPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/ProductVariant',

        Binds: [
            '$onInject',
            'openVariantTab',
            'openVariantAttributeSettings',
            'openVariantGenerating',
            'addVariant',
            '$onActivationStatusChange',
            'deleteVariantsDialog',
            '$activateVariants',
            '$deactivateVariants',
            '$deleteVariants',
            '$changeVariant',
            '$toggleDefaultVariant',
            '$changeOwnFolderStatus'
        ],

        options: {
            productId: false,
            sortOn   : false,
            sortBy   : false,
            perPage  : 150,
            page     : false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title'),
                icon : 'fa fa-shopping-bag',
                '#id': "productId" in options ? options.productId : false
            });

            var self = this;

            this.parent(options);

            this.$loaded = false;
            this.$Grid   = null;

            this.$Menu = new QUIContextMenu({
                events: {
                    onBlur: function () {
                        (function () {
                            self.$Menu.hide();
                        }).delay(200);
                    }
                }
            });

            this.$editableFields = {};
            this.$CurrentVariant = null;
            this.$VariantTabBar  = null;

            // panel extra buttons
            this.$VariantFields     = null;
            this.$BackToVariantList = null;

            // panel buttons
            this.$SaveButton      = null;
            this.$CopyButton      = null;
            this.$StatusButton    = null;
            this.$ActionSeparator = null;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            this.$SaveButton      = this.getButtons('update');
            this.$StatusButton    = this.getButtons('status');
            this.$ActionSeparator = this.getButtons('actionSeparator');
            this.$CopyButton      = this.getButtons('copy');

            this.$Elm.addClass('panel-product-variant');

            this.parent().then(function () {
                return self.$checkProductParent();
            }).then(function () {
                self.addCategory({
                    name  : 'variants',
                    text  : QUILocale.get(lg, 'panel.variants.category.title'),
                    icon  : 'fa fa-info',
                    events: {
                        onClick: function () {
                            self.Loader.show();
                            self.openVariants().then(function () {
                                if (self.$loaded) {
                                    self.Loader.hide();
                                }
                            });
                        }
                    }
                });

                self.getCategoryBar().moveChildToPos(
                    self.getCategory('variants'),
                    2
                );

                var categories = self.getCategoryBar().getChildren();

                var resetMinimize = function (Category) {
                    if (Category.getAttribute('name') === 'variants') {
                        return;
                    }

                    self.maximizeCategory();

                    self.$SaveButton.show();
                    self.$StatusButton.show();
                    self.$CopyButton.show();
                    self.$ActionSeparator.show();
                    self.$CopyButton.show();
                    self.$CloseButton.show();

                    self.$BackToVariantList.hide();
                    self.$VariantFields.hide();
                    self.$CurrentVariant = null;

                    self.getElm().removeClass('quiqqer-products-panel-show-variant');
                    self.refresh();
                };

                for (var i = 0, len = categories.length; i < len; i++) {
                    categories[i].addEvent('onClick', resetMinimize);
                }

                self.addButton({
                    name  : 'variantFields',
                    title : QUILocale.get(lg, 'panel.variants.editable.button.title'),
                    icon  : 'fa fa-exchange',
                    events: {
                        click: self.openVariantAttributeSettings
                    },
                    styles: {
                        'float': 'right'
                    }
                });

                self.$VariantFields = self.getButtons('variantFields');
                self.$VariantFields.hide();

                self.$BackToVariantList = new QUIButton({
                    name  : 'BackToVariantFields',
                    title : QUILocale.get(lg, 'panel.variants.backToList.button.title'),
                    icon  : 'fa fa-list',
                    events: {
                        click: function () {
                            self.getCategory('variants').setNormal();
                            self.openVariants();
                        }
                    },
                    styles: {
                        'float': 'right'
                    }
                }).inject(self.getHeader());

                self.$BackToVariantList.hide();

                if (self.$CurrentVariant) {
                    return self.openVariants().then(function () {
                        return self.selectVariant(self.$CurrentVariant.getId());
                    });
                }
            }).then(function () {
                self.$loaded = true;
                self.Loader.hide();
            });
        },

        /**
         * Refresh the panel
         * - if current variant exists, refresh the current variant button status
         *
         * @return {Promise}
         */
        refresh: function () {
            // parent product handling
            if (!this.$CurrentVariant) {
                return this.parent();
            }

            this.$refreshStatusButton();
        },

        /**
         *
         * @return {Promise}
         */
        $checkProductParent: function () {
            var self      = this,
                productId = parseInt(this.getAttribute('productId'));

            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getParent', function (parentId) {
                    if (parentId === false) {
                        reject('No variant');
                        // @todo close and message
                        return;
                    }

                    if (parentId === productId) {
                        self.$CurrentVariant = null;
                        resolve();
                        return;
                    }

                    self.$CurrentVariant = self.$Product;

                    self.$Product = new Product({
                        id: parentId
                    });

                    resolve();
                }, {
                    'package': 'quiqqer/products',
                    productId: productId
                });
            });
        },

        /**
         * Update the product or the variant
         */
        update: function () {
            // parent product handling
            if (!this.$CurrentVariant) {
                return this.parent();
            }

            // variant handling
            this.Loader.show();

            var self        = this;
            var VariantBody = this.getBody().getElement('.variant-body');

            return this.$unloadCategory(VariantBody, self.$CurrentVariant).then(function () {
                return Promise.all([
                    self.$CurrentVariant.getCategories(),
                    self.$CurrentVariant.getCategory(),
                    self.$CurrentVariant.getFields(),
                    self.$CurrentVariant.getEditableFields()
                ]);
            }).then(function (result) {
                var categories = result[0];
                var category   = result[1];
                var fieldsList = result[2];
                var editable   = result[3];

                var editableFields = editable.editable;

                // parse fields to ajax field array
                var i, len, entry;
                var fields = {};

                for (i = 0, len = fieldsList.length; i < len; i++) {
                    entry = fieldsList[i];

                    if (editableFields.indexOf(entry.id) === -1) {
                        continue;
                    }

                    fields['field-' + entry.id] = entry.value;
                }

                return Products.updateChild(
                    self.$CurrentVariant.getId(),
                    categories,
                    category,
                    fields
                );
            }).then(function () {
                if (self.$loaded) {
                    self.Loader.hide();
                }
            }).catch(function () {
                self.Loader.hide();
            });
        },

        /**
         * event reaction to the variant status change
         */
        $onActivationStatusChange: function () {
            // parent product handling
            if (!this.$CurrentVariant) {
                return this.parent();
            }

            // variant handling
            var self   = this,
                Button = this.getButtons('status');

            Button.disable();

            var Prom = Promise.resolve();

            if (!Button.getStatus()) {
                Prom = this.$CurrentVariant.deactivate();
            }

            return Prom.then(function () {
                return self.update();
            }).then(function () {
                if (Button.getStatus()) {
                    return self.$CurrentVariant.activate();
                }
            }).then(this.refresh).catch(this.refresh);
        },

        //region variant management

        /**
         * Open variants
         *
         * @return {Promise}
         */
        openVariants: function () {
            if (this.getCategory('variants').isActive()) {
                return Promise.resolve();
            }

            this.Loader.show();

            var self = this,
                Body = self.getBody();

            this.$VariantFields.show();
            this.$BackToVariantList.hide();

            this.$SaveButton.hide();
            this.$StatusButton.hide();
            this.$ActionSeparator.hide();
            this.$CopyButton.hide();
            this.$CloseButton.show();


            return Promise.all([
                this.$hideCategories(),
                this.refreshProductEditableFields(),
                this.$Product.getVariantFields()
            ]).then(function (result) {
                var VariantSheet = Body.getElement('.variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }

                VariantSheet.set('html', '');

                // grid render
                var LC = new Element(
                    'div.variant-list-variantListContainer'
                ).inject(VariantSheet);

                var Container = new Element('div').inject(LC);


                // grid options
                var columns = [{
                    header   : QUILocale.get(lg, 'products.product.panel.grid.defaultStatus'),
                    dataIndex: 'defaultVariant',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'status'),
                    dataIndex: 'status',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 50
                }, {
                    header   : QUILocale.get(lg, 'productNo'),
                    dataIndex: 'productNo',
                    dataType : 'text',
                    width    : 100,
                    sortable : false
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200,
                    sortable : false
                }, {
                    header   : QUILocale.get(lg, 'products.product.panel.grid.nettoprice'),
                    dataIndex: 'price_netto_display',
                    dataType : 'text',
                    width    : 100,
                    sortable : false,
                    className: 'grid-align-right'
                }];

                var variantFields = result[2];

                for (var i = 0, len = variantFields.length; i < len; i++) {
                    columns.push({
                        header   : variantFields[i].title,
                        dataIndex: 'field-' + variantFields[i].id,
                        dataType : 'text',
                        width    : 150,
                        sortable : false
                    });
                }

                // end colums
                columns = columns.concat([{
                    header   : QUILocale.get('quiqqer/system', 'editdate'),
                    dataIndex: 'e_date',
                    dataType : 'text',
                    width    : 160
                }, {
                    header   : QUILocale.get('quiqqer/system', 'createdate'),
                    dataIndex: 'c_date',
                    dataType : 'text',
                    width    : 160
                }, {
                    header   : QUILocale.get(lg, 'priority'),
                    dataIndex: 'priority',
                    dataType : 'number',
                    width    : 60,
                    sortable : false
                }]);

                self.$Grid = new Grid(Container, {
                    pagination       : true,
                    multipleSelection: true,
                    width            : VariantSheet.getSize().x - 40,
                    height           : VariantSheet.getSize().y - 40,
                    perPage          : self.getAttribute('perPage'),
                    page             : self.getAttribute('page'),
                    sortOn           : self.getAttribute('sortOn'),
                    serverSort       : true,
                    buttons          : [{
                        textimage: 'fa fa-plus',
                        text     : QUILocale.get(lg, 'panel.variants.button.create'),
                        events   : {
                            click: self.addVariant
                        }
                    }, {
                        textimage: 'fa fa-magic',
                        text     : QUILocale.get(lg, 'panel.variants.button.generate'),
                        styles   : {
                            'float': 'right'
                        },
                        events   : {
                            click: self.openVariantGenerating
                        }
                    }],
                    columnModel      : columns
                });

                self.$Grid.addEvents({
                    onDblClick: function () {
                        self.selectVariant(
                            self.$Grid.getSelectedData()[0].id
                        );
                    },

                    onRefresh: function () {
                        self.refreshVariantGrid();
                    },

                    onContextMenu: function (event) {
                        if (!self.$Grid.getSelectedIndices().length) {
                            return;
                        }

                        self.$Menu.clearChildren();

                        // default status toggle
                        var text = QUILocale.get(lg, 'panel.variants.set.default.variant'),
                            icon = 'fa fa-check-circle-o';

                        var rowData        = self.$Grid.getDataByRow(event.row),
                            DefaultVariant = rowData.defaultVariant;

                        if (DefaultVariant.hasClass('fa')) {
                            text = QUILocale.get(lg, 'panel.variants.unset.default.variant');
                            icon = 'fa fa-circle-o';
                        }

                        self.$Menu.setTitle('#' + rowData.id + ' - ' + rowData.productNo);

                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text     : text,
                                icon     : icon,
                                cellEvent: event,
                                events   : {
                                    onClick: self.$toggleDefaultVariant
                                }
                            })
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuSeparator()
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text  : QUILocale.get(lg, 'panel.variants.activate.variants'),
                                icon  : 'fa fa-check',
                                events: {
                                    onClick: self.$activateVariants
                                }
                            })
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text  : QUILocale.get(lg, 'panel.variants.deactivate.variants'),
                                icon  : 'fa fa-close',
                                events: {
                                    onClick: self.$deactivateVariants
                                }
                            })
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuSeparator()
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text  : QUILocale.get(lg, 'panel.variants.delete.variants'),
                                icon  : 'fa fa-trash',
                                events: {
                                    onClick: self.deleteVariantsDialog
                                }
                            })
                        );

                        self.$Menu.inject(document.body);
                        self.$Menu.setPosition(
                            event.event.page.x,
                            event.event.page.y
                        );
                        self.$Menu.show();
                        self.$Menu.focus();
                    }
                });

                return self.refreshVariantGrid();
            }).then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('.variants-sheet');

                return self.$showCategory(VariantSheet);
            }).then(function () {
                self.getCategory('variants').setActive();

                if (self.$loaded) {
                    self.Loader.hide();
                }

                return self.$Grid.setHeight(
                    Body.getElement('.variants-sheet').getSize().y - 40
                );
            });
        },

        /**
         * Refresh the variant grid
         *
         * @return {Promise}
         */
        refreshVariantGrid: function () {
            if (this.$Grid === null) {
                return Promise.resolve();
            }

            this.Loader.show();

            var self    = this,
                options = this.$Grid.options,
                sortOn  = options.sortOn;

            if (sortOn === 'status') {
                sortOn = 'active';
            }

            return Promise.all([
                this.$Product.getVariants({
                    perPage: options.perPage,
                    page   : options.page,
                    sortOn : sortOn,
                    sortBy : options.sortBy
                }),
                this.$Product.getVariantFields()
            ]).then(function (result) {
                var needles = [
                    'id', 'title', 'e_date', 'c_date', 'priority',
                    'url', 'price_netto_display'
                ];

                var fields = {
                    'productNo'  : 3,
                    'price_netto': 1,
                    'priority'   : 18
                };

                var variants      = result[0].data;
                var variantFields = result[1];

                var i, n, len, nLen, entry, variant, needle, field, fieldId;
                var data = [];

                var filterField = function (field) {
                    return field.id === this;
                };

                // add variant fields to field object
                for (i = 0, len = variantFields.length; i < len; i++) {
                    fields['field-' + variantFields[i].id] = variantFields[i].id;
                }

                // build grid data
                for (i = 0, len = variants.length; i < len; i++) {
                    entry   = {};
                    variant = variants[i];

                    // status
                    if (variant.active) {
                        entry.status = new Element('span', {'class': 'fa fa-check'});
                    } else {
                        entry.status = new Element('span', {'class': 'fa fa-close'});
                    }

                    if (typeof variant.defaultVariant !== 'undefined' && variant.defaultVariant) {
                        entry.defaultVariant = new Element('span', {'class': 'fa fa-check-circle-o'});
                    } else {
                        entry.defaultVariant = new Element('span', {
                            html: '&nbsp;'
                        });
                    }

                    // attributes + fields
                    for (n = 0, nLen = needles.length; n < nLen; n++) {
                        needle = needles[n];

                        if (typeof variant[needle] === 'undefined' || !variant[needle]) {
                            entry[needle] = '-';
                        } else {
                            entry[needle] = variant[needle];
                        }
                    }

                    for (needle in fields) {
                        if (!fields.hasOwnProperty(needle)) {
                            continue;
                        }

                        fieldId = fields[needle];
                        field   = variant.fields.filter(filterField.bind(fieldId));

                        if (!field.length) {
                            entry[needle] = '-';
                        } else {
                            entry[needle] = field[0].value;
                        }
                    }

                    data.push(entry);
                }

                self.$Grid.setData({
                    data : data,
                    total: result[0].total,
                    page : result[0].page
                });

                if (self.$loaded) {
                    self.Loader.hide();
                }
            });
        },

        /**
         * Refresh the editable field list of the parent product
         *
         * @return {Promise}
         */
        refreshProductEditableFields: function () {
            var self = this;

            return this.$Product.getEditableFields().then(function (result) {
                // parse fields
                var of  = result.editable,
                    len = of.length;

                self.$editableFields = {};

                for (var i = 0; i < len; i++) {
                    self.$editableFields[of[i]] = true;
                }
            });
        },

        /**
         * Opens the variant attribute inheritance setting window for this product
         *
         * @return {Promise}
         */
        openVariantAttributeSettings: function () {
            var self = this;

            return new Promise(function (resolve) {
                require([
                    'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldListWindow'
                ], function (Window) {
                    new Window({
                        productId: self.getAttribute('productId'),
                        events   : {
                            onSave: function () {
                                if (!self.$CurrentVariant) {
                                    return;
                                }

                                // refresh current variant
                                var variantId = self.$CurrentVariant.getId();

                                self.Loader.show();
                                self.$CurrentVariant = null;

                                self.refreshProductEditableFields().then(function () {
                                    return self.selectVariant(variantId);
                                }).then(function () {
                                    self.Loader.hide();
                                });
                            }
                        }
                    }).open();

                    resolve();
                });
            });
        },

        /**
         * Opens / Select a wanted variant
         *
         * @param {Number} variantId - Variant ID
         * @return {Promise}
         */
        selectVariant: function (variantId) {
            var self = this;
            var Body = self.getBody();

            this.$BackToVariantList.show();
            this.$CloseButton.hide();

            this.$CurrentVariant = new Product({
                id: variantId
            });

            return self.$hideCategories().then(function () {
                if (!self.$CurrentVariant.isLoaded()) {
                    return self.$CurrentVariant.refresh();
                }
            }).then(function () {
                var VariantSheet = Body.getElement('.variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }

                self.minimizeCategory();
                self.getElm().addClass('quiqqer-products-panel-show-variant');

                VariantSheet.set('html', Mustache.render(template));
            }).then(function () {
                return self.$Product.getVariants();
            }).then(function (result) {
                var variants = result.data;

                var VariantSheet = Body.getElement('.variants-sheet');
                var VariantList  = Body.getElement('.variant-list');
                var VariantTabs  = Body.getElement('.variants-tabs');


                var VariantSelect = new QUISelect({
                    placeholder: QUILocale.get(lg, 'panel.variants.switch'),
                    showIcons  : false,
                    styles     : {
                        width: '100%'
                    },
                    events     : {
                        onChange: function (value) {
                            if (value === false) {
                                return;
                            }

                            self.$changeVariant(value);
                        }
                    }
                }).inject(VariantList);

                var i, len, name, title, fieldId, variant, Category;
                var vId, vTitle, vProductNo;

                var productNumberFilter = function (field) {
                    return field.id === Fields.FIELD_PRODUCT_NO;
                };

                for (i = 0, len = variants.length; i < len; i++) {
                    variant = variants[i];

                    vId        = variant.id;
                    vTitle     = variant.title;
                    vProductNo = variant.fields.filter(productNumberFilter);

                    title = QUILocale.get(lg, 'panel.variants.switchTo') + ' <b>';
                    title = title + vId;
                    title = title + ' - ' + vTitle + '</b>';

                    if (vProductNo && vProductNo.length && vProductNo[0].value) {
                        title = title + ' - ' + vProductNo[0].value;
                    }

                    VariantSelect.appendChild(title, variant.id);
                }

                if (typeof variantId === 'undefined') {
                    variantId = VariantSelect.firstChild().getAttribute('value');
                }

                VariantSelect.setValue(variantId);


                // tabs
                if (self.$VariantTabBar !== null) {
                    self.$VariantTabBar.destroy();
                }

                self.$VariantTabBar = new QUIBar({
                    width: Body.getSize().x
                }).inject(VariantTabs);

                // workaround
                self.$VariantTabBar.getElm().getElement('.qui-toolbar-tabs').setStyle('display', 'flex');

                var categories = self.getCategoryBar().getChildren();

                for (i = 0, len = categories.length; i < len; i++) {
                    Category = categories[i];
                    name     = Category.getAttribute('name');
                    fieldId  = Category.getAttribute('fieldId');

                    if (name === 'variants') {
                        continue;
                    }

                    if (name === 'information') {
                        continue;
                    }

                    if (name === 'attributelist') {
                        continue;
                    }

                    if (Category.getAttribute('text') === '') {
                        continue;
                    }

                    // only if field is editable
                    if (fieldId && typeof self.$editableFields[fieldId] === 'undefined' ||
                        (fieldId && typeof self.$editableFields[fieldId] !== 'undefined' && !self.$editableFields[fieldId])) {
                        continue;
                    }

                    self.$VariantTabBar.appendChild(
                        new QUITab({
                            name   : name,
                            fieldId: fieldId,
                            text   : Category.getAttribute('text'),
                            events : {
                                onClick: self.openVariantTab
                            }
                        })
                    );
                }

                self.$VariantTabBar.resize();
                self.$VariantTabBar.firstChild().click();

                return self.$showCategory(VariantSheet);
            }).then(function () {
                return self.$refreshStatusButton();
            }).then(function () {
                self.$SaveButton.setAttribute('text', QUILocale.get(lg, 'panel.variants.save'));
                self.$SaveButton.show();

                self.$ActionSeparator.show();

                self.$StatusButton.show();
                self.$StatusButton.enable();
                self.$StatusButton.resize();
            });
        },

        /**
         * on variant change
         *
         * @param variantId
         * @return {Promise}
         */
        $changeVariant: function (variantId) {
            if (this.$VariantTabBar === null) {
                return Promise.resolve();
            }

            var self = this;

            var Active = this.$VariantTabBar.getChildren().filter(function (Tab) {
                return Tab.isActive();
            })[0];

            this.$executeUnloadForm = false;

            this.$CurrentVariant = new Product({
                id: variantId
            });

            return this.$refreshStatusButton().then(function () {
                return self.openVariantTab(Active);
            }).then(function () {
                self.$executeUnloadForm = true;
            });
        },

        /**
         * Refresh status button
         *
         * @return {Promise}
         */
        $refreshStatusButton: function () {
            var self = this;

            return this.$CurrentVariant.isActive().then(function (isActive) {
                self.$StatusButton.enable();
                self.$StatusButton.resize();

                if (isActive) {
                    self.$StatusButton.setSilentOn();
                    self.$StatusButton.setAttribute('text', QUILocale.get(lg, 'panel.variants.activated'));
                } else {
                    self.$StatusButton.setSilentOff();
                    self.$StatusButton.setAttribute('text', QUILocale.get(lg, 'panel.variants.deactivated'));
                }
            });
        },

        /**
         * Deactivate the selected variants
         */
        $deactivateVariants: function () {
            this.Loader.show();

            var self     = this;
            var selected = this.$Grid.getSelectedData().map(function (entry) {
                return entry.id;
            });

            QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_deactivate', function () {
                self.refreshVariantGrid();
                self.Loader.hide();
            }, {
                'package' : 'quiqqer/products',
                variantIds: JSON.encode(selected),
                onError   : function () {
                    self.refreshVariantGrid();
                    self.Loader.hide();
                }
            });
        },

        /**
         * activate the selected variants
         */
        $activateVariants: function () {
            this.Loader.show();

            var self     = this;
            var selected = this.$Grid.getSelectedData().map(function (entry) {
                return entry.id;
            });

            QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_activate', function () {
                self.refreshVariantGrid();
                self.Loader.hide();
            }, {
                'package' : 'quiqqer/products',
                variantIds: JSON.encode(selected),
                onError   : function () {
                    self.refreshVariantGrid();
                    self.Loader.hide();
                }
            });
        },

        /**
         * Opens the delete dilaog
         */
        deleteVariantsDialog: function () {
            var self     = this;
            var selected = this.$Grid.getSelectedData().map(function (entry) {
                return '<li>' + entry.id + '</li>';
            });

            require(['qui/controls/windows/Confirm'], function (QUIConfirm) {
                var variants = '<ul>' + selected.join('') + '</ul>';

                new QUIConfirm({
                    icon       : 'fa fa-trash',
                    texticon   : 'fa fa-trash',
                    title      : QUILocale.get(lg, 'window.variant.delete'),
                    text       : QUILocale.get(lg, 'window.variant.text'),
                    information: QUILocale.get(lg, 'window.variant.information', {
                        variants: variants
                    }),
                    maxHeight  : 400,
                    maxWidth   : 600,
                    events     : {
                        onSubmit: self.$deleteVariants
                    },
                    ok_button  : {
                        text     : QUILocale.get('quiqqer/system', 'delete'),
                        textimage: 'fa fa-trash'
                    }
                }).open();
            });
        },

        /**
         * delete the selected variants
         */
        $deleteVariants: function () {
            this.Loader.show();

            var self     = this;
            var selected = this.$Grid.getSelectedData().map(function (entry) {
                return entry.id;
            });

            QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_delete', function () {
                self.refreshVariantGrid();
            }, {
                'package' : 'quiqqer/products',
                variantIds: JSON.encode(selected),
                onError   : function () {
                    self.refreshVariantGrid();
                    self.Loader.hide();
                }
            });
        },

        /**
         * event: context menu click -> toggle default variant
         *
         * @param ContextItem
         */
        $toggleDefaultVariant: function (ContextItem) {
            var self      = this,
                cellEvent = ContextItem.getAttribute('cellEvent'),
                row       = cellEvent.row,

                variantId = this.$Grid.getDataByRow(row).id,
                Status    = this.$Grid.getDataByRow(row).defaultVariant;

            this.Loader.show();

            // toggle
            if (Status.hasClass('fa')) {
                variantId = null;
            }

            return this.$Product.setDefaultVariantId(variantId).then(function () {
                return self.refreshVariantGrid();
            });
        },

        //endregion

        //region variant generating

        /**
         * Opens the add variant dialog
         */
        addVariant: function () {
            var self = this;

            require([
                'package/quiqqer/products/bin/controls/products/variants/AddVariantWindow'
            ], function (Window) {
                new Window({
                    productId: self.getAttribute('productId'),
                    events   : {
                        onVariantCreation: function (variantId) {
                            //self.refreshVariantGrid();
                            self.selectVariant(variantId);
                        }
                    }
                }).open();
            });
        },

        /**
         * opens the variant generating window
         */
        openVariantGenerating: function () {
            var self = this;

            require([
                'package/quiqqer/products/bin/controls/products/variants/GenerateVariantsWindow'
            ], function (Window) {
                new Window({
                    productId: self.getAttribute('productId'),
                    events   : {
                        onVariantCreation: function () {
                            self.$Grid.options.page = 1;
                            self.refreshVariantGrid();
                        }
                    }
                }).open();
            });
        },

        //endregion

        //region variant tab handling

        /**
         * Opens the tab
         *
         * @param Tab
         */
        openVariantTab: function (Tab) {
            this.Loader.show();

            var name = Tab.getAttribute('name');
            var done = function () {
                if (this.$loaded) {
                    this.Loader.hide();
                }
            }.bind(this);

            if (name === 'data') {
                return this.$openVariantData().then(done);
            }

            if (name === 'prices') {
                return this.$openVariantPrices().then(done);
            }

            if (name === 'images') {
                return this.$openVariantFolderViewer(['image']).then(done);
            }

            if (name === 'files') {
                return this.$openVariantFolderViewer(['file']).then(done);
            }

            var self    = this,
                fieldId = Tab.getAttribute('fieldId'),
                Product = this.$CurrentVariant;

            if (!fieldId) {
                console.log('missing');
                console.log(name);

                return Promise.resolve().then(done);
            }

            return this.$hideTabContent().then(function (Content) {
                return self.$renderField(Content, Product, fieldId);
            }).then(function () {
                return self.$showTabContent();
            }).then(done);
        },

        /**
         * opens the data
         *
         * @return {Promise}
         */
        $openVariantData: function () {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                return self.$renderData(Content, self.$CurrentVariant);
            }).then(function () {
                var el;

                // hide categories
                var VariantBody = self.getBody().getElement('.variant-body');
                var Categories  = VariantBody.getElement('[name="categories"]');
                var Row         = Categories.getParent('tr');

                Row.setStyle('display', 'none');
                VariantBody.getElements('[name="edit-fields"]').setStyle('display', 'none');

                // category
                el          = VariantBody.getElement('[name="product-category"]');
                el.disabled = true;
                el.getParent('tr').addClass('variant-field-disabled');

                // disable fields if not editable
                self.$renderEditableFields(
                    VariantBody.getElement('form')
                );

                return self.$showTabContent();
            });
        },

        /**
         * Checks if the field is editable
         * render the status for these fields, hide or show fields
         *
         * @param Form
         */
        $renderEditableFields: function (Form) {
            var el;

            // disable all fields
            var elements = new Elements(Form.elements);
            elements.set('disabled', true);

            // disable all qui controls
            elements.each(function (Node) {
                if (Node.nodeName !== 'INPUT') {
                    return;
                }

                Node.getParent('tr').addClass('variant-field-disabled');

                if (!Node.get('data-quiid')) {
                    return;
                }

                var Control = QUI.Controls.getById(Node.get('data-quiid'));

                if (typeof Control.disable === 'function') {
                    Control.disable();
                }
            });

            var of = this.$editableFields;

            for (var fieldId in of) {
                if (!of.hasOwnProperty(fieldId)) {
                    continue;
                }

                el = Form.elements['field-' + fieldId];

                if (!el || typeof el === 'undefined') {
                    continue;
                }

                if (typeof el.getParent !== 'function') {
                    el = new Elements(el);
                }

                el.set('disabled', false);
                el.getParent('tr').removeClass('variant-field-disabled');

                var Control = QUI.Controls.getById(el.get('data-quiid'));

                if (typeof Control.enable === 'function') {
                    Control.enable();
                }
            }
        },

        /**
         * Opens the price list
         *
         * @return {Promise}
         */
        $openVariantPrices: function () {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                return self.$renderPrices(Content, self.$CurrentVariant);
            }).then(function () {
                var VariantBody = self.getBody().getElement('.variant-body');

                self.$renderEditableFields(
                    VariantBody.getElement('form')
                );

                return self.$showTabContent();
            });
        },

        /**
         * Opens the variant folder viewer
         *
         * @param {Array} types
         * @param {Number} [fileId]
         *
         * @return {Promise}
         */
        $openVariantFolderViewer: function (types, fileId) {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                return Promise.all([
                    self.$CurrentVariant.hasOwnMediaFolder(),
                    Content
                ]);
            }).then(function (result) {
                var hasOwnMediaFolder = result[0];
                var Content           = result[1];

                Content.set('html', '');

                if (hasOwnMediaFolder) {
                    return self.$renderFolderViewer(Content, self.$CurrentVariant, types, fileId);
                }

                var VariantBody        = self.getBody().getElement('.variant-body');
                var OwnFolderContainer = new Element('div', {
                    'class': 'variants-tabs-image-own-folder'
                }).inject(VariantBody, 'top');

                new QUISwitch({
                    status: false,
                    events: {
                        onChange: self.$changeOwnFolderStatus
                    }
                }).inject(OwnFolderContainer);

                new Element('div', {
                    'class': 'variants-tabs-image-own-folder-text',
                    html   : QUILocale.get(lg, 'controls.product.variants.own.folder')
                }).inject(OwnFolderContainer);

            }).then(function () {
                return self.$showTabContent();
            });
        },

        /**
         * change own folder status
         */
        $changeOwnFolderStatus: function () {
            var self = this;

            self.Loader.hide();

            require(['qui/controls/windows/Confirm'], function (QUIConfirm) {
                new QUIConfirm({
                    icon       : 'fa fa-picture-o',
                    texticon   : 'fa fa-picture-o',
                    title      : QUILocale.get(lg, 'product.variant.change.folder.status.title'),
                    information: QUILocale.get(lg, 'product.variant.change.folder.status.information'),
                    text       : QUILocale.get(lg, 'product.variant.change.folder.status.text'),
                    maxHeight  : 300,
                    maxWidth   : 600,
                    events     : {
                        onCancel: function () {
                            self.$VariantTabBar.getActive().click();
                        },

                        onSubmit: function (Win) {
                            Win.Loader.show();

                            QUIAjax.post('package_quiqqer_products_ajax_products_variant_changeOwnFolderStatus', function () {
                                self.$CurrentVariant.refresh().then(function () {
                                    self.$VariantTabBar.getActive().click();
                                    Win.close();
                                });
                            }, {
                                'package': 'quiqqer/products',
                                productId: self.$CurrentVariant.getId()
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         *
         * @param Content
         * @return {Promise}
         */
        $setDataToCategory: function (Content) {
            var self = this;

            return new Promise(function (resolve) {
                var attributes = self.$CurrentVariant.getAttributes();

                console.log(attributes);

                resolve(Content);
            });
        },

        /**
         * Hide the tab content
         *
         * @return {Promise}
         */
        $hideTabContent: function () {
            var VariantBody = this.getBody().getElement('.variant-body');

            return this.$unloadCategory(VariantBody, this.$CurrentVariant).then(function () {
                return new Promise(function (resolve) {
                    moofx(VariantBody).animate({
                        left   : -20,
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            resolve(VariantBody);
                        }
                    });
                });
            });
        },

        /**
         * Show the tab content
         *
         * @return {Promise}
         */
        $showTabContent: function () {
            var VariantBody = this.getBody().getElement('.variant-body');

            return new Promise(function (resolve) {
                moofx(VariantBody).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: function () {
                        resolve(VariantBody);
                    }
                });
            });
        }

        //endregion variant tab
    });
});
