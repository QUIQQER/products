/**
 * Variant Panel
 * this panel shows additional variant stuff
 *
 * @module package/quiqqer/products/bin/controls/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 *
 * @todo locale
 * @todo locale en prüfen
 * @todo grid blätter funktion
 * @todo varianten aktivieren über das grid (auch mehrfach)
 * @todo varianten deaktivieren über das grid (auch mehrfach)
 * @todo nicht überschreibbare felder disablen
 * @todo kategorien bei varianten raus
 */
define('package/quiqqer/products/bin/controls/products/ProductVariant', [

    'qui/QUI',
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
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/ProductVariant.html',

    'css!package/quiqqer/products/bin/controls/products/ProductVariant.css'

], function (QUI, ProductPanel, Product, Fields, Products,
             QUISelect, QUIBar, QUITab, QUIContextMenu, QUIContextMenuItem, QUIContextMenuSeparator,
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
            '$activateVariants',
            '$deactivateVariants'
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

            var self = this;

            this.parent(options);

            this.$Grid = null;
            this.$Menu = new QUIContextMenu({
                events: {
                    onBlur: function () {
                        (function () {
                            self.$Menu.hide();
                        }).delay(200);
                    }
                }
            });

            this.$CurrentVariant = null;
            this.$VariantTabBar  = null;

            // panel extra buttons
            this.$VariantFields     = null;
            this.$BackToVariantList = null;

            // panel buttons
            this.$SaveButton      = null;
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

            this.parent().then(function () {
                self.addCategory({
                    name  : 'variants',
                    text  : QUILocale.get(lg, 'panel.variants.category.title'),
                    icon  : 'fa fa-info',
                    events: {
                        onClick: function () {
                            self.Loader.show();
                            self.openVariants().then(function () {
                                self.Loader.hide();
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

                    self.$SaveButton.show();
                    self.$StatusButton.show();
                    self.$ActionSeparator.show();

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
                    title : QUILocale.get(lg, 'panel.variants.overwriteable.button.title'),
                    icon  : 'fa fa-exchange',
                    events: {
                        click: self.openVariantAttributeSettings
                    },
                    styles: {
                        'float': 'right'
                    }
                });

                self.addButton({
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
                });

                self.$VariantFields = self.getButtons('variantFields');
                self.$VariantFields.hide();

                self.$BackToVariantList = self.getButtons('BackToVariantFields');
                self.$BackToVariantList.hide();
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
                    self.$CurrentVariant.getFields()
                ]);
            }).then(function (result) {
                var categories = result[0];
                var category   = result[1];
                var fieldsList = result[2];

                // parse fields to ajax field array
                var i, len, entry;
                var fields = {};

                for (i = 0, len = fieldsList.length; i < len; i++) {
                    entry = fieldsList[i];

                    fields['field-' + entry.id] = entry.value;
                }

                return Products.updateChild(
                    self.$CurrentVariant.getId(),
                    categories,
                    category,
                    fields
                );
            }).then(function () {
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

            var self = this,
                Body = self.getBody();

            this.$VariantFields.show();

            this.$SaveButton.hide();
            this.$StatusButton.hide();
            this.$ActionSeparator.hide();

            return self.$hideCategories().then(function () {
                var VariantSheet = Body.getElement('.variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }

                VariantSheet.set('html', '');

                var LC        = new Element('div.variant-list-variantListContainer').inject(VariantSheet);
                var Container = new Element('div').inject(LC);

                self.$Grid = new Grid(Container, {
                    pagination       : true,
                    multipleSelection: true,
                    width            : VariantSheet.getSize().x - 40,
                    height           : VariantSheet.getSize().y - 40,
                    buttons          : [{
                        textimage: 'fa fa-plus',
                        text     : 'Neue Variante',
                        events   : {
                            click: self.addVariant
                        }
                    }, {
                        textimage: 'fa fa-magic',
                        text     : 'Varianten generieren',
                        styles   : {
                            'float': 'right'
                        },
                        events   : {
                            click: self.openVariantGenerating
                        }
                    }],
                    columnModel      : [{
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
                        width    : 100
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'title'),
                        dataIndex: 'title',
                        dataType : 'text',
                        width    : 200
                    }, {
                        header   : QUILocale.get(lg, 'products.product.panel.grid.nettoprice'),
                        dataIndex: 'price_netto',
                        dataType : 'text',
                        width    : 100
                    }, {
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
                        width    : 60
                    }]
                });

                self.$Grid.addEvents({
                    onDblClick: function () {
                        self.selectVariant(
                            self.$Grid.getSelectedData()[0].id
                        );
                    },
                    onRefresh : function () {

                    },

                    onContextMenu: function (event) {
                        self.$Menu.clearChildren();
                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text  : 'Alle markierten Varianten aktivieren',
                                icon  : 'fa fa-check',
                                events: {
                                    onClick: self.$activateVariants
                                }
                            })
                        );

                        self.$Menu.appendChild(
                            new QUIContextMenuItem({
                                text  : 'Alle markierten Varianten deaktivieren',
                                icon  : 'fa fa-cancel',
                                events: {
                                    onClick: self.$deactivateVariants
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

            var self = this;

            return this.$Product.getVariants().then(function (variants) {
                var needles = [
                    'id', 'title', 'e_date', 'c_date', 'priority'
                ];

                var fields = {
                    'productNo'  : 3,
                    'price_netto': 1,
                    'priority'   : 18
                };

                var i, n, len, nLen, entry, variant, needle, field, fieldId;
                var data = [];

                var filterField = function (field) {
                    return field.id === this;
                };

                for (i = 0, len = variants.length; i < len; i++) {
                    entry   = {};
                    variant = variants[i];

                    // status
                    if (variant.active) {
                        entry.status = new Element('span', {'class': 'fa fa-check'});
                    } else {
                        entry.status = new Element('span', {'class': 'fa fa-close'});
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
                    total: variants.length,
                    page : 1
                });
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
                    'package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldListWindow'
                ], function (Window) {
                    new Window({
                        productId: self.getAttribute('productId')
                    }).open();

                    resolve();
                });
            });
        },

        /**
         *
         * @param variantId
         * @return {Promise}
         */
        selectVariant: function (variantId) {
            var self = this;
            var Body = self.getBody();

            this.$BackToVariantList.show();

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
            }).then(function (variants) {
                var VariantSheet = Body.getElement('.variants-sheet');
                var VariantList  = Body.getElement('.variant-list');
                var VariantTabs  = Body.getElement('.variants-tabs');


                var VariantSelect = new QUISelect({
                    placeholder: 'Variante wechseln', // #locale
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

                    title = 'Zu Variante wechseln: '; // #locale
                    title = title + vId;
                    title = title + ' - ' + vTitle;

                    if (vProductNo && vProductNo.length && vProductNo[0].value) {
                        title = title + ' - ' + vProductNo[0].value;
                    }

                    VariantSelect.appendChild(title, variant.id);
                }

                VariantSelect.setValue(
                    VariantSelect.firstChild().getAttribute('value')
                );


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
                self.$SaveButton.setAttribute('text', 'Save Variant');
                self.$StatusButton.setAttribute('text', 'Variant is deactivated');

                self.$ActionSeparator.show();
                self.$SaveButton.show();
                self.$StatusButton.show();
            });
        },

        /**
         * on variant change
         * @param variantId
         */
        $changeVariant: function (variantId) {
            if (this.$VariantTabBar === null) {
                return;
            }

            var Active = this.$VariantTabBar.getChildren().filter(function (Tab) {
                return Tab.isActive();
            })[0];

            this.$CurrentVariant = new Product({
                id: variantId
            });

            this.openVariantTab(Active);
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

        //endregion

        //region variant generating

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
                this.Loader.hide();
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
                return self.$showTabContent();
            });
        },

        /**
         *
         * @return {Promise}
         */
        $openVariantPrices: function () {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                return self.$renderPrices(Content, self.$CurrentVariant);
            }).then(function () {
                return self.$showTabContent();
            });
        },

        /**
         * Opens the variant folder viewer
         *
         * @param {Array} types
         * @return {Promise<T | never>|*}
         */
        $openVariantFolderViewer: function (types) {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                return self.$renderFolderViewer(Content, self.$CurrentVariant, types);
            }).then(function () {
                return self.$showTabContent();
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
