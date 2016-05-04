/**
 * @module package/quiqqer/products/bin/controls/products/search/Result
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require Locale
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
            '$onInject'
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
                html   : '<div class="quiqqer-products-search-grid"></div>'
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
            console.warn(data);
            this.$Grid.setData(data);
        },

        /**
         * event : on inject
         */
        $onInject: function () {

            this.$Grid = new Grid(this.$GridContainer, {
                pagination : true,
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
                    dataIndex: 'ownField',
                    dataType : 'hidden'
                }]
            });
        }
    });
});
