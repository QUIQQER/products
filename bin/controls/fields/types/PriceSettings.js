/**
 * @module package/quiqqer/products/bin/controls/fields/types/PriceSettings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @todo not finish
 * @todo #locale
 * @todo locale
 */
define('package/quiqqer/products/bin/controls/fields/types/PriceSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/groups/Select',
    'css!package/quiqqer/products/bin/controls/fields/types/PriceSettings.css'

], function (QUI, QUIControl, GroupSelect) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/PriceSettings',

        Binds: [
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false,
            groups : [],

            ignoreForPriceCalculation: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Groups = null;
            this.$Ignore = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
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
                    width  : '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onInject: function () {
            var Parent = this.$Elm.getParent('.field-options');

            if (Parent) {
                Parent.setStyle('padding', 0);
            }

            new Element('div', {
                'class': 'quiqqer-products-price-settings',
                html   : '<div class="quiqqer-products-price-settings-groups">' +
                         '    <label>' +
                         '        <span class="quiqqer-products-price-settings-groups-text">' +
                         '            Gruppenzuweisung:' +
                         '        </span>' +
                         '        <div class="quiqqer-products-price-settings-groups-values"></div>' +
                         '    </label>' +
                         '</div>' +
                         '<div class="quiqqer-products-price-settings-ignoreForPriceCalculation">' +
                         '    <label>' +
                         '        <input type="checkbox" name="ignoreForPriceCalculation"/> Bei der Preisberechnung ignorieren' +
                         '    </label>' +
                         '</div>'
            }).inject(this.$Elm);

            this.$Groups = new GroupSelect({
                events: {
                    onChange: this.update
                },
                styles: {
                    height: 200
                }
            }).inject(
                this.$Elm.getElement('.quiqqer-products-price-settings-groups-values')
            );

            this.$Ignore = this.$Elm.getElement('[name="ignoreForPriceCalculation"]');
            this.$Ignore.addEvent('change', this.update);

            // values
            if (this.getAttribute('groups')) {
                this.getAttribute('groups').toString().split(',').each(function (gid) {
                    this.$Groups.addItem(gid);
                }.bind(this));
            }

            this.$Ignore.checked = this.getAttribute('ignoreForPriceCalculation') ? true : false;
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function (self, Node) {
            this.$Input = Node;
            this.$Elm   = this.create();

            var data = {};

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ("groups" in data) {
                    this.setAttribute('groups', data.groups.split(','));
                }

                if ("ignoreForPriceCalculation" in data) {
                    this.setAttribute('ignoreForPriceCalculation', data.ignoreForPriceCalculation);
                }

            } catch (e) {
                console.error(this.$Input.value);
                console.error(e);
            }

            if (!this.$data) {
                this.$data = [];
            }

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode({
                groups                   : this.$Groups.getValue(),
                ignoreForPriceCalculation: this.$Ignore.checked ? 1 : 0
            });
        }
    });
});
