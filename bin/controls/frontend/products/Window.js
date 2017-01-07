/**
 * @module package/quiqqer/products/bin/controls/frontend/products/Window
 *
 * @require qui/QUI
 * @require qui/controls/windows/Popup
 */
define('package/quiqqer/products/bin/controls/frontend/products/Window', [

    'qui/QUI',
    'qui/controls/windows/Popup'

], function (QUI, QUIWindow) {
    "use strict";

    return new Class({

        Type   : 'package/quiqqer/products/bin/controls/frontend/products/Window',
        Extends: QUIWindow,

        Binds: [
            '$onOpen'
        ],

        options: {
            productId: false,
            buttons  : false
        },

        initialize: function (options) {
            this.setAttributes({
                maxHeight: '100%',
                maxWidth : '100%'
            });

            this.parent(options);

            this.$Product = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            this.Loader.show();

            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            require([
                'package/quiqqer/products/bin/controls/frontend/products/Product'
            ], function (Product) {
                var Container = new Element('div', {
                    styles: {
                        margin  : '0 auto',
                        maxWidth: 1200
                    }
                }).inject(Content);

                self.$Product = new Product({
                    productId: self.getAttribute('productId'),
                    events   : {
                        onClose: function () {
                            self.close();
                        },
                        onLoad : function () {
                            self.Loader.hide();
                        }
                    }
                }).inject(Container);
            });
        }
    });
});