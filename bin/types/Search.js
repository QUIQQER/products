/**
 * @module package/quiqqer/products/bin/types/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Search functionality -> JavaScript
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require URI
 */

define('package/quiqqer/products/bin/types/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'URI'

], function (QUI, QUIControl, URI) {
    "use strict";

    Element.NativeEvents.popstate = 2;

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/types/Search',

        initialize: function (options) {
            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            var Elm  = this.getElm(),
                Form = Elm.getElement('.quiqqer-products-search-form');

            this.$Search = Elm.getElement('[type="search"]');

            // form event
            Form.addEvent('submit', function (event) {
                event.stop();

                this.$Search.focus();
                this.$setWindowLocation();

                // prodctlist search execute
                var nodelist = this.getElm().getElements(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                );

                var i, len, ProductList;

                for (i = 0, len = nodelist.length; i < len; i++) {
                    ProductList = QUI.Controls.getById(nodelist[i].get('data-quiid'));

                    if (ProductList) {
                        ProductList.execute();
                    }
                }

            }.bind(this));

            var Url   = URI(window.location),
                query = Url.query(true);

            if ("search" in query) {
                this.$Search.set('value', query.search);
            }
        },

        /**
         * Read the searhc input and set the search=* query
         */
        $setWindowLocation: function () {
            var Url = URI(window.location);
            Url.setSearch('search', this.$Search.value);

            var url = location.pathname + '?' + Object.toQueryString(Url.query(true));

            if ("origin" in location) {
                url = location.origin + url;
            }

            window.history.pushState({}, "", url);
        }
    });
});