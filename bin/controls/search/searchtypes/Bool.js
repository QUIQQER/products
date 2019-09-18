/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/Bool
 * @author www.pcsg.de (Henning Leutz)
 *
 * Yes / No Field
 * Ein Ja / Nein Feld
 *
 * @event onChange
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/Bool', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'Locale'

], function (QUI, QUIControl, QUISelect, QUILocale) {
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
            this.$Select = new QUISelect({
                showIcons            : false,
                placeholderText      : '---',
                placeholderSelectable: true
            });

            this.$Elm = this.$Select.create();
            this.$Elm.addClass('quiqqer-products-searchtype-bool');

            this.$Select.appendChild(QUILocale.get('quiqqer/system', 'yes'), 1);
            this.$Select.appendChild(QUILocale.get('quiqqer/system', 'no'), 0);

            this.$Select.addEvent('change', function () {
                this.fireEvent('change', [this]);
            }.bind(this));

            return this.$Elm;
        },

        /**
         * Reset the field
         */
        reset: function () {
            this.$Select.setValue(0);
        },

        /**
         * set the search data
         *
         * @param {object|array} data
         */
        setSearchData: function (data) {

        },

        /**
         * Set the input select value
         * @param value
         */
        setSearchValue: function (value) {
            this.setAttribute('value', value);

        },

        /**
         * Return the search value
         *
         * @returns {String|Boolean}
         */
        getSearchValue: function () {
            return this.$Select.getValue();
        },

        /**
         * Return the search value formatted (yes, no)
         *
         * @return {Promise}
         */
        getSearchValueFormatted: function () {
            if (this.$Select.getValue()) {
                return QUILocale.get('quiqqer/system', 'yes');
            }

            return QUILocale.get('quiqqer/system', 'no');
        }
    });
});
