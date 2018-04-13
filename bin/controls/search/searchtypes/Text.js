/**
 * @module package/quiqqer/products/bin/controls/search/SearchField
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/Text', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/Text',

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
            this.$Elm = new Element('input', {
                'class': 'quiqqer-products-searchtype-text',
                type   : 'text'
            });

            this.$Elm.addEvents({
                change: function () {
                    this.fireEvent('change', [this]);
                }.bind(this)
            });

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

            this.$Elm.value = value.toString();
        },

        /**
         * set the search data
         *
         * @param {Object|Array} data
         */
        setSearchData: function (data) {

        },

        /**
         * Return the search value
         *
         * @returns {String}
         */
        getSearchValue: function () {
            return this.$Elm.value;
        }
    });
});
