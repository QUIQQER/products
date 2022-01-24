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
    'utils/Controls',

    'Ajax',
    'Locale',
    'URI',
    'package/quiqqer/products/bin/controls/frontend/products/Product'

], function (QUI, QUILoader, QUIControlUtils, QUIAjax, QUILocale, URI, Product) {
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
            '$onPopstateChange',
            '$onSliderImageShow',
            '$registerAttributeGroupSelectEvents',
            '$onAttributeGroupSelectChange'
        ],

        options: {
            productId                 : false,
            galleryLoader             : true,
            closeable                 : false,
            image_attribute_data      : false, // Special attribute group fields data from product images (set by PHP control)
            link_images_and_attributes: false
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader             = new QUILoader({'cssclass': 'quiqqer-products-productVariant-loader'});
            this.$startInit         = false;
            this.$isOnlyVariantList = false; // if the variant has no attribute lists and only one list of its variants

            this.$currentVariantId               = false;
            this.$isVariantParent                = true;
            this.$fieldHashes                    = null;
            this.$availableHashes                = null;
            this.$ImgAttributeGroupsData         = {};
            this.$SliderControl                  = null;
            this.$nonImageAttributeGroupFieldIds = [];

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

            return this.parent().then(() => {
                if (this.$availableHashes && this.$fieldHashes) {
                    return;
                }

                return new Promise((resolve) => {
                    QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getProduct', (result) => {
                        this.$fieldHashes     = result.fieldHashes;
                        this.$availableHashes = result.availableHashes;

                        resolve();
                    }, {
                        'package': 'quiqqer/products',
                        productId: this.getAttribute('productId'),
                        siteId   : QUIQQER_SITE.id
                    });
                });
            }).then(this.$init);
        },

        /**
         * event: on popstate change
         */
        $onPopstateChange: function () {
            if (this.$startInit === false) {
                return;
            }

            const self = this,
                  url  = QUIQQER_SITE.url,
                  URL  = URI(window.location),
                  path = window.location.pathname;

            let variantId  = '',
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

                let Field;
                const Elm    = self.getElm();
                const fields = result.fields;

                for (let fieldId in fields) {
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

            const self = this;
            const Elm  = this.getElm();

            this.$startInit = true;
            this.Loader.inject(document.body);

            // remove events from AttributeList field controls (added by parent.$onImport)
            const fields = this.getFieldControls();

            fields.each(function (Control) {
                Control.removeEvents('onChange');
            });

            this.$isOnlyVariantList = !!Elm.getElement('[name="field-23"]');

            // add Variant events
            const fieldLists = Elm.getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            fieldLists.removeEvents('change');
            fieldLists.addEvent('change', (event) => {
                const currentHash = self.getCurrentHash();

                // Load parent (if a variant is currently selected and an attribute field is deselected)
                if (event.target.value === '') {
                    let refresh = true;

                    // Check if a specific variant is selected
                    fieldLists.forEach((Select) => {
                        if (Select !== event.target && Select.value === '') {
                            refresh = false;
                        }
                    });

                    if (refresh) {
                        // Reset all attribute list select fields that are not associated with a variant child image.
                        fieldLists.forEach((AttributeGroupSelect) => {
                            const fieldId = parseInt(AttributeGroupSelect.get('data-field'));

                            if (this.$nonImageAttributeGroupFieldIds.contains(fieldId)) {
                                AttributeGroupSelect.value = '';
                            }
                        });

                        self.$refreshVariant();
                    }
                    return;
                }

                if (typeof self.$availableHashes[currentHash] === 'undefined') {
                    self.hidePrice();
                    self.disableButtons();
                    return;
                }

                self.$refreshVariant();
            });

            const attributeGroups = Elm.getElements('[data-field-type="AttributeGroup"] select');

            attributeGroups.addEvent('focus', function () {
                if (attributeGroups.length === 1) {
                    return;
                }

                let i, len, select;

                const values  = {};
                const fieldId = this.name.replace('field-', '');
                const options = this.options;

                const enabled     = [];
                const EmptyOption = enabled.filter(function (option) {
                    return option.value === '';
                })[0];

                for (i = 0, len = attributeGroups.length; i < len; i++) {
                    select = attributeGroups[i];

                    values[select.name.replace('field-', '')] = select.value;
                }

                for (i = 0, len = options.length; i < len; i++) {
                    if (self.$isOnlyVariantList) {
                        continue;
                    }

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
                    const option = enabled.filter(function (option) {
                        return option.value !== '';
                    })[0];

                    this.value = option.value;

                    if (EmptyOption) {
                        EmptyOption.disabled = true;
                    }
                }
            });

            if (Elm.getElement('.product-data-fieldlist')) {
                new Element('div', {
                    class : 'product-data-fieldlist-reset',
                    html  : QUILocale.get('quiqqer/products', 'control.variant.reset.fields'),
                    events: {
                        click: function () {
                            fieldLists.set('value', '');
                            self.$refreshVariant();
                        }
                    }
                }).inject(Elm.getElement('.product-data-fieldlist'));
            }

            if (this.getAttribute('link_images_and_attributes')) {
                const SliderControlElm = Elm.getElement('[data-qui="package/quiqqer/gallery/bin/controls/Slider"]');

                if (this.getAttribute('image_attribute_data')) {
                    this.$ImgAttributeGroupsData = JSON.decode(this.getAttribute('image_attribute_data'));

                    // Determine all fields that are not associated with images
                    const availableFieldIds = [];
                    const imageFieldIds     = [];

                    fieldLists.forEach((AttributeGroupSelect) => {
                        availableFieldIds.push(parseInt(AttributeGroupSelect.get('data-field')));
                    });

                    for (const Fields of Object.values(this.$ImgAttributeGroupsData)) {
                        for (const fieldId of Object.keys(Fields)) {
                            imageFieldIds.push(parseInt(fieldId));
                        }
                    }

                    availableFieldIds.forEach((availableFieldId) => {
                        if (!imageFieldIds.contains(availableFieldId)) {
                            this.$nonImageAttributeGroupFieldIds.push(availableFieldId);
                        }
                    });
                }

                if (SliderControlElm) {
                    QUIControlUtils.getControlByElement(SliderControlElm).then((SliderControl) => {
                        this.$SliderControl = SliderControl;

                        if (SliderControl.isLoaded()) {
                            SliderControl.addEvent('onImageShow', this.$onSliderImageShow);
                        } else {
                            SliderControl.addEvent('onLoaded', () => {
                                SliderControl.addEvent('onImageShow', this.$onSliderImageShow);
                            });
                        }
                    });
                }
            }

            this.$registerAttributeGroupSelectEvents();
        },

        /**
         * refresh the variant control
         */
        $refreshVariant: function () {
            this.Loader.show();

            const self     = this;
            let fieldLists = this.getElm().getElements(
                '.product-data-fieldlist .quiqqer-product-field select'
            );

            fieldLists = fieldLists.map(function (Elm) {
                let r = {};

                r[Elm.get('data-field')] = Elm.value;

                return r;
            });

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVariant', function (result) {
                const Ghost = new Element('div', {
                    html: result.control
                });

                self.$currentVariantId = result.variantId;
                self.$isVariantParent  = !!result.isVariantParent;

                document.title = result.title;
                //self.$fieldHashes     = result.fieldHashes;
                //self.$availableHashes = result.availableHashes;

                // only if product is in main category
                if (typeof window.QUIQQER_PRODUCT_CATEGORY !== 'undefined' &&
                    parseInt(result.category) === parseInt(window.QUIQQER_PRODUCT_CATEGORY)) {
                    //window.history.pushState({}, "", result.url.toString());
                } else {
                    const Url = URI(window.location);
                    var url   = Url.setSearch('variant', result.variantId).toString();
                    //window.history.pushState({}, "", url);
                }

                self.$startInit = false;

                const Control = Ghost.getElement(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/products/ProductVariant"]'
                );

                if (Control) {
                    self.getElm().set('html', Control.get('html'));

                    // css
                    new Element('div', {
                        html: result.css
                    }).inject(self.getElm());
                }

                if (!self.getElm().getElement('.product-close-button') && self.getAttribute('closeable')) {
                    new Element('div', {
                        'class': 'product-close-button',
                        html   : '<span class="fa fa-close"></span>',
                        events : {
                            click: function () {
                                document.title = QUIQQER.title;
                                self.fireEvent('close');
                            }
                        }
                    }).inject(self.getElm());
                }

                QUI.parse(self.getElm()).then(function () {
                    self.$init();
                    self.$initTabEvents();
                    self.fireEvent('onQuiqqerProductVariantRefresh', [self]);
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
            let i, len, avHashes;
            const hashes = this.$fieldHashes;

            fieldId = parseInt(fieldId);

            if (fieldValue === '') {
                return true;
            }

            const current    = this.getCurrentFieldValues();
            const collection = {};

            const fitHashToCurrentSettings = function (h) {
                for (let c in current) {
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

            for (let id in hashes) {
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
            const PriceContainer = this.getElm().getElement('.product-data-price');

            if (!PriceContainer) {
                return;
            }

            if (PriceContainer.get('data-qui-options-price') !== '') {
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
            const PriceContainer = this.getElm().getElement('.product-data-price');

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
            const Container = document.getElement('.product-data-actionButtons');

            Container.getElements('button').set('disabled', true);

            Container.getElements('[data-quiid]').forEach(function (Node) {
                const Control = QUI.Controls.getById(Node.get('data-quiid'));

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
            const fields = this.getCurrentFieldValues();
            const hash   = [];

            for (let i in fields) {
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
            const attributeGroups = this.getElm().getElements('[data-field-type="AttributeGroup"] select');

            let i, len, fieldName, fieldValue;
            const fields = {};

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

            let i, len, char;
            let result = '';

            for (i = 0, len = str.length; i < len; i++) {
                char = str.charCodeAt(i);
                result += char.toString(16);
            }

            return result;
        },

        /**
         * Get ID of the current product
         *
         * @return {Number|Boolean} - May be false if no product is explicitly selected / set
         */
        getProductId: function () {
            return this.$currentVariantId;
        },

        /**
         * Indicates if this product can be bought in its current configuration.
         *
         * @return {boolean}
         */
        isBuyable: function () {
            return !this.$isVariantParent;
        },

        /**
         * Register change events for attribute group selects.
         *
         * If a select changes, the corresponding image has to be loaded (if applicable).
         *
         * @return {void}
         */
        $registerAttributeGroupSelectEvents: function () {
            if (!this.getAttribute('link_images_and_attributes')) {
                return;
            }

            const AttributeGroupSelects = this.getElm().getElements('div[data-field-type="AttributeGroup"] select');

            AttributeGroupSelects.forEach((Select) => {
                Select.addEventListener('change', this.$onAttributeGroupSelectChange);
            });
        },

        /**
         * event: change on AttributeGroup selects
         *
         * @return {void}
         */
        $onAttributeGroupSelectChange: function () {
            if (!this.$SliderControl || !('selectImageByFilename' in this.$SliderControl)) {
                return;
            }

            const AttributeGroupSelects    = this.getElm().getElements('div[data-field-type="AttributeGroup"] select');
            const AttributeGroupTargetData = {};
            let allSelectsWithValues       = true;

            AttributeGroupSelects.forEach((Select) => {
                if (Select.value) {
                    AttributeGroupTargetData[parseInt(Select.get('data-field'))] = parseInt(Select.value);
                } else {
                    allSelectsWithValues = false;
                }
            });

            if (allSelectsWithValues) {
                return;
            }

            if (!Object.values(AttributeGroupTargetData).length) {
                return;
            }

            const imgRegExpParseFilename = new RegExp('([^\\/]*)\\.\\w+$', 'igm');
            const matchedImages          = [];

            for (const [imagePath, ImageData] of Object.entries(this.$ImgAttributeGroupsData)) {
                let matchedFields = 0;

                for (const [fieldId, fieldValue] of Object.entries(AttributeGroupTargetData)) {
                    if (!(fieldId in ImageData)) {
                        continue;
                    }

                    if (ImageData[fieldId] !== fieldValue) {
                        continue;
                    }

                    matchedFields++;
                }

                if (matchedFields > 0) {
                    const filenameMatches = [...imagePath.split('/').pop().matchAll(imgRegExpParseFilename)];

                    if (filenameMatches.length && typeof filenameMatches[0][1] !== 'undefined') {
                        matchedImages.push({
                            imageFilename: filenameMatches[0][1],
                            matchedFields: matchedFields
                        });
                    }
                }
            }

            // Select first image that fits in gallery
            if (!matchedImages.length) {
                return;
            }

            const sortedImages = matchedImages.sort((matchA, matchB) => {
                return matchB.matchedFields - matchA.matchedFields;
            });

            this.$SliderControl.selectImageByFilename(sortedImages[0].imageFilename);
        },

        /**
         * event: onImageClick from
         *
         * @param {Object} SliderControl - package/quiqqer/gallery/bin/controls/Slider
         * @param {Object} ImageData
         */
        $onSliderImageShow: function (SliderControl, ImageData) {
            const Elm = this.getElm();

            const imgRegExpRemoveSize = new RegExp('__\\d+x\\d+', 'ig');
            const imgRegExpRemoveExt  = new RegExp('\\.\\w+$', 'id');
            const imageUrl            = ImageData.src.replace(imgRegExpRemoveSize, '');

            let AttributeGroupData = false;

            for (let [imagePath, ImageData] of Object.entries(this.$ImgAttributeGroupsData)) {
                imagePath = imagePath.replace(imgRegExpRemoveExt, '');

                if (imageUrl.indexOf(imagePath) !== -1) {
                    AttributeGroupData = ImageData;
                    break;
                }
            }

            if (!AttributeGroupData) {
                return;
            }

            for (const [fieldId, fieldValue] of Object.entries(AttributeGroupData)) {
                const FieldSelect = Elm.getElement('select[data-field="' + fieldId + '"]');

                if (FieldSelect) {
                    FieldSelect.value = fieldValue;
                }
            }
        }
    });
});
