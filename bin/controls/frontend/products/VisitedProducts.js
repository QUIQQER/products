/**
 * @module package/quiqqer/products/bin/controls/frontend/products/VisitedProducts
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 * @require package/quiqqer/products/bin/Products
 */
define('package/quiqqer/products/bin/controls/frontend/products/VisitedProducts', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'package/quiqqer/products/bin/Products'

], function (QUI, QUIControl, QUIAjax, QUILocale, Products) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/VisitedProducts',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$List   = null;
            this.$Slider = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var SliderNode = this.getElm().getElement('.quiqqer-bricks-children-slider'),
                Parse      = Promise.resolve();

            if (!SliderNode.get('data-quiid')) {
                Parse = QUI.parse(this.getElm());
            }

            Parse.then(function () {
                this.$Slider = QUI.Controls.getById(SliderNode.get('data-quiid'));

                this.$List = this.getElm().getElement(
                    'ul.quiqqer-bricks-children-slider-container-slide'
                );

                // load products
                var visited = Products.getVisitedProductIds();

                if (!visited.length) {
                    new Element('div', {
                        'class': 'quiqqer-products-control-visitedProducts-empty',
                        html   : QUILocale.get(lg, 'brick.control.VisitedProducts.empty')
                    }).replaces(this.$List);
                    return;
                }

                QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getVisitedProducts', function (result) {
                    var Ghost = new Element('div', {
                        html: result
                    });

                    var List = Ghost.getElement(
                        'ul.quiqqer-bricks-children-slider-container-slide'
                    );

                    var Result = List.getElements('li');

                    if (!Result.length) {
                        new Element('div', {
                            'class': 'quiqqer-products-control-visitedProducts-empty',
                            html   : QUILocale.get(lg, 'brick.control.VisitedProducts.empty')
                        }).replaces(this.$List);
                        return;
                    }

                    this.$List.set('html', List.get('html'));
                    this.$List.getElements('a').addEvents({
                        click: function (event) {
                            var Target = event.target;

                            if (Target.nodeName !== 'A') {
                                Target = Target.getParent('a');
                            }

                            var List = document.getElement(
                                '[data-qui="package/quiqqer/products/bin/controls/frontend/category/ProductList"]'
                            );

                            if (!List) {
                                return;
                            }

                            event.stop();

                            List = QUI.Controls.getById(List.get('data-quiid'));
                            List.openProduct(Target.get('data-pid'));
                        }
                    });

                    this.$Slider.resize();
                }.bind(this), {
                    'package'       : 'quiqqer/products',
                    productIds      : JSON.encode(visited),
                    currentProductId: window.QUIQQER_PRODUCT_ID || 0
                });

            }.bind(this));
        }
    });
});