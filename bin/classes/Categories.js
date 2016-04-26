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
         * @param {Object} [fields] - field values
         * @param {Object} [params] - query params
         * @returns {Promise}
         */
        search: function (fields, params) {
            params = params || {};
            fields = fields || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_search', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    fields   : JSON.encode(fields),
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Return the children categories
         *
         * @param {Number} parentId
         * @returns {Promise}
         */
        getChildren: function (parentId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_getChildren', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    parentId : parentId
                });
            });
        },

        /**
         * Return a category
         *
         * @param {number} categoryId
         * @returns {Promise}
         */
        getChild: function (categoryId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_get', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         *
         * @param categoryId
         * @returns {*}
         */
        getPath: function (categoryId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_path', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         * Return categories for a grid
         *
         * @param {Object} params - Grid params
         * @returns {Promise}
         */
        getList: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_list', resolve, {
                    'package': 'quiqqer/products',
                    onError  : reject,
                    params   : JSON.encode(params)
                });
            });
        },

        /**
         * Return all fields from the categories
         *
         * @param {Array} categoryIds - List of category IDs
         * @returns {Promise}
         */
        getFields: function (categoryIds) {
            categoryIds = categoryIds || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_getFields', resolve, {
                    'package'  : 'quiqqer/products',
                    onError    : reject,
                    categoryIds: JSON.encode(categoryIds)
                });
            });
        },

        /**
         * Return all sites from the category
         *
         * @param {Number} categoryId - Category-ID
         * @returns {Promise}
         */
        getSites: function (categoryId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_getSites', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         * Return stats information from the category
         *
         * @param {Number} categoryId - Category-ID
         * @returns {Promise}
         */
        getInformation: function (categoryId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_getInformation', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         * Create a new category
         *
         * @params {Number} parentId - Parent-ID
         * @params {Array} [params] - category attributes
         * @returns {Promise}
         */
        createChild: function (parentId, params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_create', function (result) {

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
                    params   : JSON.encode(params),
                    parentId : parentId
                });
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
                Ajax.post('package_quiqqer_products_ajax_categories_deleteChild', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         * Delete multible categories
         *
         * @param {Array} categoryIds - array of Category-IDs
         * @returns {Promise}
         */
        deleteChildren: function (categoryIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_deleteChildren', resolve, {
                    'package'  : 'quiqqer/products',
                    onError    : reject,
                    categoryIds: JSON.encode(categoryIds)
                });
            });
        },

        /**
         * Save a category
         *
         * @param {Number} categoryId - Category-ID
         * @param {Object} params - Category attributes
         * @param {Boolean} [updateProductFields] - Update all fields from the products, too
         * @returns {Promise}
         */
        updateChild: function (categoryId, params, updateProductFields) {
            updateProductFields = updateProductFields || false;

            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_update', resolve, {
                    'package'          : 'quiqqer/products',
                    onError            : reject,
                    categoryId         : categoryId,
                    params             : JSON.encode(params),
                    updateProductFields: updateProductFields
                });
            });
        },

        /**
         * Update all product fields with the category id fields
         *
         * @param {Number} categoryId
         * @returns {Promise}
         */
        setFieldsToAllProducts: function (categoryId) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_setFieldsToAllProducts', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId
                });
            });
        },

        /**
         * Set a new parent ID to a category
         *
         * @param {Number}  categoryId
         * @param {Number}  parentId
         * @return {Promise}
         */
        setParent: function (categoryId, parentId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_categories_setParent', resolve, {
                    'package' : 'quiqqer/products',
                    categoryId: categoryId,
                    parentId  : parentId,
                    onError   : reject
                });
            }.bind(this));
        },

        /**
         * Return the products form a category for a grid
         *
         * @param {Number} categoryId - Category-ID
         * @param {Object} params - Category attributes
         * @returns {Promise}
         */
        getProductList: function (categoryId, params) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_getProductsFromCategory', resolve, {
                    'package' : 'quiqqer/products',
                    onError   : reject,
                    categoryId: categoryId,
                    params    : JSON.encode(params)
                });
            });
        }
    });
});
