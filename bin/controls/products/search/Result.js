/**
 * @module package/quiqqer/products/bin/controls/products/search/Result
 * @author www.pcsg.de (Henning Leutz)
 *
 * Display the results from a product search
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require Locale
 * @require package/quiqqer/products/bin/Fields
 * @require css!package/quiqqer/products/bin/controls/products/search/Result.css
 *
 * @event onRefresh [this, {Object} GridOptions]
 * @event onSubmit [this, {Array} productIds]
 * @event onDblClick [this, {Array} productIds]
 */
define('package/quiqqer/products/bin/controls/products/search/Result', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/Fields',

    'css!package/quiqqer/products/bin/controls/products/search/Result.css'

], function (QUI, QUIControl, Grid, QUILocale, Fields) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Result',

        Binds: [
            '$onInject',
            'submit'
        ],

        options: {
            styles : false,
            sortOn : false,
            sortBy : false,
            perPage: 150,
            page   : false
        },

        initialize: function (options) {
            this.$data = null;
            this.$Grid = null;

            this.$GridContainer = null;

            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDirectoryElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-search-results',
                html   : '<div class="quiqqer-products-search-grid"></div>',
                styles : {
                    'float': 'left',
                    height : '100%',
                    width  : '100%'
                }
            });

            this.$GridContainer = this.$Elm.getElement(
                '.quiqqer-products-search-grid'
            );

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            return this.$Elm;
        },

        /**
         * Resize
         *
         * @return {Promise}
         */
        resize: function () {
            var size = this.$Elm.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Set result data to the grid
         *
         * @param {Object} data - grid data
         */
        setData: function (data) {
            var i, len, entry, productNo;

            var findProductNo = function (o) {
                return o.id == Fields.FIELD_PRODUCT_NO;
            };

            for (i = 0, len = data.data.length; i < len; i++) {
                entry     = data.data[i];
                productNo = '';

                // active status
                data.data[i].status = new Element('span', {
                    'class': entry.active ? 'fa fa-check' : 'fa fa-remove'
                });

                // product no
                if ("fields" in entry) {
                    productNo = entry.fields.find(findProductNo);
                }

                data.data[i].productNo = productNo.value || '';

                if (data.data[i].price_netto) {
                    data.data[i].price_netto = new Element('span', {
                        html   : data.data[i].price_netto.toFixed(2),
                        'class': 'quiqqer-products-search-results--price-display'
                    });
                } else {
                    data.data[i].price_netto = new Element('span', {
                        html   : '---',
                        'class': 'quiqqer-products-search-results--price-display'
                    });
                }
            }

            if (!this.$Grid) {
                this.$data = data;
                return;
            }

            this.$Grid.setData(data);
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Grid = new Grid(this.$GridContainer, {
                pagination       : true,
                multipleSelection: true,
                perPage          : this.getAttribute('perPage'),
                page             : this.getAttribute('page'),
                sortOn           : this.getAttribute('sortOn'),
                serverSort       : true,
                columnModel      : [{
                    header   : QUILocale.get('quiqqer/system', 'status'),
                    dataIndex: 'status',
                    dataType : 'node',
                    width    : 40
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
                    header   : QUILocale.get('quiqqer/system', 'description'),
                    dataIndex: 'description',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'products.product.panel.grid.nettoprice'),
                    dataIndex: 'price_netto',
                    dataType : 'node',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'products.product.panel.grid.currency'),
                    dataIndex: 'price_currency',
                    dataType : 'text',
                    width    : 60
                }]
            });

            this.$Grid.addEvents({
                onRefresh: function () {
                    this.fireEvent('refresh', [this, this.$Grid.options]);
                }.bind(this),

                onDblClick: function () {
                    this.fireEvent('dblClick', [this, this.getSelected()]);
                    this.submit();
                }.bind(this),

                onClick: function () {
                    this.fireEvent('click', [this, this.getSelected()]);
                }.bind(this)
            });

            if (this.$data) {
                this.$Grid.setData(this.$data);
            }
        },

        /**
         * Return the selected product ids
         *
         * @returns {Array}
         */
        getSelected: function () {
            var selected = this.$Grid.getSelectedData();

            if (!selected.length) {
                return [];
            }

            return selected.map(function (entry) {
                return entry.id;
            });
        },

        /**
         * submit the selected data
         *
         * @fires onSelect
         */
        submit: function () {
            this.fireEvent('submit', [this, this.getSelected()]);
        }
    });
});
