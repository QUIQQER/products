/**
 * @module package/quiqqer/products/bin/controls/frontend/PriceSwitch
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Switch
 */
define('package/quiqqer/products/bin/controls/frontend/PriceSwitch', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'Ajax',

    'css!package/quiqqer/products/bin/controls/frontend/PriceSwitch.css'

], function (QUI, QUIControl, QUISwitch, QUIAjax) {
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

            QUIAjax.post('ajax_session_set', function () {
                window.location.reload();
            }, {
                key  : 'QUIQQER_PRODUCTS_HIDE_PRICE',
                value: status ? 1 : 0
            });
        }
    });
});