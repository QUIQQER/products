/**
 * Create a new product
 *
 * @module package/quiqqer/products/bin/controls/products/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/products/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Form',
    'Locale',
    'Mustache',
    'package/quiqqer/products/bin/classes/Products',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/controls/categories/Select',

    'text!package/quiqqer/products/bin/controls/products/Create.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Create.css'

], function (QUI, QUIControl, QUIFormUtils, QUILocale, Mustache, Handler, FieldHandler, CategoriesSelect,
             template, templateField) {
    "use strict";

    var lg       = 'quiqqer/products',
        Products = new Handler(),
        Fields   = new FieldHandler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/Create',

        Binds: [
            '$onInject'
        ],

        options: {
            productType: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Categories = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'product-create',
                html   : Mustache.render(template, {
                    productCategories   : QUILocale.get(lg, 'productCategories'),
                    productCategory     : QUILocale.get(lg, 'productCategory'),
                    productType         : QUILocale.get(lg, 'productType'),
                    productDefaultFields: QUILocale.get(lg, 'productDefaultFields'),
                    productMasterData   : QUILocale.get(lg, 'productMasterData'),
                    productAttributes   : QUILocale.get(lg, 'productAttributes')
                })
            });

            var ProductCategory = Elm.getElement('[name="product-category"]');

            this.$Categories = new CategoriesSelect({
                events: {
                    onDelete: function (Select, Item) {
                        var categoryId = Item.getAttribute('categoryId');
                        var Option     = ProductCategory.getElement(
                            '[value="' + categoryId + '"]'
                        );

                        if (Option) {
                            Option.destroy();
                        }
                    },
                    onChange: function () {
                        var ids = self.$Categories.getValue();
                        var Row = ProductCategory.getParent('tr');

                        if (ids === '') {
                            ids = [];
                        } else {
                            ids = ids.split(',');
                        }

                        if (!ids.length) {
                            Row.setStyles({
                                display : 'inline-block',
                                overflow: 'hidden',
                                opacity : 1,
                                position: 'relative'
                            });

                            moofx(Row).animate({
                                height : 0,
                                opacity: 0
                            }, {
                                duration: 200,
                                callback: function () {
                                    Row.setStyle('display', 'none');
                                    ProductCategory.set('html', '');
                                }
                            });

                            return;
                        }

                        var i, len, id;

                        for (i = 0, len = ids.length; i < len; i++) {
                            id = ids[i];
                            if (ProductCategory.getElement('[value="' + id + '"]')) {
                                continue;
                            }

                            new Element('option', {
                                value: id,
                                html : QUILocale.get(lg, 'products.category.' + id + '.title')
                            }).inject(ProductCategory);
                        }

                        if (Row.getStyle('display') === 'table-row') {
                            return;
                        }

                        Row.setStyles({
                            display : 'inline',
                            'float' : 'left',
                            height  : 0,
                            overflow: 'hidden',
                            position: 'relative'
                        });

                        moofx(Row).animate({
                            height : 50,
                            opacity: 1
                        }, {
                            duration: 200,
                            callback: function () {
                                Row.setStyles({
                                    display: 'table-row',
                                    'float': null

                                });
                            }
                        });
                    }
                }
            }).inject(
                Elm.getElement('.product-categories')
            );

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this,
                Elm  = this.getElm();

            var StandardFields = Elm.getElement('.product-standardfield tbody');
            var Data           = Elm.getElement('.product-data tbody');

            Promise.all([
                Fields.getSystemFields(),
                Fields.getStandardFields(),
                Products.getTypes()
            ]).then(function (result) {
                var i, len, field, entry, Label;
                var systemFields   = result[0],
                    standardFields = result[1],
                    types          = result[2];

                var diffFields = standardFields.filter(function (value) {
                    for (var i = 0, len = systemFields.length; i < len; i++) {
                        if (value.id === systemFields[i].id) {
                            return false;
                        }
                    }
                    return true;
                });

                var useField = function (Field) {
                    if (Field.id === 10 ||  // Folder existiert noch nicht, wird erst angelegt
                        Field.id === 9) {   // Image kann beim create nicht gesetzt werden, noch kein Media Ordner vorhanden
                        return false;
                    }

                    if (Field.id >= 100) {
                        return false;
                    }

                    if (Field.type === 'TextareaMultiLang' ||
                        Field.type === 'Textarea' ||
                        Field.type === 'Products'
                    ) {
                        return false;
                    }

                    return true;
                };

                // standard felder
                for (i = 0, len = diffFields.length; i < len; i++) {
                    field = diffFields[i];

                    if (!useField(field)) {
                        continue;
                    }

                    new Element('tr', {
                        'class'       : 'field',
                        html          : Mustache.render(templateField, {
                            fieldTitle: QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                            fieldName : 'field-' + field.id,
                            control   : field.jsControl
                        }),
                        'data-fieldid': field.id
                    }).inject(StandardFields);
                }

                // systemfields
                for (i = 0, len = systemFields.length; i < len; i++) {
                    field = systemFields[i];

                    if (!useField(field)) {
                        continue;
                    }

                    new Element('tr', {
                        'class'       : 'field',
                        html          : Mustache.render(templateField, {
                            fieldTitle: QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                            fieldName : 'field-' + field.id,
                            control   : field.jsControl
                        }),
                        'data-fieldid': field.id
                    }).inject(Data);
                }

                // types
                var ProductTypes = Elm.getElement('.product-types');

                var productTypeChange = function (event) {
                    var Target = event.target;

                    Elm.getElement('form').getElements('[name="productType"]').each(function (Node) {
                        if (Node !== Target) {
                            Node.set('checked', false);
                        }
                    });
                };

                for (i = 0, len = types.length; i < len; i++) {
                    entry = types[i];

                    if (entry.isTypeSelectable === false) {
                        continue;
                    }

                    // default product
                    if (entry.type === '\\QUI\\ERP\\Products\\Product\\Types\\Product') {
                        continue;
                    }

                    Label = new Element('label', {
                        html  : '<input type="checkbox" name="productType" value="' + entry.type + '" />' + entry.typeTitle,
                        styles: {
                            padding: 10,
                            width  : '100%'
                        }
                    }).inject(ProductTypes);

                    Label.getElement('input').addEvent('change', productTypeChange);
                }

                QUI.parse(self.getElm()).then(function () {
                    self.fireEvent('loaded');
                });
            });
        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {
            var self = this,
                Elm  = this.getElm();


            var cValue     = self.$Categories.getValue().trim();
            var categories = cValue.split(',');
            var Form       = Elm.getElement('form');
            var data       = QUIFormUtils.getFormData(Form);

            // fields
            var fields = Object.filter(data, function (value, key) {
                return (key.indexOf('field-') >= 0);
            });

            if (!categories.length || cValue === '') {
                QUI.getMessageHandler().then(function (MH) {
                    MH.addAttention(
                        QUILocale.get(lg, 'message.product.create.missing.category'),
                        Elm.getElement('.product-categories')
                    );
                });

                return Promise.reject('No categories');
            }

            var productType  = '\\QUI\\ERP\\Products\\Product\\Types\\Product';
            var productTypes = Form.getElements('[name="productType"]').filter(function (Input) {
                return Input.checked;
            });

            if (productTypes.length) {
                productType = productTypes[0].value;
            }

            return Products.createChild(
                categories,
                fields,
                productType
            );
        }
    });
});
