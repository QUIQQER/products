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

    let lg = 'quiqqer/products';

    const Products = new Handler(),
          Fields   = new FieldHandler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/Create',

        Binds: [
            '$onInject'
        ],

        options: {
            productType: '',
            categories : false
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
            const Elm = this.parent();

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

            const ProductCategory = Elm.getElement('[name="product-category"]');

            Elm.getElement('form').addEvent('submit', function (e) {
                e.stop();
            });

            this.$Categories = new CategoriesSelect({
                events: {
                    onDelete: function (Select, Item) {
                        let categoryId = Item.getAttribute('categoryId');
                        const Option   = ProductCategory.getElement(
                            '[value="' + categoryId + '"]'
                        );

                        if (Option) {
                            Option.destroy();
                        }
                    },
                    onChange: () => {
                        let ids   = this.$Categories.getValue();
                        const Row = ProductCategory.getParent('tr');

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

                        let i, len, id;

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

            if (typeOf(this.getAttribute('categories')) === 'array') {
                this.$Categories;
            }

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const Elm            = this.getElm();
            const StandardFields = Elm.getElement('.product-standardfield tbody');
            const Data           = Elm.getElement('.product-data tbody');

            Promise.all([
                Fields.getSystemFields(),
                Fields.getStandardFields(),
                Products.getTypes()
            ]).then((result) => {
                let i, len, field, entry, Label;
                let systemFields   = result[0],
                    standardFields = result[1],
                    types          = result[2];

                let diffFields = standardFields.filter(function (value) {
                    for (let i = 0, len = systemFields.length; i < len; i++) {
                        if (value.id === systemFields[i].id) {
                            return false;
                        }
                    }
                    return true;
                });

                const useField = function (Field) {
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
                            control   : field.jsControl,
                            required  : field.isRequired ? 1 : 0
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
                            control   : field.jsControl,
                            required  : field.isRequired ? 1 : 0
                        }),
                        'data-fieldid': field.id
                    }).inject(Data);
                }

                // types
                const ProductTypes = Elm.getElement('.product-types');

                const productTypeChange = function (event) {
                    let Target = event.target;

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

                QUI.parse(this.getElm()).then(() => {
                    if (!this.getAttribute('categories')) {
                        this.fireEvent('loaded');
                        return;
                    }

                    const categories = this.getAttribute('categories');

                    if (categories.length) {
                        categories.forEach((categoryId) => {
                            this.$Categories.addCategory(categoryId);
                        });
                    }

                    this.fireEvent('loaded');
                });
            });
        },

        /**
         * Create the field
         *
         * @returns {Promise}
         */
        submit: function () {
            const Elm = this.getElm();

            let cValue     = this.$Categories.getValue().trim();
            let categories = cValue.split(',');
            let Form       = Elm.getElement('form');
            let data       = QUIFormUtils.getFormData(Form);

            // fields
            let fields = Object.filter(data, function (value, key) {
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

            // check require
            let l, name, value, Label;
            let required = Form.getElements('[data-required="1"]');

            const triggerMessage = function (Field, message) {
                QUI.getMessageHandler().then(function (MH) {
                    MH.addAttention(message, Field);
                });
            };

            for (let i = 0, len = required.length; i < len; i++) {
                name  = required[i].get('name');
                value = required[i].value;

                if (required[i].get('data-qui').indexOf('MultiLang') === -1) {
                    if (value === '') {
                        Label = required[i].getParent('label');

                        triggerMessage(
                            required[i],
                            QUILocale.get(lg, 'exception.field.is.invalid', {
                                fieldtitle: Label.getElement('.field-container-item').get('text').trim()
                            })
                        );
                        return Promise.reject('Please fill out all fields correctly');
                    }

                    continue;
                }

                // if json field
                try {
                    value = JSON.decode(value);
                } catch (e) {
                    continue;
                }

                for (l in value) {
                    if (value.hasOwnProperty(l) && value[l] === '') {
                        Label = required[i].getParent('label');

                        triggerMessage(
                            Label.getElement('.quiqqer-inputmultilang-entry input'),
                            QUILocale.get(lg, 'exception.field.is.invalid', {
                                fieldtitle: Label.getElement('.field-container-item').get('text').trim()
                            })
                        );

                        QUI.Controls.getById(required[i].get('data-quiid')).open();


                        return Promise.reject('Please fill out all fields correctly');
                    }
                }
            }


            let category     = Form.getElement('[name="product-category"]').value;
            let productType  = '\\QUI\\ERP\\Products\\Product\\Types\\Product';
            let productTypes = Form.getElements('[name="productType"]').filter(function (Input) {
                return Input.checked;
            });

            if (productTypes.length) {
                productType = productTypes[0].value;
            }

            return Products.createChild(
                category,
                categories,
                fields,
                productType
            );
        }
    });
});
