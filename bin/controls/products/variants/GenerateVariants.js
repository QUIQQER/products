/**
 * @module package/quiqqer/products/bin/controls/products/GenerateVariants
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad
 * @event onChange
 */
define('package/quiqqer/products/bin/controls/products/variants/GenerateVariants', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/variants/GenerateVariants.List.html',
    'text!package/quiqqer/products/bin/controls/products/variants/GenerateVariants.FieldSelect.html',
    'css!package/quiqqer/products/bin/controls/products/variants/GenerateVariants.css'

], function (QUI, QUIControl, Grid, QUIAjax, QUILocale, Mustache, template, templateFieldSelect) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/GenerateVariants',

        Binds: [
            '$onInject',
            '$togglePAL',
            'refreshCalc'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid        = null;
            this.$CalcDisplay = null;
            this.$isOnEnd     = false;

            this.$ButtonUsePAL = null;

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

        //region action

        /**
         * Next Step
         *
         * @return {Promise}
         */
        next: function () {
            var self = this;

            var FieldSelect = self.getElm().getElement('.quiqqer-products-variant-generate-fieldSelect'),
                checkboxes  = FieldSelect.getElements('input:checked');

            if (!checkboxes.length) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                var children = self.getElm().getChildren();

                moofx(children).animate({
                    left   : -50,
                    opacity: 0
                }, {
                    callback: function () {
                        children.setStyles({
                            display: 'none'
                        });

                        self.renderFieldValueSelect().then(function () {
                            self.fireEvent('change', [self]);
                            resolve();
                        });
                    }
                });
            });
        },

        /**
         * Reset the generate variants and shows the first step
         *
         * @return {Promise}
         */
        reset: function () {
            this.$isOnEnd = false;

            var self        = this;
            var children    = this.getElm().getChildren();
            var FieldSelect = this.getElm().getElement('.quiqqer-products-variant-generate-fieldSelect');

            return new Promise(function (resolve) {
                moofx(children).animate({
                    left   : -50,
                    opacity: 0
                }, {
                    duration: 300,
                    callback: function () {
                        self.fireEvent('change', [self]);

                        children.setStyle('display', 'none');

                        FieldSelect.setStyle('opacity', 0);
                        FieldSelect.setStyle('left', -50);
                        FieldSelect.setStyle('display', null);

                        moofx(FieldSelect).animate({
                            opacity: 1,
                            left   : 0
                        }, {
                            duration: 300,
                            callback: resolve
                        });
                    }
                });
            });
        },

        /**
         * Saves the editable fields to the product
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
                    'package'     : 'quiqqer/products',
                    productId     : self.getAttribute('productId'),
                    fields        : JSON.encode(fields),
                    generationType: self.getElm().getElement('[name="generation-type"]').value,
                    onError       : reject
                });
            });
        },

        /**
         * Is the generation wizard at the end?
         *
         * @return {boolean}
         */
        isOnEndStep: function () {
            return this.$isOnEnd;
        },

        //endregion

        //region rendering

        /**
         * List all fields
         * The user can choose between these fields which he wants to use for variant generation.
         *
         * @return {Promise}
         */
        renderFieldSelect: function () {
            var self = this;

            var Container = new Element('div', {
                'class': 'quiqqer-products-variant-generate-fieldSelect'
            }).inject(this.$Elm);

            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getAvailableVariantFields', function (fields) {
                    var attributeGroupList = fields.filter(function (field) {
                        return field.type === "AttributeGroup";
                    });

                    var productAttributeList = fields.filter(function (field) {
                        return field.type === "ProductAttributeList";
                    });

                    Container.set('html', Mustache.render(templateFieldSelect, {
                        attributeGroupList        : attributeGroupList,
                        titleAttributeGroupList   : QUILocale.get(lg, 'variants.generating.attributeGroupList'),
                        descGenerationType        : QUILocale.get(lg, 'variants.generating.type.description'),
                        textCreateOnlyNewOne      : QUILocale.get(lg, 'variants.generating.type.createOnlyNewOne'),
                        textDeleteCurrent         : QUILocale.get(lg, 'variants.generating.type.deleteCurrent'),
                        descAttributeGroupList    : QUILocale.get(lg, 'variants.generating.selectFields.description'),
                        textProductAttributeButton: QUILocale.get(lg, 'variants.generating.productAttributeButton'),
                        productAttributeList      : productAttributeList,
                        titleProductAttributeList : QUILocale.get(lg, 'variants.generating.productAttributeList'),
                        descProductAttributeList  : QUILocale.get(lg, 'variants.generating.selectFields.productAttributes.description')
                    }));

                    self.$ButtonUsePAL = Container.getElement('.product-attribute-list button');
                    self.$ButtonUsePAL.addEvent('click', self.$togglePAL);

                    resolve();
                }, {
                    'package': 'quiqqer/products'
                });
            });
        },

        /**
         * Render the selected fields for the generation
         *
         * @return {Promise}
         */
        renderFieldValueSelect: function () {
            var self = this;

            this.$isOnEnd = true;

            return new Promise(function (resolve) {
                var FieldSelect = self.getElm().getElement('.quiqqer-products-variant-generate-fieldSelect');
                var checkboxes  = FieldSelect.getElements('input:checked');

                var fields = checkboxes.map(function (Input) {
                    return parseInt(Input.value);
                });

                var Container = new Element('div', {
                    'class': 'quiqqer-products-variant-generate-generation',
                    styles : {
                        left   : -50,
                        opacity: 0
                    }
                }).inject(self.getElm());

                self.$CalcDisplay = new Element('div', {
                    'class': 'quiqqer-products-variant-generate-calcDisplay',
                    html   : QUILocale.get(lg, 'variants.generating.window.calc', {
                        count: 0
                    })
                }).inject(Container);


                QUIAjax.get('package_quiqqer_products_ajax_fields_getFields', function (fields) {
                    require(['package/quiqqer/products/bin/utils/Fields'], function (FieldUtils) {
                        FieldUtils.renderVariantFieldSelect(fields).then(function (Node) {
                            Node.inject(Container);
                            Node.getElements('input').addEvent('change', self.refreshCalc);

                            self.refreshCalc();

                            moofx(Container).animate({
                                left   : 0,
                                opacity: 1
                            }, {
                                duration: 250,
                                callback: function () {
                                    self.resize();
                                    resolve();
                                }
                            });
                        });
                    });
                }, {
                    'package': 'quiqqer/products',
                    fieldIds : JSON.encode(fields)
                });
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.renderFieldSelect().then(function () {
                this.fireEvent('load', [this]);
            }.bind(this));
        },

        //endregion

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
         * refresh the calc display
         */
        refreshCalc: function () {
            var count  = 0,
                tables = this.getElm().getElements('.quiqqer-products-variant-generate-generation table');

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
        },

        /**
         * Toggle the product attribute list selection
         */
        $togglePAL: function () {
            var Section   = this.getElm().getElement('.product-attribute-list');
            var Button    = Section.getElement('button');
            var Container = Section.getElement('.product-attribute-list-container');

            // open
            if (Container.getStyle('display') === 'none') {
                Container.setStyle('opacity', 0);
                Container.setStyle('height', 0);
                Container.setStyle('display', 'inline-block');

                moofx(Container).animate({
                    height : Container.getScrollSize().y,
                    opacity: 1
                }, {
                    duration: 300,
                    callback: function () {
                        Button.getElement('.fa').removeClass('fa-plus').addClass('fa-minus');
                    }
                });

                return;
            }

            // close
            moofx(Container).animate({
                height : 0,
                opacity: 0
            }, {
                duration: 300,
                callback: function () {
                    Container.setStyle('display', 'none');
                    Button.getElement('.fa').removeClass('fa-minus').addClass('fa-plus');
                }
            });
        }
    });
});
