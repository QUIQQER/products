/**
 * @module package/quiqqer/products/bin/classes/Product
 * @author www.pcsg.de (henning Leutz)
 *
 * @event onRefresh [this]
 *
 * for frontend products, please use package/quiqqer/products/bin/classes/frontend/Product
 */
define('package/quiqqer/products/bin/classes/Product', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale',
    'package/quiqqer/products/bin/Fields',
    'qui/utils/String'

], function (QUI, QUIDOM, Ajax, QUILocale, Fields, StringUtils) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Product',

        options: {
            id: false
        },

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$uid = String.uniqueID();
            this.$data = null;
            this.$loaded = false;
            this.$quantity = 1;
        },

        /**
         * Is the project already loaded?
         *
         * @return {boolean}
         */
        isLoaded: function () {
            return this.$loaded;
        },

        /**
         * load the attributes if the product is not loaded
         *
         * @returns {Promise<unknown>|*}
         */
        load: function () {
            if (this.$loaded) {
                return Promise.resolve(this);
            }

            return this.refresh().then(() => {
                return this;
            });
        },

        /**
         * Add a field to the product
         *
         * @param {Number|Array}  fieldId
         * @return {Promise}
         */
        addField: function (fieldId) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_addField', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : JSON.encode(fieldId)
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
         * Create the media folder for the product
         *
         * @param {Number|Boolean}  [fieldId]
         * @returns {Promise}
         */
        createMediaFolder: function (fieldId) {
            fieldId = fieldId || false;

            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_createMediaFolder', function () {
                    this.refresh().then(resolve, reject);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    onError  : reject,
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
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_setPublicStatusFromField', function () {
                    this.refresh().then(resolve, reject);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fieldId  : fieldId,
                    status   : status ? 1 : 0,
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * Set permissions for the own product permissions
         *
         * @param {Object} permissions - list of permissions
         * @returns {Promise}
         */
        setPermissions: function (permissions) {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_setPermissions', function () {
                    this.refresh().then(resolve, reject);
                }.bind(this), {
                    'package'  : 'quiqqer/products',
                    productId  : this.getId(),
                    permissions: JSON.encode(permissions),
                    onError    : reject
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
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_setQuantity', function (result) {
                    this.$quantity = parseInt(result);
                    this.fireEvent('change', [this]);

                    resolve(result);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    quantity : quantity,
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * Set the default variant ID for the parent product
         * Works only if the product is a VariantParent
         *
         * @param {String|Number} variantId
         * @return {Promise}
         */
        setDefaultVariantId: function (variantId) {
            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_products_variant_setDefaultVariant', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    variantId: variantId,
                    onError  : reject
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

            return this.getFieldValue(Fields.FIELD_TITLE).then(function (field) {
                if (typeOf(field) === 'string') {
                    try {
                        field = JSON.decode(field);
                    } catch (e) {
                        field = {};
                    }
                }

                let title   = '',
                    current = Locale.getCurrent();

                if (current in field) {
                    title = field[current];
                }

                return title;
            });
        },

        /**
         * Return the product image
         *
         * @param {Object} [Locale] - optional, QUI Locale object
         * @returns {Promise}
         */
        getWorkingTitle: function (Locale) {
            Locale = Locale || QUILocale;

            return this.getFieldValue(Fields.FIELD_WORKING_TITLE).then(function (field) {
                if (typeOf(field) === 'string') {
                    try {
                        field = JSON.decode(field);
                    } catch (e) {
                        field = {};
                    }
                }

                let title   = '',
                    current = Locale.getCurrent();

                if (current in field) {
                    title = field[current];
                }

                return title;
            });
        },

        /**
         * Return the product description
         *
         * @param {Object} [Locale] - optional, QUI Locale object
         * @returns {Promise}
         */
        getDescription: function (Locale) {
            Locale = Locale || QUILocale;

            return this.getFieldValue(Fields.FIELD_SHORT_DESC).then(function (field) {
                if (typeOf(field) === 'string') {
                    try {
                        field = JSON.decode(field);
                    } catch (e) {
                        field = {};
                    }
                }

                let title   = '',
                    current = Locale.getCurrent();

                if (current in field) {
                    title = field[current];
                }

                return title;
            });
        },

        /**
         * Return the product image
         *
         * @returns {Promise}
         */
        getImage: function () {
            return new Promise(function (resolve, reject) {
                this.getFieldValue(Fields.FIELD_IMAGE).then(function (result) {
                    if (result !== '' && result) {
                        return resolve(result);
                    }

                    this.getFolder().then(function (folder) {
                        if (!folder) {
                            return reject('Product has no media folder.');
                        }

                        const params = StringUtils.getUrlParams(folder);

                        if (!("id" in params) || !("project" in params)) {
                            return reject('Product media folder is no QUIQQER media url.');
                        }

                        // get first folder image
                        Ajax.get('ajax_media_folder_firstImage', function (file) {
                            if (!file.length) {
                                resolve(false);
                                return;
                            }

                            resolve(file.url);
                        }, {
                            project  : params.project,
                            folderId : params.id,
                            showError: false,
                            onError  : reject
                        });
                    });
                }.bind(this));
            }.bind(this));
        },

        /**
         * Return the product image
         *
         * @returns {Promise}
         */
        getFolder: function () {
            return this.getFieldValue(Fields.FIELD_FOLDER);
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
                    return resolve(!!this.$data.active);
                }

                this.refresh().then(function () {
                    resolve(!!this.$data.active);
                }.bind(this)).catch(reject);

            }.bind(this));
        },

        /**
         * Activate the product
         *
         * @returns {Promise}
         */
        activate: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_activate', function () {
                    this.refresh().then(resolve, reject);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * Activate the product
         *
         * @returns {Promise}
         */
        deactivate: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_products_deactivate', function () {
                    this.refresh().then(resolve, reject);
                }.bind(this), {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * Refresh the product data
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise((resolve, reject) => {
                require(['package/quiqqer/products/bin/Products'], (Products) => {
                    Products.getChild(this.getId()).then((data) => {
                        this.$loaded = true;
                        this.$data = data;

                        resolve(this);

                        this.fireEvent('refresh', [this]);
                    }).catch(reject);
                });
            });
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
         * Return all fields from the specific type
         *
         * @param {String} type
         * @return {Array}
         */
        getFieldsByType: function (type) {
            return this.getFields().then(function (fields) {
                return fields.filter(function (Field) {
                    return Field.type === type;
                });
            });
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
                        return (item.id === fieldId);
                    });

                    if (field.length) {
                        return resolve(field[0]);
                    }

                    return reject('Field #' + fieldId + ' not found');
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

        setFieldValue: function (fieldId, value) {
            fieldId = parseInt(fieldId);

            for (let i = 0, len = this.$data.fields.length; i < len; i++) {
                if (parseInt(this.$data.fields[i].id) === fieldId) {
                    this.$data.fields[i].value = value;
                }
            }
        },

        /**
         * Return the editable fields of this product
         * Makes only sense if the product is a parent product
         *
         * @return {Promise}
         */
        getEditableFields: function () {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId()
                });
            }.bind(this));
        },

        /**
         * Return the categories of the product
         *
         * @returns {Promise}
         */
        getCategories: function () {
            const self = this;

            return new Promise(function (resolve, reject) {
                if (self.$loaded) {
                    const categories = self.$data.categories.split(',').filter(function (entry) {
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

            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_calc', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    fields   : JSON.encode(this.$data.fields),
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
        },

        /**
         * Return all variants of this product
         * - variant children
         *
         * @param [options]
         * @return {Promise}
         */
        getVariants: function (options) {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_variant_getVariants', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId(),
                    options  : JSON.encode(options)
                });
            }.bind(this));
        },

        /**
         * Return the fields which can be used for variants
         *
         * @return {Promise}
         */
        getVariantFields: function () {
            return new Promise(function (resolve) {
                Ajax.get('package_quiqqer_products_ajax_products_variant_getVariantFields', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId()
                });
            }.bind(this));
        },

        /**
         * Reset the inherited fields of a product to the global ones
         *
         * @return {Promise}
         */
        resetInheritedFields: function () {
            return new Promise((resolve) => {
                Ajax.post('package_quiqqer_products_ajax_products_variant_resetEditableInheritedFields', () => {
                    this.refresh().then(resolve);
                }, {
                    'package': 'quiqqer/products',
                    productId: this.getId()
                });
            });
        },

        /**
         * Has the product its own folder ?
         *
         * @return {Promise}
         */
        hasOwnMediaFolder: function () {
            return new Promise(function (resolve) {
                Ajax.post('package_quiqqer_products_ajax_products_variant_hasOwnFolder', resolve, {
                    'package': 'quiqqer/products',
                    productId: this.getId()
                });
            }.bind(this));
        }
    });
});
