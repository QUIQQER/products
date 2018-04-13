/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/SelectMulti
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/SelectMulti', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/SelectMulti',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.$Elm = null;

            this.parent(options);
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('select');
            this.$Elm.addClass('quiqqer-products-searchtype-selectmulti');

            return this.$Elm;
        },

        /**
         * Reset the field
         */
        reset: function () {
            this.$Elm.value = '';
            this.fireEvent('change', [this]);
        },

        /**
         * Set the input select value
         * @param value
         */
        setSearchValue: function (value) {
            this.setAttribute('value', value);

        },

        /**
         * set the search data
         *
         * @param {object|array} data
         */
        setSearchData: function (data) {

        },

        getSearchValue: function () {
            return '';
        }
    });
});
