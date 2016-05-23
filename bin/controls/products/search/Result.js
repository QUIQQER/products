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
 *
 * @event onRefresh [this, {Object} GridOptions]
 * @event onSubmit [this, {Array} productIds]
 */
define('package/quiqqer/products/bin/controls/products/search/Result', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Locale'

], function (QUI, QUIControl, Grid, QUILocale) {
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
            styles: false
        },

        initialize: function (options) {
            this.$Grid          = null;
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

            this.$GridContainer = this.$Elm.getElement('.quiqqer-products-search-grid');

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
            this.$Grid.setData(data);
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Grid = new Grid(this.$GridContainer, {
                pagination       : true,
                multipleSelection: true,
                columnModel      : [{
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

            this.$Grid.addEvents({
                onRefresh: function () {
                    this.fireEvent('refresh', [this, this.$Grid.options]);
                }.bind(this),

                onDblClick: this.submit
            });
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
