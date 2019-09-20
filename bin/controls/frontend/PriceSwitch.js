/**
 * @module package/quiqqer/products/bin/controls/frontend/PriceSwitch
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/PriceSwitch', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/frontend/PriceSwitch.css'

], function (QUI, QUIControl, QUISwitch, QUIAjax, QUILocale) {
    "use strict";

    if (typeof window.QUIQQER_PRODUCTS_HIDE_PRICE === 'undefined') {
        window.QUIQQER_PRODUCTS_HIDE_PRICE = false;
    }

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/PriceSwitch',

        Binds: [
            '$onInject',
            '$onChange'
        ],

        options: {
            icon: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Switch = null;
            this.$Elm    = null;
            this.$Icon   = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @returns {*|Element|null}
         */
        create: function () {
            if (this.getAttribute('icon')) {
                this.$Elm = new Element('div', {
                    'class': 'quiqqer-products-priceSwitch',
                    html   : '<span class="fa"></span>',
                    title  : QUILocale.get(
                        'quiqqer/products',
                        'controls.PriceSwitch.title.hide_prices'
                    ),
                    styles : {
                        cursor: 'pointer'
                    },
                    events : {
                        click: this.$onChange
                    }
                });

                this.$Icon = this.$Elm.getElement('span');

                this.$Icon.addClass(
                    this.getAttribute('icon')
                );

                if (window.QUIQQER_PRODUCTS_HIDE_PRICE) {
                    this.$Elm.set('title', QUILocale.get(
                        'quiqqer/products',
                        'controls.PriceSwitch.title.show_prices'
                    ));

                    this.$Elm.addClass('quiqqer-products-priceSwitch-hidePrice');
                }

                return this.$Elm;
            }

            this.$Switch = new QUISwitch({
                status: window.QUIQQER_PRODUCTS_HIDE_PRICE,
                events: {
                    onChange: this.$onChange
                }
            });

            this.$Elm = this.$Switch.create();

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            if (!this.getAttribute('icon')) {
                this.$Switch.$onInject();
            }
        },

        /**
         * event : on change
         */
        $onChange: function () {
            var status;

            if (this.getAttribute('icon')) {
                status = !this.$Elm.hasClass('quiqqer-products-priceSwitch-hidePrice');
            } else {
                status = this.$Switch.getStatus();
            }

            if (status == window.QUIQQER_PRODUCTS_HIDE_PRICE) {
                return;
            }

            window.QUIQQER_PRODUCTS_HIDE_PRICE = status;

            if (status) {
                this.$Elm.set('title', QUILocale.get(
                    'quiqqer/products',
                    'controls.PriceSwitch.title.show_prices'
                ));
            } else {
                this.$Elm.set('title', QUILocale.get(
                    'quiqqer/products',
                    'controls.PriceSwitch.title.hide_prices'
                ));
            }

            QUIAjax.post('ajax_session_set', function () {
                window.location.reload();
            }, {
                key  : 'QUIQQER_PRODUCTS_HIDE_PRICE',
                value: status ? 1 : 0
            });
        }
    });
});