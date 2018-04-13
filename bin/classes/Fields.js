/**
 * Fields handler
 * Create and edit fields
 *
 * @module package/quiqqer/products/bin/classes/Fields
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/classes/Fields', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Fields',

        /**
         * Fields
         */
        FIELD_PRICE           : 1,
        FIELD_VAT             : 2,
        FIELD_PRODUCT_NO      : 3,
        FIELD_TITLE           : 4,
        FIELD_SHORT_DESC      : 5,
        FIELD_CONTENT         : 6,
        FIELD_SUPPLIER        : 7,
        FIELD_MANUFACTURER    : 8,
        FIELD_IMAGE           : 9,
        FIELD_FOLDER          : 10,
        FIELD_WORKING_TITLE   : 11,
        FIELD_KEYWORDS        : 13,
        FIELD_EQUIPMENT       : 14,
        FIELD_SIMILAR_PRODUCTS: 15,

        /**
         * Types
         */
        TYPE_BOOL               : 'BoolType',
        TYPE_DATE               : 'Date',
        TYPE_FLOAT              : 'FloatType',
        TYPE_FOLDER             : 'Folder',
        TYPE_GROUP_LIST         : 'GroupList',
        TYPE_IMAGE              : 'Image',
        TYPE_INPUT              : 'Input',
        TYPE_INPUT_MULTI_LANG   : 'InputMultiLang',
        TYPE_INT                : 'IntType',
        TYPE_PRICE              : 'Price',
        TYPE_PRICE_BY_QUANTITY  : 'PriceByQuantity',
        TYPE_ATTRIBUTE_LIST     : 'ProductAttributeList',
        TYPE_TEXTAREA           : 'Textarea',
        TYPE_TEXTAREA_MULTI_LANG: 'TextareaMultiLang',
        TYPE_URL                : 'Url',
        TYPE_VAT                : 'Vat',
        TYPE_TAX                : 'Tax',
        TYPE_PRODCUCTS          : 'Products',

        /**
         * product array changed types
         */
        PRODUCT_ARRAY_CHANGED  : 'pac', // product array has changed
        PRODUCT_ARRAY_UNCHANGED: 'pau', // product array hasn't changed

        /**
         * Return the allowed field attributes
         *
         * @returns {Array}
         */
        getChildAttributes: function () {
            return [
                'name',
                'type',
                'search_type',
                'prefix',
                'suffix',
                'priority',
                'standardField',
                'systemField',
                'requiredField'
            ];
        },

        /**
         * Search fields
         *
         * @param {Object} [params] - query params
         * @param {Object} [fields] - field list
         * @returns {Promise}
         */
        search: function (params, fields) {
            params = params || {};
            fields = fields || {};

            // check if fields are allowed
            var i, len, field;

            var fieldList = {},
                allowed   = this.getChildAttributes();

            for (i = 0, len = allowed.length; i < len; i++) {
                field = allowed[i];

                if (field in fields) {
                    fieldList[field] = fields[field];
                }
            }

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_search', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fields   : JSON.encode(fieldList),
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Return a field
         *
         * @param {number} fieldId
         * @returns {Promise}
         */
        getChild: function (fieldId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_get', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fieldId  : fieldId
                });
            });
        },

        /**
         * Return multiple fields
         *
         * @param {array} fieldIds - list of field IDs
         * @returns {Promise}
         */
        getChildren: function (fieldIds) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getChildren', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fieldIds : JSON.encode(fieldIds)
                });
            });
        },

        /**
         * Return fields for a grid
         *
         * @param {String} params - Grid params
         * @returns {Promise}
         */
        getList: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_list', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Return all field types
         *
         * @returns {Promise}
         */
        getFieldTypes: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getFieldTypes', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         * Return search types for specific field
         *
         * @param {number} fieldId
         * @returns {Promise}
         */
        getSearchTypesForField: function (fieldId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getSearchTypesForField', resolve, {
                    'package': 'quiqqer/products',
                    fieldId  : fieldId,
                    onError  : reject
                });
            });
        },

        /**
         * Return all field types
         *
         * @returns {Promise}
         */
        getSystemFields: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getSystemFields', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         * Return all field types
         *
         * @returns {Promise}
         */
        getStandardFields: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getStandardFields', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         * Return all field types
         *
         * @returns {Promise}
         */
        getPublicFields: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getPublicFields', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         * Return the extra settings for special field types
         *
         * @returns {Promise}
         */
        getFieldTypeSettings: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_getFieldTypeSettings', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         * Create a new field
         *
         * @params {Array} [params] - field attributes
         * @returns {Promise}
         */
        createChild: function (params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_fields_create', function (result) {

                    require([
                        'package/quiqqer/translator/bin/classes/Translator'
                    ], function (Translator) {
                        new Translator().refreshLocale().then(function () {
                            resolve(result);
                        });
                    });

                }, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Delete a field
         *
         * @param {Number} fieldId - Field-ID
         * @returns {Promise}
         */
        deleteChild: function (fieldId) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_fields_deleteChild', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fieldId  : fieldId
                });
            });
        },

        /**
         * Delete multible fields
         *
         * @param {Array} fieldIds - array of Field-IDs
         * @returns {Promise}
         */
        deleteChildren: function (fieldIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_fields_deleteChildren', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fieldIds : JSON.encode(fieldIds)
                });
            });
        },

        /**
         * Save / Update a field
         *
         * @param {Number} fieldId
         * @param {Object} params - Field attributes
         */
        updateChild: function (fieldId, params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_fields_update', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fieldId  : fieldId,
                    params   : JSON.encode(params)
                });
            });
        }
    });
});
