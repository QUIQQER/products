/**
 *
 *
 * @module package/quiqqer/products/bin/controls/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/ProductVariant', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/products/Product',
    'qui/controls/buttons/Select',
    'qui/controls/toolbar/Bar',
    'qui/controls/toolbar/Tab',
    'controls/grid/Grid',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/ProductVariant.html',
    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/ProductPrices.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',

    'css!package/quiqqer/products/bin/controls/products/ProductVariant.css'

], function (QUI, ProductPanel, QUISelect, QUIBar, QUITab, Grid, QUILocale, Mustache,
             template, templateProductData) {
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
            'addVariant'
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

            this.$Grid          = null;
            this.$VariantFields = null;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

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

                var categories    = self.getCategoryBar().getChildren();
                var resetMinimize = function (Category) {
                    if (Category.getAttribute('name') === 'variants') {
                        return;
                    }

                    self.maximizeCategory();
                    self.$VariantFields.hide();
                    self.getElm().removeClass('quiqqer-products-panel-show-variant');
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

                self.$VariantFields = self.getButtons('variantFields');
                self.$VariantFields.hide();
            });
        },

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

            self.$VariantFields.show();

            return self.$hideCategories().then(function () {
                var VariantSheet = Body.getElement('.variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }

                VariantSheet.set('html', '');

                var Container = new Element('div').inject(VariantSheet);

                self.$Grid = new Grid(Container, {
                    pagination : true,
                    width      : VariantSheet.getSize().x - 40,
                    height     : VariantSheet.getSize().y - 40,
                    buttons    : [{
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
                    columnModel: [{
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

                    }
                });

                return self.refreshVariantGrid();
            }).then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('.variants-sheet');

                return self.$showCategory(VariantSheet);
            }).then(function () {
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
                console.log(variants);

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

            return self.$hideCategories().then(function () {
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
                var VariantBody  = Body.getElement('.variant-body');

                // variant select
                var VariantSelect = new QUISelect({
                    placeholder: 'Variante wechseln', // #locale
                    showIcons  : false,
                    styles     : {
                        width: '70%'
                    }
                }).inject(VariantList);

                var i, len, name, variant, Category;

                for (i = 0, len = variants.length; i < len; i++) {
                    variant = variants[i];

                    // #locale
                    VariantSelect.appendChild(
                        'Zu variante wechseln: ' + variant.id + ' - ' + variant.title
                    );
                }

                VariantSelect.setValue(
                    VariantSelect.firstChild().getAttribute('value')
                );


                // tabs
                var TabBar = new QUIBar({
                    width: Body.getSize().x
                }).inject(VariantTabs);

                // workaround
                TabBar.getElm().getElement('.qui-toolbar-tabs').setStyle('display', 'flex');

                var categories = self.getCategoryBar().getChildren();

                for (i = 0, len = categories.length; i < len; i++) {
                    Category = categories[i];
                    name     = Category.getAttribute('name');

                    if (name === 'variants') {
                        continue;
                    }

                    if (name === 'information') {
                        continue;
                    }

                    if (name === 'attributelist') {
                        continue;
                    }

                    TabBar.appendChild(
                        new QUITab({
                            name  : name,
                            text  : Category.getAttribute('text'),
                            events: {
                                onClick: self.openVariantTab
                            }
                        })
                    );
                }

                TabBar.resize();
                TabBar.firstChild().click();


                // body
                VariantBody.set('html', 'test');

                return self.$showCategory(VariantSheet);
            });
        },

        //region variant generating

        addVariant: function () {

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

            console.log(name);

            if (name === 'data') {
                return this.$openVariantData().then(done);
            }

            return Promise.resolve().then(done);
        },

        /**
         * opens the data
         *
         * @return {Promise}
         */
        $openVariantData: function () {
            var self = this;

            return this.$hideTabContent().then(function (Content) {
                Content.set('html', self.$Data.get('html'));

                Content.getElement('[name="categories"]')
                       .getParent('tr')
                       .destroy();

                Content.getElement('[name="product-category"]')
                       .getParent('tr')
                       .destroy();

                Content.getElement('form + button')
                       .destroy();

                QUI.Controls.getControlsInElement(Content).each(function (Field) {
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

                return self.$showTabContent();
            });
        },

        /**
         * Hide the tab content
         *
         * @return {Promise}
         */
        $hideTabContent: function () {
            var VariantBody = this.getBody().getElement('.variant-body');

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
