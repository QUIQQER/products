/**
 * @module package/quiqqer/products/bin/controls/frontend/category/Menu
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */

define('package/quiqqer/products/bin/controls/frontend/category/Menu', [

    'qui/QUI',
    'qui/controls/Control',
    'URI'

], function (QUI, QUIControl, URI) {
    "use strict";

    Element.NativeEvents.popstate = 2;

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
                if (typeof window.QUIQQER_PRODUCT_ID !== 'undefined') {
                    // Wenn die Seite ein Produkt ist, muss auf die Kategorie weitergeleitet werden
                    return;
                }

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

                // set to the locale storage
                if (this.checked) {
                    self.$selected[categoryId] = true;
                } else {
                    Object.erase(self.$selected, categoryId);
                }

                if (!("history" in window)) {
                    if (this.checked) {
                        Object.each(self.getProductLists(), function (List) {
                            List.addCategory(categoryId);
                        });
                    } else {
                        Object.each(self.getProductLists(), function (List) {
                            List.removeCategory(categoryId);
                        });
                    }

                    return;
                }

                // window popstate
                var Url = URI(window.location);

                Url.removeSearch('c');

                if (Object.getLength(self.$selected)) {
                    Url.addSearch('c', Object.keys(self.$selected).join(','));
                }

                window.history.pushState({}, "", Url.toString());
                window.fireEvent('popstate');
            });

            window.addEvent('popstate', function () {

            });

            // check the checkboxes
            var Url    = URI(window.location),
                search = Url.search(true);

            if ("c" in search) {
                var urlCategories = search.c.toString().split(',').map(function (v) {
                    return parseInt(v);
                });

                categories.each(function (Checkbox) {
                    Checkbox.checked = urlCategories.contains(
                        parseInt(Checkbox.value)
                    );

                    if (Checkbox.checked) {
                        self.$selected[parseInt(Checkbox.value)] = true;
                    }
                });
            }
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
        },

        /**
         * Return all available loaded product lists objects
         *
         * @returns {Promise}
         */
        $getLists: function () {
            var self = this;

            return new Promise(function (resolve) {
                if (Object.getLength(self.$lists)) {
                    return resolve(self.$lists);
                }

                var i, len, quiid;
                var nodes = document.getElements(
                    '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                );

                var promisesList = [];

                var promiseSolved = function (resolve) {
                    this.addEvent('load', resolve);
                };

                for (i = 0, len = nodes.length; i < len; i++) {
                    if (!nodes.hasOwnProperty(i)) {
                        continue;
                    }

                    quiid = nodes[i].get('data-quiid');

                    if (self.$lists.hasOwnProperty(quiid)) {
                        continue;
                    }

                    if (quiid) {
                        self.$lists[quiid] = QUI.Controls.getById(quiid);
                        continue;
                    }

                    promisesList.push(
                        new Promise(promiseSolved.bind(nodes[i]))
                    );
                }

                Promise.all(promisesList).then(function () {
                    document.getElements(
                        '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                    ).each(function (Node) {
                        var quiid = Node.get('data-quiid');

                        self.$lists[quiid] = QUI.Controls.getById(quiid);
                    });

                    resolve(self.$lists);
                });
            });
        }
    });
});