/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/InputSelectSingle
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onChange [this]
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/InputSelectSingle', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select'

], function (QUI, QUIControl, QUISelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/InputSelectSingle',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {

            this.$Select = null;
            this.$Elm    = null;
            this.$data   = null;

            this.parent(options);
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Select = new QUISelect({
                showIcons      : false,
                placeholderText: '---', // @todo locale
                styles         : {
                    width: '100%'
                },
                events         : {
                    onChange: function () {
                        this.fireEvent('change', [this]);
                    }.bind(this)
                }
            });

            this.$Elm = this.$Select.create();
            this.$Elm.addClass('quiqqer-products-searchtype-inputselectsingle');

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
            this.$data = data;
            this.refresh();
        },

        /**
         * Return the search value
         *
         * @returns {String}
         */
        getSearchValue: function () {
            return this.$Select.getValue();
        }
    });
});
