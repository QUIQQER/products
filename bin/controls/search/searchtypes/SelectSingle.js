/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/SelectSingle
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/SelectSingle', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select'

], function (QUI, QUIControl, QUISelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/SelectSingle',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {

            this.$Elm    = null;
            this.$Select = null;

            this.parent(options);
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Select = new QUISelect();
            this.$Elm    = this.$Select.create();
            this.$Elm.addClass('quiqqer-products-searchtype-selectsingle');

            return this.$Elm;
        },

        /**
         * Reset the field
         */
        reset: function () {
            this.$Select.setValue(
                this.$Select.firstChild().getValue()
            )
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

        /**
         * Return the search value
         *
         * @returns {String}
         */
        getSearchValue: function () {
            return '';
        }
    });
});
