/**
 * Category handler
 * Create and edit categories
 *
 * @module package/quiqqer/products/bin/classes/Categories
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */
define('package/quiqqer/products/bin/classes/Categories', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Categories',

        /**
         * Search categories
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
         * @param {number} categoryId
         * @returns {Promise}
         */
        getChild: function (categoryId) {
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
         * Create a new category
         *
         * @params {Array} [params] - category attributes
         * @returns {Promise}
         */
        createChild: function (params) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Delete a category
         *
         * @param {Number} categoryId - Category-ID
         * @returns {Promise}
         */
        deleteChild: function (categoryId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Delete multible categories
         *
         * @param {Array} categoryId - array of Category-IDs
         * @returns {Promise}
         */
        deleteChildren: function (categoryId) {
            return new Promise(function (resolve, reject) {

            });
        },

        /**
         * Save a category
         *
         * @param {Number} categoryId
         * @param {Object} data - Category attributes
         */
        update: function (categoryId, data) {
            return new Promise(function (resolve, reject) {

            });
        }
    });
});
