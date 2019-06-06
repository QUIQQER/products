/**
 * Field utils
 * Helper for fields
 *
 * @module package/quiqqer/products/bin/utils/Fields
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/utils/Fields', {

    /**
     * Sort a field array
     *
     * @param {Array} fields
     * @return {Array}
     */
    sortFields: function (fields) {
        "use strict";

        return fields.clean().sort(function (a, b) {
            var ap = parseInt(a.priority);
            var bp = parseInt(b.priority);

            if (ap === 0) {
                return 1;
            }

            if (bp === 0) {
                return -1;
            }

            if (ap < bp) {
                return -1;
            }

            if (ap > bp) {
                return 1;
            }

            return 0;
        });
    },

    /**
     * Can the field used as a detail field?
     * JavaScript equivalent package/quiqqer/products/bin/utils/Fields
     *
     * @param {string|number} field - Field Type or Field-Id
     * @returns {Promise} (bool)
     */
    canUsedAsDetailField: function (field) {
        "use strict";
        return new Promise(function (resolve) {
            require(['package/quiqqer/products/bin/Fields'], function (FieldHandler) {
                var fieldId = parseInt(field);

                if (fieldId === FieldHandler.FIELD_TITLE ||
                    fieldId === FieldHandler.FIELD_CONTENT ||
                    fieldId === FieldHandler.FIELD_SHORT_DESC ||
                    fieldId === FieldHandler.FIELD_PRICE ||
                    fieldId === FieldHandler.FIELD_IMAGE
                ) {
                    return resolve(false);
                }

                if (field === FieldHandler.TYPE_ATTRIBUTE_LIST ||
                    field === FieldHandler.TYPE_FOLDER ||
                    field === FieldHandler.TYPE_PRODCUCTS ||
                    field === FieldHandler.TYPE_TEXTAREA_MULTI_LANG
                ) {
                    return resolve(false);
                }

                return resolve(true);
            });
        });
    },

    /**
     *
     * @param fields
     */
    renderVariantFieldSelect: function (fields) {
        "use strict";

        return new Promise(function (resolve) {
            require([
                'Mustache',
                'Locale',
                'text!package/quiqqer/products/bin/utils/Fields.GenerateVariants.html',
                'css!package/quiqqer/products/bin/utils/Fields.GenerateVariants.css'
            ], function (Mustache, QUILocale, template) {
                var i, len, field, values;
                var lg = 'quiqqer/products';

                var Container = new Element('div', {
                        'class': 'quiqqer-products-variant-generate-tableBody'
                    }),
                    current   = QUILocale.getCurrent();

                var fieldList            = [],
                    productAttributeList = [];

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
                    field = fields[i];

                    if (field.type === "ProductAttributeList") {
                        productAttributeList.push({
                            fieldId: field.id,
                            title  : field.title
                        });

                        continue;
                    }

                    values = field.options.entries.map(filterValues);

                    fieldList.push({
                        fieldId: field.id,
                        title  : field.title,
                        values : values
                    });
                }

                Container.set('html', Mustache.render(template, {
                    fields              : fieldList,
                    productAttributeList: productAttributeList,
                    message_no_values   : QUILocale.get(lg, 'variants.generating.window.message.no.values')
                }));

                resolve(Container);
            });
        });
    }
});
