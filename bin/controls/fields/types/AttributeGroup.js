/**
 * @module package/quiqqer/products/bin/controls/fields/types/AttributeGroup
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/AttributeGroup', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Fields',
    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/types/AttributeGroup.css'

], function (QUI, QUIControl, Fields, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/AttributeGroup',

        Binds: [
            '$onImport'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$initValue = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input = this.getElm();

            this.$Elm = new Element('div').wraps(this.$Input);
            this.$Elm.addClass('field-container-field');
            this.$Elm.addClass('field-container-field-no-padding');
            this.$Elm.addClass('quiqqer-products-field-attributeGroup');
            this.$Elm.set('data-quiid', this.getId());

            this.$Select = new Element('select').inject(this.$Elm);
            this.$Select.addClass('quiqqer-products-field-attributeGroup-select');
            this.$Select.addEvent('change', function (e) {
                this.$Input.value = e.target.value;
            }.bind(this));

            this.$Select.name = this.$Input.name;
            this.$Select.set('data-quiid', this.getId());

            this.$Input.name = '';

            this.$loadOptions().catch(function (err) {
                console.error(err);
            });
        },

        /**
         * Set data to the field
         *
         * @param val
         */
        setData: function (val) {
            this.$initValue = val;

            if (!this.$Select && this.$Elm) {
                this.$Elm.value = val;
            }

            if (this.$Select) {
                this.$Select.value = val;
            }

            this.addEvent('onLoad', function () {
                this.$Select.value = val;
            }.bind(this));
        },

        /**
         * load the options for the field
         *
         * @return {Promise<void>}
         */
        $loadOptions: function () {
            var self    = this,
                fieldId = parseInt(this.$Select.get('name').replace('field-', '')),
                value   = this.$initValue;

            self.$Select.set('disabled', true);
            self.$Select.set('html', '');

            return Fields.getFieldOptions(fieldId).then(function (options) {
                if (typeof options.entries === 'undefined') {
                    return;
                }

                var i, len, text;
                var current = QUILocale.getCurrent();
                var entries = options.entries;

                if (!entries.length) {
                    new Element('option', {
                        html : '---',
                        value: ''
                    }).inject(self.$Select);
                } else if (entries.length > 1) {
                    new Element('option', {
                        html : '',
                        value: ''
                    }).inject(self.$Select);
                }

                for (i = 0, len = entries.length; i < len; i++) {
                    if (typeof entries[i].title[current] !== 'undefined') {
                        text = entries[i].title[current];
                    } else if (typeOf(entries[i].title) === 'object') {
                        text = entries[i].title[Object.keys(entries[i].title)[0]];
                    } else {
                        text = '';
                    }

                    new Element('option', {
                        html : text,
                        value: entries[i].valueId
                    }).inject(self.$Select);
                }

                if (value) {
                    self.$Select.value = value;
                } else {
                    var selected = entries.filter(function (entry) {
                        return entry.selected;
                    });

                    if (selected.length) {
                        self.$Select.value = selected[0].valueId;
                    }
                }

                // if variant, than disable
                // varianten können ihre attribute listen nicht mehr ändern
                // da es sonst wegen doppelten varianten probleme geben kann
                var isVariantPanel = self.getElm().getParent('.panel-product-variant');

                if (!isVariantPanel) {
                    self.$Select.set('disabled', false);
                }

                self.fireEvent('load', [self]);
            });
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Select.value;
        }
    });
});
