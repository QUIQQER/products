/**
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductListField
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductListField', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter'

], function (QUI, ProductListFilter) {
    "use strict";

    return new Class({

        Extends: ProductListFilter,
        Type: 'package/quiqqer/products/bin/controls/frontend/category/ProductListField',

        Binds: [
            '$onInject',
            'refresh'
        ],

        options: {
            Field: false
        },

        /**
         * Return new div element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            let fieldValue = '';
            const Field = this.getAttribute('Field');

            if (Field && Field.getSearchValue()) {
                fieldValue = Field.getSearchValue();

                if (typeof fieldValue !== 'string' && typeof fieldValue.length !== 'undefined') {
                    fieldValue = fieldValue.join(',');
                } else if (typeof fieldValue === 'object') {
                    fieldValue = Object.toQueryString(fieldValue);
                }
            }

            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-productList-filter',
                html: '<div class="quiqqer-products-productList-filter-text"></div>' +
                    '<div class="quiqqer-products-productList-filter-destroy">' +
                    '    <span class="fa fa-close"></span>' +
                    '</div>',
                'data-field': Field.getAttribute('fieldid'),
                'data-field-value': fieldValue
            });

            this.$Text = this.$Elm.getElement('.quiqqer-products-productList-filter-text');
            this.$Cancel = this.$Elm.getElement('.quiqqer-products-productList-filter-destroy');

            this.$Cancel.addEvent('click', () => {
                this.hide();
                this.fireEvent('close', [this]);
            });

            return this.$Elm;
        },

        /**
         * event : on refresh
         */
        $onInject: function () {
            if (!this.getAttribute('Field')) {
                this.hide();
                return;
            }

            this.getAttribute('Field').addEvent('change', this.refresh);
            this.refresh();
        },

        /**
         * Reset the value
         */
        reset: function () {
            if (this.getAttribute('Field') && 'reset' in this.getAttribute('Field')) {
                this.getAttribute('Field').reset();
            }
        },

        /**
         * refresh the display,
         * if no value exists, the ListField are hidden
         */
        refresh: function () {
            if (!this.getAttribute('Field')) {
                this.hide();
                return;
            }

            const Field = this.getAttribute('Field');
            let value = Field.getSearchValue();

            if (Field.getType() === 'package/quiqqer/productsearch/bin/controls/search/SearchField' &&
                typeof Field.$Type.$Select !== 'undefined') {
                const max = Field.$Type.$Select.getAttribute('max');
                const min = Field.$Type.$Select.getAttribute('min');

                if (!min && !max) {
                    value = false;
                }

                if (min === parseFloat(value.from) && max === parseFloat(value.to)) {
                    value = false;
                }
            }

            if (!value) {
                this.hide();
                return;
            }

            let text = '';

            if (Field.getAttribute('title')) {
                text = text + Field.getAttribute('title').trim() + ': ';
            }

            text = text + Field.getSearchValueFormatted();

            this.$Text.set('text', text);
            this.show();
        }
    });
});
