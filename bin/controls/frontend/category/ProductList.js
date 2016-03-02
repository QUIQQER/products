/**
 * Category view
 * Display a product
 *
 * @module package/quiqqer/products/bin/controls/frontend/category/ProductList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/category/ProductList', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax'

], function (QUI, QUIControl, Ajax) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/ProductList',

        Binds: [
            '$onInject',
            'galleryView',
            'detailView',
            'listView'
        ],

        options: {
            categoryId: false,
            view      : 'gallery',
            sort      : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$ButtonDetails = null;
            this.$ButtonGallery = null;
            this.$ButtonList    = null;

            this.$BarSort     = null;
            this.$BarDisplays = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            console.log('inject');
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$ButtonDetails = Elm.getElement('.quiqqer-products-productList-sort-display-details');
            this.$ButtonGallery = Elm.getElement('.quiqqer-products-productList-sort-display-gallery');
            this.$ButtonList    = Elm.getElement('.quiqqer-products-productList-sort-display-list');

            this.$BarSort     = Elm.getElement('.quiqqer-products-productList-sort-sorting');
            this.$BarDisplays = Elm.getElement('.quiqqer-products-productList-sort-display');

            this.$ButtonDetails.addEvent('click', this.detailView);
            this.$ButtonGallery.addEvent('click', this.galleryView);
            this.$ButtonList.addEvent('click', this.listView);

            switch (this.getAttribute('view')) {
                case 'details':
                    this.$ButtonDetails.addClass('active');
                    break;
                case 'gallery':
                    this.$ButtonGallery.addClass('active');
                    break;
                case 'list':
                    this.$ButtonList.addClass('active');
                    break;
            }

            this.$BarSort.setStyle('display', null);
            this.$BarDisplays.setStyle('display', null);
        },

        /**
         * Change to gallery view
         */
        galleryView: function () {
            this.resetButtons();
            this.$ButtonGallery.addClass('active');
            this.setAttribute('view', 'gallery');
            this.$loadData();
        },

        /**
         * Change to detail view
         */
        detailView: function () {
            this.resetButtons();
            this.$ButtonDetails.addClass('active');
            this.setAttribute('view', 'detail');
            this.$loadData();
        },

        /**
         * Change to list view
         */
        listView: function () {
            this.resetButtons();
            this.$ButtonList.addClass('active');
            this.setAttribute('view', 'list');
            this.$loadData();
        },

        /**
         * remove all active class from the buttons
         */
        resetButtons: function () {
            this.$ButtonDetails.removeClass('active');
            this.$ButtonGallery.removeClass('active');
            this.$ButtonList.removeClass('active');
        },

        /**
         * Load the data view
         */
        $loadData: function () {
            var view = this.getAttribute('view'),
                sort = this.getAttribute('sort');

            Ajax.get('package_quiqqer_products_ajax_controls_categories_productList', function (result) {
console.warn(result);
            }, {
                'package': 'quiqqer/products',
                view     : view,
                sort     : sort,
                page     : 0
            });
        }
    });
});
