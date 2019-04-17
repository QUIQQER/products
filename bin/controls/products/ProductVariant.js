/**
 *
 *
 * @module package/quiqqer/products/bin/controls/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/ProductVariant', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/products/Product',
    'qui/controls/buttons/Select',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/ProductVariant.html',
    'css!package/quiqqer/products/bin/controls/products/ProductVariant.css'

], function (QUI, ProductPanel, QUISelect, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: ProductPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/ProductVariant',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title'),
                icon : 'fa fa-shopping-bag',
                '#id': "productId" in options ? options.productId : false
            });

            this.parent(options);

            this.$Variants = null;

            this.addEvents({
                onCreate: this.$onCreate
            });
        },

        /**
         * event: on create
         */
        $onCreate: function () {
            this.parent();


        },

        $onInject: function () {
            var self = this;

            this.parent().then(function () {
                self.addCategory({
                    name  : 'variants',
                    text  : 'VARIANTEN',
                    icon  : 'fa fa-info',
                    events: {
                        onClick: function () {
                            self.Loader.show();
                            self.openVariants().then(function () {
                                self.Loader.hide();
                            });
                        }
                    }
                });

                self.getCategoryBar().moveChildToPos(
                    self.getCategory('variants'),
                    1
                );
            });
        },

        /**
         * Open variants
         *
         * @return {Promise}
         */
        openVariants: function () {
            if (this.getCategory('variants').isActive()) {
                return Promise.resolve();
            }

            var self = this;

            return self.$hideCategories().then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }


            });
        },

        /**
         *
         */
        selectVariant: function () {
            var self = this;

            return self.$hideCategories().then(function () {
                var Body         = self.getBody();
                var VariantSheet = Body.getElement('variants-sheet');

                if (!VariantSheet) {
                    VariantSheet = new Element('div', {
                        'class': 'variants-sheet sheet'
                    }).inject(Body);
                }


            });

            this.minimizeCategory();

            VariantSheet.set('html', Mustache.render(template));


            // @todo categorien ausgrauen wenn offen
            // @todo categorien klein machen wenn variante ausgewählt ist
            // @todo grid aller varianten anzeigen wenn keine variante ausgewählt ist


            var VariantList = self.getBody().getElement('.variant-list');
            var VariantTabs = self.getBody().getElement('.variants-tabs');
            var VariantBody = self.getBody().getElement('.variant-body');

            var VariantSelect = new QUISelect({
                placeholder: 'Variante wechseln',
                styles     : {
                    width: '70%'
                }
            }).inject(VariantList);

            VariantSelect.appendChild('Zu variante wechseln: VC100');
            VariantSelect.appendChild('Zu variante wechseln: VC101');
            VariantSelect.appendChild('Zu variante wechseln: VC102');

            VariantTabs.set('html', 'test');
            VariantBody.set('html', 'test');
        }
    });
});