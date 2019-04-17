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
    'css!package/quiqqer/products/bin/controls/products/ProductVariant.css'

], function (QUI, ProductPanel, QUISelect, QUIBar, QUITab, Grid, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: ProductPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/ProductVariant',

        Binds: [
            '$onCreate',
            '$onInject'
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

            this.$Grid     = null;
            this.$Variants = null;

            this.addEvents({
                onCreate: this.$onCreate
            });
        },

        /**
         * event: on create
         */
        $onCreate: function () {
            this.parent();


        },

        $onInject: function () {
            var self = this;

            this.parent().then(function () {
                self.addCategory({
                    name  : 'variants',
                    text  : 'VARIANTEN',
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
                    1
                );
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

            var self = this;

            return self.$hideCategories().then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('.variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }

                VariantSheet.set('html', '');

                self.$Grid = new Grid(VariantSheet, {
                    pagination : true,
                    width      : VariantSheet.getSize().x - 40,
                    height     : VariantSheet.getSize().y - 40,
                    columnModel: [{
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
                    }, {
                        header   : QUILocale.get('quiqqer/system', 'status'),
                        dataIndex: 'status',
                        dataType : 'text',
                        width    : 60
                    }]
                });

                self.$Grid.addEvents({
                    onDblClick: function () {
                        self.selectVariant(
                            self.$Grid.getSelectedData()[0].id
                        );
                    }
                });

                return self.refreshVariantGrid();
            }).then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('.variants-sheet');

                return self.$showCategory(VariantSheet);
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
                    'productNo', 'title', 'price_netto',
                    'e_date', 'c_date', 'priority', 'status'
                ];

                var fillMissing = function (variant) {
                    var i, len, attribute;
                    for (i = 0, len = needles.length; i < len; i++) {
                        attribute = needles[i];

                        if (typeof variant[attribute] === 'undefined' || !variant[attribute]) {
                            variant[attribute] = '-';
                        }
                    }

                    return variant;
                };

                for (var i = 0, len = variants.length; i < len; i++) {
                    variants[i] = fillMissing(variants[i]);
                }

                self.$Grid.setData({
                    data : variants,
                    total: variants.length,
                    page : 1
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

                // @todo categorien wieder normal, wenn zurück
                // @todo grid aller varianten anzeigen wenn keine variante ausgewählt ist


                var VariantList = Body.getElement('.variant-list');
                var VariantTabs = Body.getElement('.variants-tabs');
                var VariantBody = Body.getElement('.variant-body');

                // variant select
                var VariantSelect = new QUISelect({
                    placeholder: 'Variante wechseln',
                    showIcons  : false,
                    styles     : {
                        width: '70%'
                    }
                }).inject(VariantList);

                var i, len, name, variant, Category;

                for (i = 0, len = variants.length; i < len; i++) {
                    variant = variants[i];

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
                            name: name,
                            text: Category.getAttribute('text')
                        })
                    );
                }

                TabBar.resize();
                TabBar.firstChild().setActive();


                // body
                VariantBody.set('html', 'test');

                return self.$showCategory(VariantSheet);
            });
        }
    });
});
