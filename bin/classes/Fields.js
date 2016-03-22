/**
 * Fields handler
 * Create and edit fields
 *
 * @module package/quiqqer/products/bin/classes/Fields
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
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

        FIELD_PRICE       : 1,
        FIELD_TAX         : 2,
        FIELD_PRODUCT_NO  : 3,
        FIELD_TITLE       : 4,
        FIELD_SHORT_DESC  : 5,
        FIELD_CONTENT     : 6,
        FIELD_SUPPLIER    : 7,
        FIELD_MANUFACTURER: 8,
        FIELD_IMAGE       : 9,
        FIELD_FOLDER      : 10,

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
