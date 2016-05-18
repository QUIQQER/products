/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/SelectRange
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/SelectRange', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select'

], function (QUI, QUIControl, QUISelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/SelectRange',

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

            this.refresh();

            return this.$Elm;
        },

        /**
         * Refresh the control
         */
        refresh: function () {
            if (!this.$Select || !this.$data) {
                return;
            }

            this.$Select.clear();

            for (var i = 0, len = this.$data.length; i < len; i++) {
                this.$Select.appendChild(
                    this.$data[i].label,
                    this.$data[i].value
                );
            }
        },

        /**
         * set the search data
         *
         * @param {object|array} data
         */
        setSearchData: function (data) {
            this.$data = data;
            this.refresh();
        },

        getSearchValue: function () {
            return '';
        }
    });
});