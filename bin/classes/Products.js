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
 * @require package/quiqqer/products/bin/classes/Product
 */
define('package/quiqqer/products/bin/classes/Products', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'package/quiqqer/products/bin/classes/Product'

], function (QUI, QUIDOM, Ajax, Product) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Products',

        initialize: function () {
            this.$products = {};
        },

        /**
         * Return a product
         *
         * @param {Number} productId - Product ID
         * @return {Object} package/quiqqer/products/bin/classes/Product
         */
        get: function (productId) {
            if (!(productId in this.$products)) {
                this.$products[productId] = new Product({
                    id: productId
                });
            }

            return this.$products[productId];
        },

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
                Ajax.get('package_quiqqer_products_ajax_products_get', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    productId: productId
                });
            });
        },

        /**
         * Return products for a grid
         *
         * @param {String} params - Grid params
         * @returns {Promise}
         */
        getList: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_list', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Create a new product
         *
         * @params {Array} categories - id list of categories
         * @params {Array} [fields] - product fields
         * @returns {Promise}
         */
        createChild: function (categories, fields) {
            fields = fields || {};

            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_create', function (result) {

                    require([
                        'package/quiqqer/translator/bin/classes/Translator'
                    ], function (Translator) {
                        new Translator().refreshLocale().then(function () {
                            resolve(result);
                        });
                    });

                }, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categories: JSON.encode(categories),
                    fields    : JSON.encode(fields)
                });
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
                Ajax.post('package_quiqqer_products_ajax_products_deleteChild', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    productId: productId
                });
            });
        },

        /**
         * Delete multible products
         *
         * @param {Array} productIds - array of Product-IDs
         * @returns {Promise}
         */
        deleteChildren: function (productIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_deleteChildren', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    productIds: JSON.encode(productIds)
                });
            });
        },

        /**
         * Save a product
         *
         * @param {Number} productId
         * @param {Object} categories - Product categories
         * @param {Number} categoryId - ID of the main category
         * @param {Object} fields - Product field data {field-ID : value, field-ID : value}
         * @return {Promise}
         */
        updateChild: function (productId, categories, categoryId, fields) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_update', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    productId : productId,
                    categories: JSON.encode(categories),
                    categoryId: categoryId,
                    fields    : JSON.encode(fields)
                });
            });
        }
    });
});
