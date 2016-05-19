/**
 * Search handler
 * Search products
 *
 * @module package/quiqqer/products/bin/classes/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */
define('package/quiqqer/products/bin/classes/Search', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Search',

        /**
         * Search products
         *
         * @param {Number} siteid
         * @param {Object} project - {name: '', lang: ''}
         * @param {Object} searchParams - search query params
         * @returns {Promise}
         */
        search: function (siteid, project, searchParams) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_search_frontend_execute', resolve, {
                    'package'   : 'quiqqer/products',
                    siteId      : siteid,
                    project     : JSON.encode(project),
                    searchParams: JSON.encode(searchParams)
                });
            }.bind(this));
        },

        /**
         * Return the field data
         *
         * @param {Number} siteid
         * @param {Object} project - {name: '', lang: ''}
         * @returns {*}
         */
        getFieldData: function (siteid, project) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_search_frontend_getSearchFieldData', resolve, {
                    'package': 'quiqqer/products',
                    siteId   : siteid,
                    project  : JSON.encode(project)
                });
            }.bind(this));
        }
    });
});
