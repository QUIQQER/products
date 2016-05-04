/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/Bool
 * @author www.pcsg.de (Henning Leutz)
 *
 * Ein Ja / Nein Feld
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/Bool', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select'

], function (QUI, QUIControl, QUISelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/Bool',

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

            this.$Select.appendChild('Ja', 1);
            this.$Select.appendChild('Nein', 0);

            return this.$Elm;
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
         * @returns {String|Boolean}
         */
        getSearchValue: function () {
            return this.$Select.getValue();
        }
    });
});
