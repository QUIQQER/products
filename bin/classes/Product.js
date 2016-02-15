/**
 * @module package/quiqqer/products/bin/classes/Product
 * @author www.pcsg.de (henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require package/quiqqer/products/bin/classes/Products
 */
define('package/quiqqer/products/bin/classes/Product', [

    'qui/QUI',
    'qui/classes/DOM',
    'package/quiqqer/products/bin/classes/Products'

], function (QUI, QUIDOM, ProductHandler) {
    "use strict";

    var Handler = new ProductHandler();

    return new Class({
        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Product',

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$data   = null;
            this.$loaded = false;
        },

        /**
         * Refresh the product data
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise(function (resolve, reject) {

                Handler.getChild(this.getAttribute('id')).then(function (data) {

                    this.$loaded = true;
                    this.$data   = data;

                    resolve(this);

                }.bind(this)).catch(reject);

            }.bind(this));
        },

        /**
         * Return the fields of the product
         *
         * @returns {Promise}
         */
        getFields: function () {
            return new Promise(function (resolve, reject) {

                if (this.$loaded) {
                    return resolve(this.$data.fields);
                }

                this.refresh().then(function () {
                    resolve(this.$data.fields);
                }.bind(this)).catch(reject);

            }.bind(this));
        },

        /**
         * Return the categories of the product
         *
         * @returns {Promise}
         */
        getCategories: function () {
            var self = this;

            return new Promise(function (resolve, reject) {

                if (self.$loaded) {
                    var categories = self.$data.categories.split(',');

                    categories.push(parseInt(self.$data.category));
                    categories = categories.filter(function (item) {
                        return item !== '';
                    });

                    return resolve(categories);
                }

                self.refresh().then(function () {
                    self.getCategories().then(resolve);
                }).catch(reject);

            });
        },

        /**
         * Return the main category of the product
         *
         * @returns {Promise}
         */
        getCategory: function () {
            return new Promise(function (resolve, reject) {

                if (this.$loaded) {
                    return resolve(this.$data.category);
                }

                this.refresh().then(function () {
                    resolve(this.$data.category);
                }.bind(this)).catch(reject);

            }.bind(this));
        }
    });
});
