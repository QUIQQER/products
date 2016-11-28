/**
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductListField
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require package/quiqqer/products/bin/controls/frontend/category/ProductListFilter
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductListField', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter'

], function (QUI, ProductListFilter) {
    "use strict";

    return new Class({

        Extends: ProductListFilter,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductListField',

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
            this.$Elm = new Element('div', {
                'class'   : 'quiqqer-products-productList-filter',
                html      : '<div class="quiqqer-products-productList-filter-text"></div>',
                'data-tag': this.getAttribute('tag')
            });

            this.$Text = this.$Elm.getElement('.quiqqer-products-productList-filter-text');

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
         * refresh the display,
         * if no value exists, the ListField are hidden
         */
        refresh: function () {
            if (!this.getAttribute('Field')) {
                this.hide();
                return;
            }

            var value = this.getAttribute('Field').getSearchValue();

            if (!value) {
                this.hide();
                return;
            }

            var Field = this.getAttribute('Field'),
                text  = '';

            if (Field.getAttribute('title')) {
                text = text + Field.getAttribute('title').trim() + ': ';
            }

            text = text + Field.getSearchValueFormatted();

            console.warn(this.getElm());
            console.info(value);

            this.$Text.set('text', text);
            this.show();
        }
    });
});