/**
 * @module package/quiqqer/products/bin/controls/frontend/category/Menu
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/category/Menu', [

    'qui/QUI',
    'qui/controls/Control',
    'utils/Session'

], function (QUI, QUIControl, Session) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/Menu',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Nav      = null;
            this.$lists    = {};
            this.$selected = {};

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm();

            // search product list
            this.getProductLists();

            // element events
            this.$Nav = Elm.getElement('.quiqqer-products-category-menu-navigation');

            this.$Nav.getElements('a').addEvent('click', function (event) {
                var Target = event.target,
                    Label  = Target.getParent('label');

                if (!Label) {
                    return;
                }

                var Input = Label.getElement('input');

                if (!Input) {
                    return;
                }

                event.stop();

                Input.checked = !Input.checked;
                Input.fireEvent('change');
            });

            var categories = this.$Nav.getElements('input[type="checkbox"]');

            categories.addEvent('change', function () {
                var categoryId = parseInt(this.value);

                if (!categoryId) {
                    this.checked = false;
                    return;
                }

                if (this.checked) {
                    Object.each(self.getProductLists(), function (List) {
                        List.addCategory(categoryId);
                    });
                } else {
                    Object.each(self.getProductLists(), function (List) {
                        List.removeCategory(categoryId);
                    });
                }

                // set to the locale storage
                if (this.checked) {
                    self.$selected[categoryId] = true;
                } else {
                    Object.erase(self.$selected, categoryId);
                }

                Session.set(
                    'quiqqer/products/productList/categories',
                    self.$selected
                );
            });

            Session.get(
                'quiqqer/products/productList/categories'
            ).then(function (keys) {
                if (!keys) {
                    return;
                }

                var i, len, categoryId;

                for (i = 0, len = categories.length; i < len; i++) {
                    categoryId = parseInt(categories[i].value);

                    if (categoryId in keys) {
                        categories[i].checked = true;
                    }
                }
            });
        },

        /**
         * Return all availabl product lists
         *
         * @returns {Object}
         */
        getProductLists: function () {
            if (Object.getLength(this.$lists)) {
                return this.$lists;
            }


            var self  = this,
                nodes = document.getElements(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                );

            var i, len, quiid;

            var onNodeLoad = function () {
                var qid          = this.get('data-quiid');
                self.$lists[qid] = QUI.Controls.getById(qid);
            };


            for (i = 0, len = nodes.length; i < len; i++) {
                if (!nodes.hasOwnProperty(i)) {
                    continue;
                }

                quiid = nodes[i].get('data-quiid');

                if (this.$lists.hasOwnProperty(quiid)) {
                    continue;
                }

                if (quiid) {
                    this.$lists[quiid] = QUI.Controls.getById(quiid);
                    continue;
                }

                nodes[i].addEvent('load', onNodeLoad.bind(nodes[i]));
            }

            return this.$lists;
        }
    });
});