/**
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductListFilter
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductListFilter', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',

    'css!package/quiqqer/products/bin/controls/frontend/category/ProductListFilter.css'

], function (QUI, QUIControl, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductListFilter',

        Binds: [
            '$onInject',
            '$onFieldChange',
            '$onDestroy',
            'destroy'
        ],

        options: {
            tag: false,
            Field: null
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject,
                onDestroy: this.$onDestroy
            });
        },

        /**
         * Return new div element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class'   : 'quiqqer-products-productList-filter',
                html      : '<div class="quiqqer-products-productList-filter-text"></div>' +
                '<div class="quiqqer-products-productList-filter-destroy">' +
                '    <span class="fa fa-close"></span>' +
                '</div>'
            });

            if (this.getAttribute('Field')) {
                const Field = this.getAttribute('Field');

                this.$Elm.set('data-field', Field.getAttribute('fieldid'));
                this.$Elm.set('data-field-value', this.getAttribute('tag'));

                Field.addEvent('change', this.$onFieldChange);
            } else {
                this.$Elm.set('data-tag', this.getAttribute('tag'));
            }

            this.$Text   = this.$Elm.getElement('.quiqqer-products-productList-filter-text');
            this.$Cancel = this.$Elm.getElement('.quiqqer-products-productList-filter-destroy');

            this.$Cancel.addEvent('click', this.destroy);

            return this.$Elm;
        },

        $onFieldChange: function() {
            if (!this.getAttribute('Field')) {
                this.$onDestroy();
                return;
            }

            if (!this.$Elm) {
                this.$onDestroy();
                return;
            }

            const Field      = this.getAttribute('Field');
            const fieldValue = this.getAttribute('tag');
            const values     = Field.getSearchValue();

            if (!values || values.indexOf(fieldValue) === -1) {
                this.hide();

                (() => {
                    this.destroy();
                }).delay(200);
            }
        },

        $onDestroy: function() {
            if (this.getAttribute('Field')) {
                this.getAttribute('Field').removeEvent('change', this.$onFieldChange);
            }
        },

        /**
         * event : on refresh
         */
        $onInject: function () {
            this.refresh().catch(console.error);
        },

        /**
         * Refresh the tag display
         */
        refresh: function () {
            return new Promise((resolve) => {
                this.$Text.set('html', '<span class="fa fa-spinner fa-spin"></span>');

                if (this.getAttribute('Field')) {
                    const SearchField = this.getAttribute('Field');
                    const Label = SearchField.getElm().getParent('label');

                    let title = '';
                    let fieldValue = this.getAttribute('tag');

                    if (Label && Label.getElement('.quiqqer-products-productList-filter-entry-title')) {
                        title = Label.getElement('.quiqqer-products-productList-filter-entry-title').get('html').trim();
                        title = title +': ';
                    }

                    this.$Text.set('text', title + fieldValue);
                    resolve();

                    return;
                }

                QUIAjax.get('package_quiqqer_tags_ajax_tag_get', (result) => {
                    this.$Text.set('text', result.title || result.tag);
                    resolve();
                }, {
                    'package'  : 'quiqqer/tags',
                    projectName: window.QUIQQER_PROJECT.name,
                    projectLang: window.QUIQQER_PROJECT.lang,
                    tag        : this.getAttribute('tag')
                });
            });
        }
    });
});