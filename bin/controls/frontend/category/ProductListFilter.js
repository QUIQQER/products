/**
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductListFilter
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
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
            'destroy'
        ],

        options: {
            tag: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
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
                            '    <span class="fa fa-remove"></span>' +
                            '</div>',
                'data-tag': this.getAttribute('tag')
            });

            this.$Text   = this.$Elm.getElement('.quiqqer-products-productList-filter-text');
            this.$Cancel = this.$Elm.getElement('.quiqqer-products-productList-filter-destroy');

            this.$Cancel.addEvent('click', this.destroy);

            return this.$Elm;
        },

        /**
         * event : on refresh
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * Refresh the tag display
         */
        refresh: function () {
            return new Promise(function () {
                this.$Text.set('html', '<span class="fa fa-spinner fa-spin"></span>');

                QUIAjax.get('package_quiqqer_tags_ajax_tag_get', function (result) {
                    this.$Text.set('text', result.title || result.tag);
                }.bind(this), {
                    'package'  : 'quiqqer/tags',
                    projectName: QUIQQER_PROJECT.name,
                    projectLang: QUIQQER_PROJECT.lang,
                    tag        : this.getAttribute('tag')
                });
            }.bind(this));
        }
    });
});