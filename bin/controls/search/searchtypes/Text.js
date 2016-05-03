/**
 * @module package/quiqqer/products/bin/controls/search/SearchField
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
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
                'class' : 'quiqqer-products-searchtype-text'
            });

            return this.$Elm;
        },

        /**
         * set the search data
         *
         * @param {object|array} data
         */
        setSearchData: function (data) {
            this.$Elm.value = data.toString();
        }
    });
});
