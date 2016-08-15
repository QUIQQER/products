/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require Locale
 *
 * @event onChange [this]
 *
 * mit eingabe vom benutzer
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange', [

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
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange',

        Binds: [
            '$onImport'
        ],

        options: {
            range  : false,
            snap   : true,
            connect: true,
            value  : false
        },

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
            var self = this;

            this.$Select = new QUIRange({
                range    : this.getAttribute('range'),
                snap     : this.getAttribute('snap'),
                connect  : this.getAttribute('connect'),
                styles   : {
                    width: '100%'
                },
                Formatter: function () {
                    return self.getSearchValueFormatted();
                },
                events   : {
                    change: function () {
                        self.fireEvent('change', [self]);
                    }
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
            if (typeOf(this.$data) === 'array') {
                var i, pc;
                var values = this.$data.map(function (entry) {
                    return parseFloat(entry.value);
                });

                values.sort(function (a, b) {
                    return a - b;
                });

                var len = values.length;

                var range = {
                    min: values[0],
                    max: values[len - 1]
                };

                // percent
                var percentStep = 100 / len;

                for (i = 1; i < len - 1; i++) {
                    pc = Math.round(percentStep * i);

                    range[pc + '%'] = values[i];
                }

                this.$Select.setRange(range);
                return;
            }


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
            if (typeOf(data) !== 'object' && typeOf(data) !== 'array') {
                return;
            }

            this.$data = data;
            this.refresh();

            // value
            if (this.getAttribute('value') !== false) {
                return;
            }

            var values = this.$data.map(function (entry) {
                return parseFloat(entry.value);
            });

            this.$Select.setValue([
                values[0],
                values[values.length - 1]
            ]);
        },

        /**
         * Set the input select value
         * @param {Array|String|Object} value
         */
        setSearchValue: function (value) {
            if (typeOf(value) === 'object') {
                var from = null, to = null;

                if ("from" in value) {
                    from = value.from;
                }

                if ("to" in value) {
                    to = value.to;
                }

                value = [from, to];
            }

            this.setAttribute('value', value);

            if (this.$Select) {
                this.$Select.setValue(value);
            }
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
