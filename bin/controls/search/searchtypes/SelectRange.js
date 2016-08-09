/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/SelectRange
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require Locale
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/SelectRange', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/input/Range',
    'Locale'

], function (QUI, QUIControl, QUIRange, QUILocale) {
    "use strict";

    var NumberFormatter = QUILocale.getNumberFormatter({
        style   : 'currency',
        currency: window.DEFAULT_CURRENCY || 'EUR'
    });

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/SelectRange',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.$Elm    = null;
            this.$Select = null;
            this.$data   = {};

            this.parent(options);
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Select = new QUIRange({
                range : true,
                styles: {
                    width: '100%'
                },

                Formatter: function () {
                    return this.getSearchValueFormatted();
                }.bind(this),

                events: {
                    change: function () {
                        this.fireEvent('change', [this]);
                    }.bind(this)
                }
            });

            this.$Elm = this.$Select.create();
            this.$Elm.addClass('quiqqer-products-searchtype-selectrange');

            this.refresh();

            return this.$Elm;
        },

        /**
         * Refresh the control
         */
        refresh: function () {
            if ('from' in this.$data && 'to' in this.$data) {
                this.$Select.setValue([
                    this.$data.from,
                    this.$data.to
                ]);
                return;
            }

            if ('from' in this.$data) {
                this.$Select.setFrom(this.$data.from);
                return;
            }

            if ('to' in this.$data) {
                this.$Select.setTo(this.$data.to);
            }
        },

        /**
         * Reset the field
         */
        reset: function () {

        },

        /**
         * set the search data
         *
         * @param {Object|Array} data
         */
        setSearchData: function (data) {
            if (typeOf(data) !== 'object') {
                return;
            }

            this.$data = data;
            this.refresh();
        },

        /**
         * Return the search value
         *
         * @returns {Object}
         */
        getSearchValue: function () {
            return this.$Select.getValue();
        },

        /**
         * Return the value formatted
         *
         * @returns {string}
         */
        getSearchValueFormatted: function () {
            var value = this.getSearchValue();

            return NumberFormatter.format(value.from) +
                   ' bis ' + NumberFormatter.format(value.to);
        }
    });
});
