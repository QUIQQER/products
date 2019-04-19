/**
 * @module package/quiqqer/products/bin/controls/products/GenerateVariants
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/GenerateVariants', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, Grid, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/GenerateVariants',

        Binds: [
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {Element}
         */
        create: function () {
            this.parent();

            this.$Elm = new Element('div', {
                'class'   : 'quiqqer-products-variant-generate',
                id        : this.getId(),
                'data-qui': 'package/quiqqer/products/bin/controls/products/variants/GenerateVariants',
                styles    : {
                    height: '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            if (!this.$Grid) {
                return;
            }

            var size = this.$Elm.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Saves the overwriteable fields to the product
         *
         * @return {Promise}
         */
        save: function () {

        },

        /**
         * event: on inject
         */
        $onInject: function () {
            // get attribute fields
            QUIAjax.get('package_quiqqer_products_ajax_products_variant_getVariantFields', function (fields) {
                var columns = [{
                    header   : '&nbsp;',
                    dataIndex: 'select',
                    dataType : 'node',
                    width    : 60
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
                }];

                for (var i = 0, len = fields.length; i < len; i++) {
                    columns.push({
                        header   : fields[i].title,
                        dataIndex: 'field-' + fields[i].id,
                        dataType : 'text',
                        width    : 100
                    });
                    //console.log(fields);
                }


                var Container = new Element('div').inject(this.$Elm);

                this.$Grid = new Grid(Container, {
                    pagination : true,
                    width      : Container.getSize().x,
                    height     : Container.getSize().y,
                    columnModel: columns
                });

                this.resize();
                this.fireEvent('load', [this]);
            }.bind(this), {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId')
            });
        }
    });
});
