/**
 * @module package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 *
 * @event onChange [this]
 */
define('package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select'

], function (QUI, QUIControl, QUISelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange',

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
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-searchtype-inputselectrange',
                styles : {
                    width: '100%'
                }
            });

            this.$SelectFrom = new QUISelect({
                showIcons      : false,
                placeholderText: 'Von',
                styles         : {
                    margin: '0 2px 0 0',
                    width : 'calc(50% - 2px)'
                }
            });

            this.$SelectFrom.addEvent('change', function () {
                this.fireEvent('change', [this]);
            }.bind(this));


            this.$SelectTo = new QUISelect({
                showIcons      : false,
                placeholderText: 'Bis',
                styles         : {
                    margin: '0 0 0 2px',
                    width : 'calc(50% - 2px)'
                }
            });

            this.$SelectTo.addEvent('change', function () {
                this.fireEvent('change', [this]);
            }.bind(this));


            this.$SelectFrom.inject(this.$Elm);
            this.$SelectTo.inject(this.$Elm);


            this.refresh();

            return this.$Elm;
        },

        /**
         * Refresh the control
         */
        refresh: function () {
            if (!this.$SelectFrom || !this.$data) {
                return;
            }

            this.$SelectFrom.clear();
            this.$SelectTo.clear();

            for (var i = 0, len = this.$data.length; i < len; i++) {
                this.$SelectFrom.appendChild(
                    this.$data[i].label,
                    this.$data[i].value
                );

                this.$SelectTo.appendChild(
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

        /**
         * Return the search value
         *
         * @returns {Object}
         */
        getSearchValue: function () {
            return {
                from: this.$SelectFrom.getValue(),
                to  : this.$SelectTo.getValue()
            };
        }
    });
});
