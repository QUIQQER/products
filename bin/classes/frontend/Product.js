/**
 * @module package/quiqqer/products/bin/classes/frontend/Product
 * @author www.pcsg.de (henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 * @require Locale
 *
 * @event onRefresh [this]
 */
define('package/quiqqer/products/bin/classes/frontend/Product', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function (QUI, QUIDOM, Ajax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Product',

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$data     = null;
            this.$loaded   = false;
            this.$quantity = 1;
            this.$fields   = {};
        },

        /**
         * Set the field value
         *
         * @param {Number} fieldId - field id
         * @param {String} value - field value
         */
        setFieldValue: function (fieldId, value) {
            return new Promise(function (resolve) {
                Ajax.post('package_quiqqer_products_ajax_products_setCustomFieldValue', function (result) {

                    this.$fields[fieldId] = result;
                    this.fireEvent('change', [this]);
                    resolve(this);

                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : fieldId,
                    value    : value
                });
            });
        },

        /**
         * Set multiple field values
         *
         * @param {Object} fields - { fieldId : fieldValue, fieldId : fieldValue}
         * @return {Promise}
         */
        setFieldValues: function (fields) {
            if (!Object.getLength(fields)) {
                return Promise.resolve(this);
            }

            return new Promise(function (resolve) {
                Ajax.post('package_quiqqer_products_ajax_products_frontend_setCustomFieldValues', function (result) {

                    for (var fieldId in result) {
                        if (result.hasOwnProperty(fieldId)) {
                            this.$fields[fieldId] = result[fieldId];
                        }
                    }

                    this.fireEvent('change', [this]);
                    resolve(this);

                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fields   : JSON.encode(fields)
                });

            }.bind(this));
        },

        /**
         * Set the quantity
         *
         * @param {Number} quantity
         * @return {Promise}
         */
        setQuantity: function (quantity) {
            return new Promise(function (resolve) {
                Ajax.post('package_quiqqer_products_ajax_products_setQuantity', function (result) {
                    this.$quantity = parseInt(result);
                    this.fireEvent('change', [this]);

                    resolve(result);

                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    quantity : quantity
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
         * Return the product title
         *
         * @param {Object} [Locale] - optional, QUI Locale object
         * @returns {Promise}
         */
        getTitle: function (Locale) {
            Locale = Locale || QUILocale;

            return this.getFieldValue(4).then(function (field) {
                if (typeOf(field) == 'string') {
                    try {
                        field = JSON.decode(field);
                    } catch (e) {
                        field = {};
                    }
                }

                var title   = '',
                    current = Locale.getCurrent();

                if (current in field) {
                    title = field[current];
                }

                return title;
            });
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

            this.$data.id       = this.getId();
            this.$data.quantity = this.getQuantity();
            this.$data.fields   = this.$fields;

            return this.$data;
        },

        /**
         * Return the status of the product
         *
         * @returns {Promise}
         */
        isActive: function () {
            return new Promise(function (resolve, reject) {

                if (this.$loaded) {
                    return resolve(this.$data.active ? true : false);
                }

                this.refresh().then(function () {
                    resolve(this.$data.active ? true : false);
                }.bind(this)).catch(reject);

            }.bind(this));
        },

        /**
         * Refresh the product data
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise(function (resolve, reject) {

                if (typeof this.$data === 'undefined' || !this.$data) {
                    this.$data = {};
                }

                if (!this.$data.hasOwnProperty('fields')) {
                    this.$data.fields = {};
                }

                require([
                    'package/quiqqer/products/bin/Products'
                ], function (Products) {
                    Products.getChild(
                        this.getAttribute('id'),
                        this.$data.fields
                    ).then(function (data) {
                        this.$loaded = true;
                        this.$data   = data;

                        this.$data.fields.each(function (Field) {
                            if (typeof this.$fields[Field.id] !== 'undefined') {
                                Field.value = this.$fields[Field.id];
                            }
                        }.bind(this));

                        resolve(this);

                        this.fireEvent('refresh', [this]);
                    }.bind(this)).catch(reject);

                }.bind(this));

            }.bind(this));
        },

        /**
         * Return the fields of the frontend product
         *
         * @returns {Object}
         */
        getFields: function () {
            return this.$fields;
        },

        /**
         * Return the field data
         *
         * @param {Number} fieldId - ID of the field
         * @returns {Promise}
         */
        getField: function (fieldId) {
            return new Promise(function (resolve, reject) {
                if (typeof this.$fields[fieldId] !== 'undefined') {
                    resolve(this.$fields[fieldId]);
                    return;
                }

                reject();
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
                    var categories = self.$data.categories.split(',').filter(function (entry) {
                        return entry !== '';
                    });


                    categories.each(function (value, index) {
                        categories[index] = parseInt(value);
                    });

                    if (self.$data.category !== 0 &&
                        self.$data.category && !categories.contains(parseInt(self.$data.category))) {
                        categories.push(parseInt(self.$data.category));
                    }

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
         * @param {Number} [quantity] - price of a wanted quantity
         * @returns {Promise}
         */
        getPrice: function (quantity) {
            quantity = quantity || this.getQuantity();

            var fields    = this.getFields(),
                fieldList = [];

            for (var fieldId in fields) {
                if (fields.hasOwnProperty(fieldId)) {
                    fieldList.push({
                        fieldId: fieldId,
                        value  : fields[fieldId]
                    });
                }
            }

            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_calc', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fields   : JSON.encode(fieldList),
                    quantity : quantity
                });
            }.bind(this));
        },

        /**
         * Return the quantity
         *
         * @returns {Number}
         */
        getQuantity: function () {
            return this.$quantity;
        }
    });
});
