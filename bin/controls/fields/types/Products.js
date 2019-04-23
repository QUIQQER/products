/**
 * Settings for field Products
 *
 * @module package/quiqqer/products/bin/controls/fields/types/Products
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/Products', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/products/Select'

], function (QUI, QUIControl, ProductSelect) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Products',

        Binds: [
            '$onImport'
        ],

        options: {
            value: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Select = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    height : '100%',
                    width  : '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onInject: function () {
            this.$Select = new ProductSelect({
                multiple: true,
                styles  : {
                    height: '100%',
                    width : '100%'
                }
            }).inject(this.$Elm);

            var value = this.getAttribute('value');

            if (typeOf(value) === 'array') {
                for (var i = 0, len = value.length; i < len; i++) {
                    if (value[i] !== '') {
                        this.$Select.addItem(value[i]);
                    }
                }
            }
        },

        /**
         * Return the value
         *
         * @return {Array}
         */
        save: function () {
            return this.$Select.getValue().split(',');
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Select.getValue();
        }
    });
});
