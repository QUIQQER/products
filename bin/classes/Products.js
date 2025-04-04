/**
 * Products handler
 * Create and edit products
 *
 * @module package/quiqqer/products/bin/classes/Products
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/classes/Products', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'package/quiqqer/products/bin/classes/Product'

], function (QUI, QUIDOM, Ajax, Product) {
    'use strict';

    const requestCache = {};

    return new Class({

        Extends: QUIDOM,
        Type: 'package/quiqqer/products/bin/classes/Products',

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
            return new Product({
                id: productId
            });
            /*
            if (!(productId in this.$products)) {
                this.$products[productId] = new Product({
                    id: productId
                });
            }

            return this.$products[productId];
            */
        },

        getProducts: function (productIds) {
            let products = [];

            for (let i = 0, len = productIds.length; i < len; i++) {
                products.push(
                    new Product({
                        id: productIds[i]
                    })
                );
            }

            return products;
        },

        /**
         * Return the product frontend control class of the product
         *
         * @param productId
         * @return {Promise}
         */
        getProductControlClass: function (productId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_frontend_getProductControlClass', resolve, {
                    'package': 'quiqqer/products',
                    productId: productId,
                    onError: reject
                });
            });
        },

        /**
         * Open the product panel
         *
         * @param productId
         */
        openProduct: function (productId) {
            return this.getChild(productId).then(function (attributes) {
                let panel = attributes.typePanel;

                if (panel === '' || typeof panel === 'undefined') {
                    panel = 'package/quiqqer/products/bin/controls/products/Product';
                }

                return new Promise(function (resolve) {
                    let needles = [];
                    needles.push(panel);
                    needles.push('utils/Panels');

                    require(needles, function (Panel, Utils) {
                        const Control = new Panel({
                            productId: productId
                        });

                        Utils.openPanelInTasks(Control).then(function () {
                            resolve(Control);
                        });
                    });
                });
            });
        },

        /**
         * Activate the product
         *
         * @param {Number} productId - Product ID
         * @returns {Promise}
         */
        activate: function (productId) {
            return this.get(productId).activate();
        },

        /**
         * Deactivate the product
         *
         * @param {Number} productId - Product ID
         * @returns {Promise}
         */
        deactivate: function (productId) {
            return this.get(productId).deactivate();
        },

        /**
         * Copy a product
         *
         * @param {Number} productId - Product ID
         * @returns {Promise}
         */
        copy: function (productId) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_copy', resolve, {
                    'package': 'quiqqer/products',
                    productId: productId,
                    onError: reject
                });
            });
        },

        /**
         * Calculate the product price
         *
         * @param {Number} productId - Product ID
         * @param {Object} fields - Fields with values {field: value, field: value ..., field: value}
         * @returns {Promise}
         */
        calcPrice: function (productId, fields) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_calc', resolve, {
                    'package': 'quiqqer/products',
                    productId: productId,
                    fields: JSON.encode(fields),
                    onError: reject
                });
            });
        },

        /**
         * Return the parent media folder for the products
         * @returns {Promise}
         */
        getParentFolder: function () {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_getParentFolder', function (result) {

                    if (!result) {
                        return resolve(false);
                    }

                    require(['Projects'], function (Projects) {
                        const Project = Projects.get(result.project),
                            Media = Project.getMedia();

                        Media.get(result.id).then(resolve).catch(function () {
                            resolve(false);
                        });
                    }, function () {
                        resolve(false);
                    });
                }, {
                    'package': 'quiqqer/products',
                    onError: function () {
                        resolve(false);
                    }
                });
            });
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
                Ajax.get('package_quiqqer_products_ajax_search_backend_executeForGrid', function (result) {
                    resolve(result.data);
                }, {
                    'package': 'quiqqer/products',
                    searchParams: JSON.encode(params),
                    onError: reject
                });
            });
        },

        /**
         * Return the data of a product
         *
         * @param {Number} productId
         * @param {Object} [fields] - optional, {fieldID: fieldValue, fieldID: fieldValue}
         * @param {Boolean} forceCache - disabled frontend cache, force ajax call
         * @returns {Promise}
         */
        getChild: function (productId, fields, forceCache) {
            fields = fields || {};

            if (typeof forceCache === 'undefined') {
                forceCache = false;
            }

            const jsonData = JSON.stringify({productId, fields});
            const key = btoa(String.fromCharCode(...new TextEncoder().encode(jsonData))).slice(0, 32);

            let inAdministration = false;
            if (typeof QUIQQER.inAdministration !== 'undefined' && QUIQQER.inAdministration) {
                inAdministration = !!QUIQQER.inAdministration;
            }

            return new Promise(function (resolve, reject) {
                if (!forceCache && typeof requestCache[key] !== 'undefined' && !inAdministration) {
                    // JSON.parse(JSON.stringify because JS reference usage
                    return resolve(JSON.parse(JSON.stringify(requestCache[key])));
                }

                Ajax.get('package_quiqqer_products_ajax_products_get', (result) => {
                    requestCache[key] = result;

                    // JSON.parse(JSON.stringify because JS reference usage
                    resolve(JSON.parse(JSON.stringify(result)));
                }, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    productId: productId,
                    fields: JSON.decode(fields)
                });
            });
        },

        /**
         * Return the data of a product
         *
         * @param {number} productIds
         * @returns {Promise}
         */
        getChildren: function (productIds) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_getChildren', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    productIds: JSON.encode(productIds)
                });
            });
        },

        /**
         * Return products for a grid
         *
         * @param {Object} params - Grid params
         * @returns {Promise}
         */
        getList: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_list', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    params: JSON.encode(params)
                });
            });
        },

        /**
         * Return all product types
         *
         * @param {Object} params - Grid params
         * @returns {Promise}
         */
        getTypes: function (params) {
            params = params || {};

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_getProductTypes', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    params: JSON.encode(params)
                });
            });
        },

        /**
         * Create a new product
         *
         * @params {int|float} category - id of the main category
         * @params {Array} categories - id list of categories
         * @params {Array} [fields] - product fields
         * @params {String} [productType] - product type
         * @returns {Promise}
         */
        createChild: function (category, categories, fields, productType) {
            fields = fields || {};
            productType = productType || '';

            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_create', function (result) {
                    require(['package/quiqqer/translator/bin/classes/Translator'], function (Translator) {
                        new Translator().refreshLocale().then(function () {
                            resolve(result);
                        });
                    });
                }, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    category: category,
                    categories: JSON.encode(categories),
                    fields: JSON.encode(fields),
                    productType: productType
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
                    onError: reject,
                    productId: productId
                });
            });
        },

        /**
         * Delete multiple products
         *
         * @param {Array} productIds - array of Product-IDs
         * @returns {Promise}
         */
        deleteChildren: function (productIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_deleteChildren', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
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
                    'package': 'quiqqer/products',
                    onError: reject,
                    productId: productId,
                    categories: JSON.encode(categories),
                    categoryId: categoryId,
                    fields: JSON.encode(fields)
                });
            });
        },

        /**
         * Activate multiple products
         *
         * @param {Array} productIds - array of Product-IDs
         * @returns {Promise}
         */
        activateChildren: function (productIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_activate', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    productId: JSON.encode(productIds)
                });
            });
        },

        /**
         * Deactivate multiple products
         *
         * @param {Array} productIds - array of Product-IDs
         * @returns {Promise}
         */
        deactivateChildren: function (productIds) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_deactivate', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    productId: JSON.encode(productIds)
                });
            });
        },

        /**
         * Get list of all packages that belong to the quiqqer/products ecosystem
         * but are not necessarily required.
         *
         * @return {Promise}
         */
        getInstalledProductPackages: function () {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_getInstalledProductPackages', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject
                });
            });
        },

        /**
         * Get total product count
         *
         * @return {Promise}
         */
        getProductCount: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_getCount', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject
                });
            });
        },

        /**
         * Check if product article nos. are auto-generated.
         *
         * @return {Promise}
         */
        isAutoGenerateNextArticleNo: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_settings_isAutoGenerateNextArticleNo', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject
                });
            });
        },

        /**
         * Get data for a product SelectItem
         *
         * @param {Number} productId
         * @return {Promise}
         */
        getDataForSelectItem: function (productId) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_getDataForSelectItem', resolve, {
                    'package': 'quiqqer/products',
                    onError: reject,
                    productId: productId
                });
            });
        },

        /**
         * Opens a product panel
         *
         * @param {number} productId
         */
        openPanel: function (productId) {
            require([
                'package/quiqqer/products/bin/controls/products/Product',
                'utils/Panels'
            ], function (ProductPanel, Panels) {
                const PPanel = new ProductPanel({
                    productId: productId,
                    '#id': productId
                });

                Panels.openPanelInTasks(PPanel);
            });
        },

        /**
         * Add a product id to the visited list
         *
         * @param {number} pid
         */
        addToVisited: function (pid) {
            let visited = this.getVisitedProductIds();

            visited.push(pid);
            visited = visited.unique();

            if (visited.length > 10) {
                visited.shift();
            }

            QUI.Storage.set('quiqqer-products-visited', JSON.encode(visited));
        },

        /**
         * Return the last visited product ids
         *
         * @returns {Array}
         */
        getVisitedProductIds: function () {
            let visited = QUI.Storage.get('quiqqer-products-visited');

            if (!visited) {
                return [];
            }

            try {
                visited = JSON.decode(visited);
            } catch (e) {
                return [];
            }

            if (typeOf(visited) !== 'array') {
                return [];
            }

            visited.reverse();

            return visited;
        }
    });
});
