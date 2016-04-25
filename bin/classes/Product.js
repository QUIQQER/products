/**
 * @module package/quiqqer/products/bin/classes/Product
 * @author www.pcsg.de (henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 *
 * @event onRefresh [this]
 */
define('package/quiqqer/products/bin/classes/Product', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

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
         * Add a field to the product
         *
         * @param {Number}  fieldId
         * @return {Promise}
         */
        addField: function (fieldId) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_addField', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : fieldId
                });
            }.bind(this));
        },

        /**
         * Remove a ownField field from the product
         *
         * @param {Number}  fieldId
         * @return {Promise}
         */
        removeField: function (fieldId) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_removeField', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : fieldId
                });
            }.bind(this));
        },

        /**
         * Set the public status from a product field
         *
         * @param {Number}  fieldId
         * @param {Boolean}  status
         * @return {Promise}
         */
        setPublicStatusFromField: function (fieldId, status) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_setPublicStatusFromField', function () {
                    this.refresh().then(resolve);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : fieldId,
                    status   : status ? 1 : 0
                });
            }.bind(this));
        },

        /**
         * Return the Product-ID
         *
         * @returns {Number|Boolean}
         */
        getId: function () {
            return this.getAttribute('id');
        },

        /**
         * Return the product attributes
         *
         * @returns {Object}
         */
        getAttributes: function () {
            if (!this.$data) {
                this.$data = {};
            }

            this.$data.id = this.getId();

            return this.$data;
        },

        /**
         * Refresh the product data
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise(function (resolve, reject) {

                require([
                    'package/quiqqer/products/bin/Products'
                ], function (Products) {

                    Products.getChild(this.getAttribute('id')).then(function (data) {
                        this.$loaded = true;
                        this.$data   = data;

                        resolve(this);

                        this.fireEvent('refresh', [this]);

                    }.bind(this)).catch(reject);
                }.bind(this));

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
         * Return the field data
         *
         * @param {Number} fieldId - ID of the field
         * @returns {Promise}
         */
        getField: function (fieldId) {
            return new Promise(function (resolve, reject) {

                if (typeof fieldId === 'undefined') {
                    return reject('No field given');
                }

                if (this.$loaded) {
                    var field = this.$data.fields.filter(function (item) {
                        return (item.id == fieldId);
                    });

                    if (field.length) {
                        return resolve(field[0]);
                    }

                    return reject('Field not found');
                }

                this.refresh().then(function () {
                    this.getField(fieldId).then(resolve);
                }.bind(this)).catch(reject);

            }.bind(this));
        },

        /**
         * Return the field value
         *
         * @param {Number} fieldId - ID of the field
         * @returns {Promise}
         */
        getFieldValue: function (fieldId) {
            return this.getField(fieldId).then(function (field) {
                return field.value;
            });
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

                    categories.each(function (value, index) {
                        categories[index] = parseInt(value);
                    });

                    if (self.$data.category && !categories.contains(parseInt(self.$data.category))) {
                        categories.push(parseInt(self.$data.category));
                    }

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
        },

        /**
         * Return the caluclated product price
         *
         * @returns {Promise}
         */
        getPrice: function () {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_product_calc', resolve, {
                    'package' : 'quiqqer/products',
                    productId : this.getId(),
                    attributes: this.getAttributes()
                });
            }.bind(this));
        }
    });
});
