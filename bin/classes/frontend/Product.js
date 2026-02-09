/**
 * @event onRefresh [this]
 */
define('package/quiqqer/products/bin/classes/frontend/Product', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function (QUI, QUIDOM, Ajax, QUILocale) {
    "use strict";

    const generateFieldHash = (fieldList) => {
        let hash = 0;
        for (const {fieldId, value} of fieldList) {
            const str = `${fieldId}:${value}`;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = (hash << 5) - hash + char;
                hash |= 0; // Convert to 32bit integer
            }
        }
        return hash.toString();
    };

    return new Class({
        Extends: QUIDOM,
        Type: 'package/quiqqer/products/bin/classes/Product',

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$fieldPriceHash = null;
            this.$currentPrice = null;
            this.$priceRequest = null;

            this.$data = null;
            this.$loaded = false;
            this.$quantity = 1;
            this.$fields = {};
        },

        /**
         * Set the field value
         *
         * @param {Number} fieldId - field id
         * @param {String} value - field value
         */
        setFieldValue: function (fieldId, value) {
            return new Promise((resolve, reject) => {
                Ajax.post('package_quiqqer_products_ajax_products_setCustomFieldValue', (result) => {
                    this.$fields[fieldId] = result;
                    this.fireEvent('change', [this]);
                    resolve(this);
                }, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId: fieldId,
                    value: value,
                    onError: reject
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

            return new Promise((resolve, reject) => {
                Ajax.post('package_quiqqer_products_ajax_products_frontend_setCustomFieldValues', (result) => {
                    for (let fieldId in result) {
                        if (result.hasOwnProperty(fieldId)) {
                            this.$fields[fieldId] = result[fieldId];
                        }
                    }

                    this.fireEvent('change', [this]);
                    resolve(this);
                }, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fields: JSON.encode(fields),
                    onError: reject
                });
            });
        },

        /**
         * Set the quantity
         *
         * @param {Number} quantity
         * @return {Promise}
         */
        setQuantity: function (quantity) {
            if (this.$quantity === quantity) {
                return Promise.resolve(this.$quantity);
            }

            return new Promise((resolve, reject) => {
                Ajax.post('package_quiqqer_products_ajax_products_setQuantity', (result) => {
                    this.$currentPrice = null;
                    this.$quantity = parseInt(result);
                    this.fireEvent('change', [this]);

                    resolve(result);
                }, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    quantity: quantity,
                    onError: reject
                });
            });
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
                if (typeOf(field) === 'string') {
                    try {
                        field = JSON.decode(field);
                    } catch (e) {
                        field = {};
                    }
                }

                let title = '',
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

            this.$data.id = this.getId();
            this.$data.quantity = this.getQuantity();
            this.$data.fields = this.$fields;

            return this.$data;
        },

        /**
         * Return the status of the product
         *
         * @returns {Promise}
         */
        isActive: function () {
            return new Promise((resolve, reject) => {
                if (this.$loaded) {
                    return resolve(!!this.$data.active);
                }

                this.refresh().then(() => {
                    resolve(!!this.$data.active);
                }).catch(reject);
            });
        },

        /**
         * Refresh the product data
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise((resolve, reject) => {
                if (typeof this.$data === 'undefined' || !this.$data) {
                    this.$data = {};
                }

                if (!this.$data.hasOwnProperty('fields')) {
                    this.$data.fields = {};
                }

                require(['package/quiqqer/products/bin/Products'], (Products) => {
                    Products.getChild(
                        this.getAttribute('id'),
                        this.$data.fields
                    ).then((data) => {
                        this.$loaded = true;
                        this.$data = data;

                        this.$data.fields.each((Field) => {
                            if (typeof this.$fields[Field.id] !== 'undefined') {
                                Field.value = this.$fields[Field.id];
                            }
                        });

                        resolve(this);

                        this.fireEvent('refresh', [this]);
                    }).catch(reject);
                });
            });
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
            return new Promise((resolve, reject) => {
                if (typeof this.$fields[fieldId] !== 'undefined') {
                    resolve(this.$fields[fieldId]);
                    return;
                }

                reject();
            });
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
            return new Promise((resolve, reject) => {
                if (this.$loaded) {
                    let categories = this.$data.categories.split(',').filter(function (entry) {
                        return entry !== '';
                    });


                    categories.each(function (value, index) {
                        categories[index] = parseInt(value);
                    });

                    if (this.$data.category !== 0 &&
                        this.$data.category && !categories.contains(parseInt(this.$data.category))) {
                        categories.push(parseInt(this.$data.category));
                    }

                    return resolve(categories);
                }

                this.refresh().then(() => {
                    this.getCategories().then(resolve);
                }).catch(reject);
            });
        },

        /**
         * Return the main category of the product
         *
         * @returns {Promise}
         */
        getCategory: function () {
            return new Promise((resolve, reject) => {
                if (this.$loaded) {
                    return resolve(this.$data.category);
                }

                this.refresh().then(() => {
                    resolve(this.$data.category);
                }).catch(reject);
            });
        },

        /**
         * Return the caluclated product price
         *
         * @param {Number} [quantity] - price of a wanted quantity
         * @returns {Promise}
         */
        getPrice: function (quantity) {
            quantity = quantity || this.getQuantity();

            const fields = this.getFields(),
                fieldList = [];

            for (let fieldId in fields) {
                if (fields.hasOwnProperty(fieldId)) {
                    fieldList.push({
                        fieldId: fieldId,
                        value: fields[fieldId]
                    });
                }
            }

            let fieldPriceHash = generateFieldHash(fieldList);

            if (this.$fieldPriceHash === fieldPriceHash && this.$currentPrice) {
                return Promise.resolve(this.$currentPrice);
            }

            this.$fieldPriceHash = fieldPriceHash;

            if (this.$fieldPriceHash === fieldPriceHash && this.$priceRequest) {
                return this.$priceRequest;
            }

            this.$priceRequest = new Promise((resolve) => {
                Ajax.get('package_quiqqer_products_ajax_products_calc', (result) => {
                    this.$currentPrice = result;
                    this.$priceRequest = null;
                    resolve(this.$currentPrice);
                }, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fields: JSON.encode(fieldList),
                    quantity: quantity
                });
            });

            return this.$priceRequest;
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
