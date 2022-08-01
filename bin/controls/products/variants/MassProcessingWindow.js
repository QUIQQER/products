/**
 * @module package/quiqqer/products/bin/controls/products/variants/MassProcessingWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/MassProcessingWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/utils/Products',
    'qui/controls/buttons/Select',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/products/variants/MassProcessingWindow.css'

], function (QUI, QUIConfirm, Product, ProductUtils, QUISelect, QUIAjax, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/MassProcessingWindow',

        Binds: [
            '$onSubmit',
            '$onOpen'
        ],

        options: {
            productIds: false, // array of product ids
            maxWidth  : 660,
            maxHeight : 600,
            buttons   : true,
            autoclose : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$fields = {};

            this.setAttributes({
                icon : 'fa fa-edit',
                title: QUILocale.get(lg, 'window.products.variant.mass.processing.title')
            });

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * @returns {Promise<unknown>}
         */
        $onOpen: function () {
            if (!this.getAttribute('productIds')) {
                this.close();
                return Promise.resolve();
            }

            const productIds = this.getAttribute('productIds');

            // fetch product data
            this.Loader.show();
            this.getContent().addClass('product-variant-mass-processing');
            this.getContent().set('html', '');

            return this.getVariantParentId(productIds[0]).then((variantParentId) => {
                new Element('div', {
                    'class': 'product-variant-mass-processing-text',
                    html   : QUILocale.get(lg, 'window.products.variant.mass.processing.content', {
                        productCount: productIds.length
                    })
                }).inject(this.getContent());

                const VariantParent = new Product({
                    id: variantParentId
                });

                return VariantParent.getFields();
            }).then((fields) => {
                this.$fields = fields;

                // field select
                const Select = new QUISelect({
                    showIcons : false,
                    searchable: true,
                    styles    : {
                        float : 'initial',
                        margin: '20px auto 0',
                        width : '100%'
                    },
                    events    : {
                        onChange: (value) => {
                            this.$onSelectChange(value);
                        }
                    }
                }).inject(this.getContent().getElement('.product-variant-mass-processing-text'));

                new Element('div', {
                    'class': 'product-variant-mass-processing-content'
                }).inject(this.getContent());

                fields.sort(function (a, b) {
                    return ('' + a.title).localeCompare(b.title);
                });

                for (let i = 0, len = fields.length; i < len; i++) {
                    if (fields[i].type === "Image" ||
                        fields[i].type === "Folder") {
                        continue;
                    }

                    Select.appendChild(
                        fields[i].title,
                        fields[i].id
                    );
                }

                this.Loader.hide();
            });
        },

        $onSubmit: function () {
            this.Loader.show();


        },

        /**
         * get the parent id from the variants
         *
         * @param productId
         */
        getVariantParentId: function (productId) {
            return new Promise((resolve) => {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getParent', resolve, {
                    'package': 'quiqqer/products',
                    productId: productId
                });
            });
        },

        /**
         * event: on select change
         *
         * @param value
         */
        $onSelectChange: function (value) {
            this.Loader.show();

            require(['package/quiqqer/products/bin/Fields'], (Fields) => {
                Promise.all([
                    Fields.getFieldTypes(),
                    Fields.getChild(value)
                ]).then((result) => {
                    let i, len;

                    const types = result[0];
                    const field = result[1];
                    const fieldTypes = [];

                    for (i = 0, len = types.length; i < len; i++) {
                        fieldTypes[types[i].name] = types[i];
                    }

                    const Row = ProductUtils.renderDataField(field);
                    const Parent = this.getContent().getElement('.product-variant-mass-processing-content');

                    Parent.set('html', '');

                    if (field.type === 'TextareaMultiLang' ||
                        field.type === 'Textarea' ||
                        field.type === 'Folder' ||
                        field.type === 'Products' ||
                        fieldTypes[field.type].category
                    ) {
                        // categories controls
                        return new Promise((resolve) => {
                            require([field.jsControl], function (Control) {
                                new Control().inject(Parent);
                                resolve();
                            });
                        });
                    }

                    // details (data) controls
                    const Table = new Element('table', {
                        'class': 'data-table data-table-flexbox product-data'
                    }).inject(Parent);

                    Row.inject(Table);
                    Table.inject(Parent);

                    return QUI.parse(Parent);
                }).then(() => {
                    this.Loader.hide();
                });
            });
        }
    });
});