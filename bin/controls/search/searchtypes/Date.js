/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/Date
 * @author www.pcsg.de (Henning Leutz)
 *
 * Ein Datumsfeld
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/Date', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/Date',

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
                type: 'date'
            });

            this.$Elm.addClass('quiqqer-products-searchtype-date');

            var triggerChange = function () {
                this.fireEvent('change', [this]);
            }.bind(this);

            this.$Elm.addEvents({
                change: triggerChange
            });

            return this.$Elm;
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
