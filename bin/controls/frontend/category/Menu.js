/**
 * @module package/quiqqer/products/bin/controls/frontend/category/Menu
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/frontend/category/Menu', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/Menu',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Nav  = null;
            this.$List = null;

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

            var ProductListNode = document.getElement(
                '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
            );

            var quiid = ProductListNode.get('data-quiid');

            if (!quiid) {
                ProductListNode.addEvent('load', function () {
                    this.$List = QUI.Controls.getById(
                        ProductListNode.get('data-quiid')
                    );
                }.bind(this));
            } else {
                this.$List = QUI.Controls.getById(quiid);
            }

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

            this.$Nav.getElements('input[type="checkbox"]').addEvent('change', function () {
                if (!self.$List) {
                    console.log('No list found');
                    return;
                }

                var categoryId = parseInt(this.value);

                if (!categoryId) {
                    this.checked = false;
                    return;
                }

                if (this.checked) {
                    self.$List.addCategory(categoryId);
                } else {
                    self.$List.removeCategory(categoryId);
                }
            });
        }
    });
});