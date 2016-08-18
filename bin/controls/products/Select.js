/**
 * Makes an input field to a user selection field
 *
 * @module package/quiqqer/products/bin/controls/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/classes/Products
 * @require Locale
 *
 * @event onAddProduct [ this, id ]
 * @event onChange [ this ]
 */
define('package/quiqqer/products/bin/controls/products/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'package/quiqqer/products/bin/classes/Products',
    'Locale'

], function (QUI, QUIElementSelect, Handler, QUILocale) {
    "use strict";

    var lg       = 'quiqqer/products';
    var Products = new Handler();

    /**
     * @class package/quiqqer/products/bin/controls/products/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'package/quiqqer/products/bin/controls/products/Select',

        Binds: [
            '$onSearchButtonClick',
            'productSearch'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('Search', this.productSearch);
            this.setAttribute('icon', 'fa fa-shopping-bag');
            this.setAttribute('child', 'package/quiqqer/products/bin/controls/products/SelectItem');

            this.setAttribute(
                'placeholder',
                QUILocale.get(lg, 'control.select.search.placeholder')
            );

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },

        /**
         * Search areas
         *
         * @param {String} value
         * @returns {Promise}
         */
        productSearch: function (value) {
            return Products.search({
                freetext: value
            });
        },

        /**
         * event : on search button click
         *
         * @param self
         * @param Btn
         */
        $onSearchButtonClick: function (self, Btn) {
            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'package/quiqqer/products/bin/controls/products/search/Window'
            ], function (Search) {
                new Search({
                    events: {
                        onSubmit: function (Win, values) {
                            for (var i = 0, len = values.length; i < len; i++) {
                                self.addItem(values[i]);
                            }
                        }
                    }
                }).open();

                Btn.setAttribute('icon', 'fa fa-search');
            });
        }
    });
});
