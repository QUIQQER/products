/**
 * Product Variant view
 * Display a product variant in the content
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/products/ProductVariant', [

    'qui/QUI',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',
    'URI',
    'package/quiqqer/products/bin/controls/frontend/products/Product'

], function (QUI, QUILoader, QUIAjax, QUILocale, URI, Product) {
    "use strict";

    // history popstate for mootools
    Element.NativeEvents.popstate = 2;

    return new Class({

        Extends: Product,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/ProductVariant',

        Binds: [
            '$onInject',
            '$onImport',
            '$init',
            '$onPopstateChange'
        ],

        options: {
            closeable    : false,
            productId    : false,
            galleryLoader: true
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader     = new QUILoader();
            this.$startInit = false;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport,
                onClose : function () {
                    window.removeEvent('popstate', this.$onPopstateChange);
                }.bind(this)
            });

            // react for url change
            window.addEvent('popstate', this.$onPopstateChange);
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.parent().then(this.$init);
        },

        /**
         * event : on import
         */
        $onImport: function () {
            if (typeof window.fieldHashes !== 'undefined') {
                this.$fieldHashes = window.fieldHashes;
            }

            if (typeof window.availableHashes !== 'undefined') {
                this.$availableHashes = window.availableHashes;
            }

            return this.parent().then(this.$init);
        },

        /**
         * event: on popstate change
         */
        $onPopstateChange: function () {
            if (this.$startInit === false) {
                return;
            }

            var self       = this,
                url        = QUIQQER_SITE.url,
                URL        = URI(window.location),
                path       = window.location.pathname,

                variantId  = '',
                variantUrl = path.substring(
                    path.lastIndexOf(url) + url.length
                );

            this.Loader.show();

            if (URL.hasQuery('variant')) {
                variantId = parseInt(URL.query(true).variant);
            }

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVariantByUrl', function (result) {
                if (!result) {
                    self.Loader.hide();
                }

                var Field;
                var Elm    = self.getElm();
                var fields = result.fields;

                for (var fieldId in fields) {
                    if (!fields.hasOwnProperty(fieldId)) {
                        continue;
                    }

                    Field = Elm.getElement('[name="field-' + fieldId + '"]');

                    if (Field) {
                        Field.value = fields[fieldId];
                    }
                }

                self.Loader.hide();
            }, {
                'package' : 'quiqqer/products',
                variantUrl: variantUrl,
                variantId : variantId,
                productId : this.getAttribute('productId')
            });
        },

        /**
         * init the variant stuff
         */
        $init: function () {
            if (this.$startInit) {
                return;
            }

            var self = this;

            this.$startInit = true;
            this.Loader.inject(this.getElm());

            // remove events from AttributeList field controls (added by parent.$onImport)
            var fields = this.getFieldControls();

            fields.each(function (Control) {
                Control.removeEvents('onChange');
            });

            // add Variant events
            var fieldLists = this.getElm().getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            var attributeGroups = this.getElm().getElement(
                '[data-qui="package/pbisschop/template/bin/js/AttributeGroups"]'
            ).getElements(
                '.quiqqer-product-field select'
            );

            fieldLists.removeEvents('change');

            fieldLists.addEvent('change', function () {
                if (this.getParent('[data-qui="package/pbisschop/template/bin/js/AttributeGroups"]')) {
                    var currentHash = self.getCurrentHash();

                    if (typeof self.$availableHashes[currentHash] === 'undefined') {
                        self.hidePrice();
                        self.disableButtons();
                        return;
                    }
                }

                self.$refreshVariant();
            });

            attributeGroups.addEvent('focus', function () {
                if (attributeGroups.length === 1) {
                    return;
                }

                var i, len, select;

                var values  = {};
                var fieldId = this.name.replace('field-', '');
                var options = this.options;

                var enabled     = [];
                var EmptyOption = enabled.filter(function (option) {
                    return option.value === '';
                })[0];

                for (i = 0, len = attributeGroups.length; i < len; i++) {
                    select = attributeGroups[i];

                    values[select.name.replace('field-', '')] = select.value;
                }

                for (i = 0, len = options.length; i < len; i++) {
                    options[i].disabled = !self.$isFieldValueInFields(
                        fieldId,
                        options[i].value
                    );

                    if (options[i].disabled === false) {
                        enabled.push(options[i]);
                    }
                }

                if (EmptyOption) {
                    EmptyOption.disabled = false;
                }

                if (enabled.length === 1 && EmptyOption) {
                    EmptyOption.disabled = true;
                }

                if (enabled.length === 1) {
                    this.value = enabled[0].value;
                    return;
                }

                if (EmptyOption && enabled.length === 2) {
                    var option = enabled.filter(function (option) {
                        return option.value !== '';
                    })[0];

                    this.value = option.value;

                    if (EmptyOption) {
                        EmptyOption.disabled = true;
                    }
                }
            });

            new Element('div', {
                class : 'product-data-fieldlist-reset',
                html  : QUILocale.get('quiqqer/products', 'control.variant.reset.fields'),
                events: {
                    click: function () {
                        fieldLists.set('value', '');
                        self.$refreshVariant();
                    }
                }
            }).inject(this.getElm().getElement('.product-data-fieldlist'));
        },

        /**
         * refresh the variant control
         */
        $refreshVariant: function () {
            this.Loader.show();

            var self       = this;
            var fieldLists = this.getElm().getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            fieldLists = fieldLists.map(function (Elm) {
                var r = {};

                r[Elm.get('data-field')] = Elm.value;

                return r;
            });

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVariant', function (result) {
                var Ghost = new Element('div', {
                    html: result.control
                });

                document.title        = result.title;
                self.$fieldHashes     = result.fieldHashes;
                self.$availableHashes = result.availableHashes;

                // only if product is in main category
                if (typeof window.QUIQQER_PRODUCT_CATEGORY !== 'undefined' &&
                    parseInt(result.category) === parseInt(window.QUIQQER_PRODUCT_CATEGORY)) {
                    window.history.pushState({}, "", result.url.toString());
                } else {
                    var Url = URI(window.location);
                    var url = Url.setSearch('variant', result.variantId).toString();

                    window.history.pushState({}, "", url);
                }

                self.$startInit = false;

                var Control = Ghost.getElement(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/products/ProductVariant"]'
                );

                if (Control) {
                    self.getElm().set('html', Control.get('html'));

                    // css
                    new Element('div', {
                        html: result.css
                    }).inject(self.getElm());
                }

                QUI.parse(self.getElm()).then(function () {
                    self.$init();
                    self.$initTabEvents();
                    self.Loader.hide();
                });
            }, {
                'package'           : 'quiqqer/products',
                productId           : this.getAttribute('productId'),
                fields              : JSON.encode(fieldLists),
                ignoreDefaultVariant: 1
            });
        },

        /**
         * Helper to get hashes which fit at the current setting
         *
         * @param fieldId
         * @param fieldValue
         * @return {boolean}
         */
        $isFieldValueInFields: function (fieldId, fieldValue) {
            var i, len, avHashes;
            var hashes = this.$fieldHashes;

            fieldId = parseInt(fieldId);

            if (fieldValue === '') {
                return true;
            }


            var current    = this.getCurrentFieldValues();
            var collection = {};

            var fitHashToCurrentSettings = function (h) {
                for (var c in current) {
                    if (!current.hasOwnProperty(c)) {
                        continue;
                    }

                    if (!isNaN(c)) {
                        c = parseInt(c);
                    }

                    if (c === fieldId) {
                        continue;
                    }

                    if (current[c] === '') {
                        continue;
                    }

                    if (h.indexOf(c + ':' + current[c]) === -1) {
                        return false;
                    }
                }

                return true;
            };

            for (var id in hashes) {
                if (!hashes.hasOwnProperty(id)) {
                    continue;
                }

                id = parseInt(id);

                if (id === fieldId) {
                    continue;
                }

                /**
                 * Strings with more than 1 character that begin with "0" (e.g. "00") must not be parsed to int,
                 * since the "0" at the beginning would be deleted that way.
                 */
                if (!isNaN(fieldValue) &&
                    !(fieldValue.length > 1 && fieldValue.indexOf("0") === 0)) {
                    fieldValue = parseInt(fieldValue);
                } else {
                    fieldValue = this.stringToHex(fieldValue);
                }

                try {
                    if (typeof hashes[id][fieldId][fieldValue] === 'undefined') {
                        continue;
                    }

                    avHashes = hashes[id][fieldId][fieldValue];

                    for (i = 0, len = avHashes.length; i < len; i++) {
                        collection[avHashes[i]] = true;
                    }
                } catch (e) {
                }
            }

            for (i in collection) {
                if (!collection.hasOwnProperty(i)) {
                    continue;
                }

                if (fitHashToCurrentSettings(i)) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Hide the price display
         */
        hidePrice: function () {
            var PriceContainer = this.getElm().getElement('.product-data-price');

            if (!PriceContainer) {
                return;
            }

            moofx(PriceContainer).animate({
                height : 0,
                opacity: 0
            }, {
                duration: 200
            });
        },

        /**
         * Hide the price display
         */
        showPrice: function () {
            var PriceContainer = this.getElm().getElement('.product-data-price');

            if (!PriceContainer) {
                return;
            }

            PriceContainer.setStyle('height', null);

            moofx(PriceContainer).animate({
                opacity: 1
            }, {
                duration: 200
            });
        },

        /**
         * disable buttons
         */
        disableButtons: function () {
            var Container = document.getElement('.product-data-actionButtons');

            Container.getElements('button').set('disabled', true);

            Container.getElements('[data-quiid]').forEach(function (Node) {
                var Control = QUI.Controls.getById(Node.get('data-quiid'));

                if (typeof Control.disable !== 'undefined') {
                    Control.disable();
                }
            });
        },

        /**
         * Return the current hash from the product field settings
         *
         * @return {string}
         */
        getCurrentHash: function () {
            var fields = this.getCurrentFieldValues();
            var hash   = [];

            for (var i in fields) {
                if (fields.hasOwnProperty(i)) {
                    hash.push(i + ':' + fields[i]);
                }
            }

            return ';' + hash.join(';') + ';';
        },

        /**
         * @return {Object}
         */
        getCurrentFieldValues: function () {
            var attributeGroups = this.getElm().getElement(
                '[data-qui="package/pbisschop/template/bin/js/AttributeGroups"]'
            ).getElements('.quiqqer-product-field select');

            var i, len, fieldName, fieldValue;
            var fields = {};

            for (i = 0, len = attributeGroups.length; i < len; i++) {
                fieldName  = attributeGroups[i].name.replace('field-', '');
                fieldValue = this.stringToHex(attributeGroups[i].value);

                fields[fieldName] = fieldValue;
            }

            return fields;
        },

        /**
         *
         * @param str
         * @return {string}
         */
        stringToHex: function (str) {
            if (str === '') {
                return '';
            }

            if (!isNaN(str)) {
                return str;
            }

            var i, len, char;
            var result = '';

            for (i = 0, len = str.length; i < len; i++) {
                char = str.charCodeAt(i);
                result += char.toString(16);
            }

            return result;
        }
    });
});
