/**
 * @module package/quiqqer/products/bin/controls/fields/windows/PriceBrutto
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/windows/PriceBrutto', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale',
    'Ajax',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/windows/PriceBrutto.html',
    'css!package/quiqqer/products/bin/controls/fields/windows/PriceBrutto.css'

], function (QUI, QUIConfirm, QUILocale, QUIAjax, Mustache, template) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/fields/windows/PriceBrutto',

        options: {
            productId: false
        },

        Binds: [
            '$onOpen'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                icon     : 'fa fa-calculator',
                title    : QUILocale.get(lg, 'control.window.price.brutto.title'),
                maxHeight: 400,
                maxWidth : 600
            });

            this.addEvents({
                onOpen: this.$onOpen
            });

            // admin format
            this.$Formatter = QUILocale.getNumberFormatter({
                //style                : 'currency',
                //currency             : 'EUR',
                minimumFractionDigits: 8
            });
        },

        /**
         * Return the domnode element
         *
         * @return {Element}
         */
        $onOpen: function () {
            const self    = this,
                  Content = this.getContent();

            Content.set('html', Mustache.render(template, {
                title      : QUILocale.get(lg, 'control.window.price.brutto.label'),
                description: QUILocale.get(lg, 'control.window.price.brutto.description')
            }));

            Content.addClass('price-brutto-window');
            Content.getElement('input').placeholder = this.$Formatter.format(1000);

            Content.getElement('form').addEvent('submit', function (event) {
                event.stop();
                self.submit();
            });

            this.getContent().getElement('input').focus();
        },

        /**
         * submit the window
         */
        submit: function () {
            const self = this;

            this.Loader.show();

            QUIAjax.get('package_quiqqer_products_ajax_products_calcNettoPrice', function (price) {
                self.fireEvent('submit', [
                    self,
                    price
                ]);

                self.close();
            }, {
                'package': 'quiqqer/products',
                price    : this.getContent().getElement('input').value,
                productId: this.getAttribute('productId')
            });
        }
    });
});
