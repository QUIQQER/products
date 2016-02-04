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

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_fields_search', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fields   : JSON.encode(fields),
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
