/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/DateRange
 * @author www.pcsg.de (Henning Leutz)
 *
 * Ein Datumsfeld von bis - A date field from to
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/DateRange', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/DateRange',

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
            this.$Elm = new Element('input');
            this.$Elm.addClass('quiqqer-products-searchtype-daterange');

            return this.$Elm;
        },

        /**
         * Reset the field
         */
        reset: function () {

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
