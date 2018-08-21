/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/SelectRange
 * @author www.pcsg.de (Henning Leutz)
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
                range : {
                    min: 0,
                    max: 100
                },
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
            var min = this.$Select.getAttribute('min'),
                max = this.$Select.getAttribute('max');

            this.setSearchValue({
                from: min,
                to  : max
            });
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
