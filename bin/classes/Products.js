/**
 * Products handler
 * Create and edit products
 *
 * @module package/quiqqer/products/bin/classes/Products
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */
define('package/quiqqer/products/bin/classes/Products', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Products',

        /**
         * Search products
         *
         * @param {Object} [params] - query params
         * @returns {Promise}
         */
        search: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {

            });
        },

        /**
         *
         * @param {number} productId
         * @returns {Promise}
         */
        getChild: function (productId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         *
         * @returns {Promise}
         */
        getList: function () {
            return this.search();
        },

        /**
         * Create a new product
         *
         * @params {Array} [params] - product attributes
         * @returns {Promise}
         */
        createChild: function (params) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Delete a product
         *
         * @param {Number} productId - Product-ID
         * @returns {Promise}
         */
        deleteChild: function (productId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Delete multible products
         *
         * @param {Array} productId - array of Product-IDs
         * @returns {Promise}
         */
        deleteChildren: function (productId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Save a product
         *
         * @param {Number} productId
         * @param {Object} data - Product attributes
         */
        update: function (productId, data) {
            return new Promise(function (resolve, reject) {

            });
        }
    });
});
