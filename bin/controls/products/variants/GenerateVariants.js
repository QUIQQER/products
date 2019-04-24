/**
 * @module package/quiqqer/products/bin/controls/products/GenerateVariants
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/GenerateVariants', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/variants/GenerateVariants.List.html',
    'css!package/quiqqer/products/bin/controls/products/variants/GenerateVariants.css'

], function (QUI, QUIControl, Grid, QUIAjax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/GenerateVariants',

        Binds: [
            '$onInject',
            'refreshCalc'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid        = null;
            this.$CalcDisplay = null;

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
        generate: function () {
            var self   = this,
                tables = this.getElm().getElements('table');

            var fields = tables.map(function (Table) {
                var inputs = Table.getElements('input[type="checkbox"]:checked');
                var values = inputs.map(function (Input) {
                    return Input.value;
                });

                return {
                    fieldId: Table.get('data-field-id'),
                    values : values
                };
            });

            return new Promise(function (resolve, reject) {
                QUIAjax.post('package_quiqqer_products_ajax_products_variant_generate_generate', resolve, {
                    'package': 'quiqqer/products',
                    productId: self.getAttribute('productId'),
                    fields   : JSON.encode(fields),
                    onError  : reject
                });
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            // get attribute fields
            QUIAjax.get('package_quiqqer_products_ajax_products_variant_getVariantFields', function (fields) {
                var i, len, field, values;

                var Container = new Element('div', {
                        'class': 'quiqqer-products-variant-generate-tableBody'
                    }).inject(this.$Elm),
                    current   = QUILocale.getCurrent(),
                    fieldList = [];

                this.$CalcDisplay = new Element('div', {
                    'class': 'quiqqer-products-variant-generate-calcDisplay'
                }).inject(this.$Elm);

                var filterValues = function (entry, key) {
                    if ("valueId" in entry) {
                        key = entry.valueId;
                    }

                    return {
                        fieldId: field.id,
                        title  : entry.title[current],
                        valueId: key
                    };
                };

                for (i = 0, len = fields.length; i < len; i++) {
                    field  = fields[i];
                    values = field.options.entries.map(filterValues);

                    fieldList.push({
                        fieldId: field.id,
                        title  : field.title,
                        values : values
                    });
                }

                Container.set('html', Mustache.render(template, {
                    fields           : fieldList,
                    message_no_values: QUILocale.get(lg, 'variants.generating.window.message.no.values')
                }));

                Container.getElements('input').addEvent('change', this.refreshCalc);

                this.refreshCalc();

                this.resize();
                this.fireEvent('load', [this]);
            }.bind(this), {
                'package': 'quiqqer/products',
                productId: this.getAttribute('productId')
            });
        },

        /**
         * refresh the calc display
         */
        refreshCalc: function () {
            var count  = 0,
                tables = this.getElm().getElements('table');

            var counts = tables.map(function (Table) {
                return Table.getElements('input[type="checkbox"]:checked').length;
            });

            for (var i = 0, len = counts.length; i < len; i++) {
                if (counts[i] === 0) {
                    continue;
                }

                if (count === 0) {
                    count = 1;
                }

                count = count * counts[i];
            }

            this.$CalcDisplay.set('html', QUILocale.get(lg, 'variants.generating.window.calc', {
                count: count
            }));
        }
    });
});
