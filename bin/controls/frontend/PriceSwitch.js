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
    'Ajax'

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

        initialize: function (options) {
            this.parent(options);

            this.$Switch = null;
            this.$Elm    = null;

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
            this.$Switch.$onInject();
        },

        /**
         * event : on change
         */
        $onChange: function () {
            var status = this.$Switch.getStatus();

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