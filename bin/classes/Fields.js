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

            });
        },

        /**
         * Return all fields
         *
         * @returns {Promise}
         */
        getList: function () {
            return this.search();
        },

        /**
         * Create a new field
         *
         * @params {Array} [params] - field attributes
         * @returns {Promise}
         */
        createChild: function (params) {
            return new Promise(function (resolve, reject) {

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

            });
        },

        /**
         * Delete multible fields
         *
         * @param {Array} fieldId - array of Field-IDs
         * @returns {Promise}
         */
        deleteChildren: function (fieldId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Save a field
         *
         * @param {Number} fieldId
         * @param {Object} data - Field attributes
         */
        update: function (fieldId, data) {
            return new Promise(function (resolve, reject) {

            });
        }
    });
});
