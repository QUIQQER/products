define('package/quiqqer/products/bin/controls/fields/types/PriceSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/groups/Select',
    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/types/PriceSettings.css'

], function (QUI, QUIControl, GroupSelect, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/PriceSettings',

        Binds: [
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false,
            groups: [],

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
                    width: '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onInject: function () {
            const Parent = this.$Elm.getParent('.field-options');

            if (Parent) {
                Parent.setStyle('padding', 0);
            }

            const localePriceCalcTitle = QUILocale.get('quiqqer/products', 'fieldConsiderPriceCalculation')
            const fieldConsiderPriceCalculationGroup = QUILocale.get('quiqqer/products', 'fieldConsiderPriceCalculationGroup')

            new Element('div', {
                'class': 'quiqqer-products-price-settings',
                html: '<div class="quiqqer-products-price-settings-groups">' +
                    '    <label>' +
                    '        <span class="quiqqer-products-price-settings-groups-text">' +
                    fieldConsiderPriceCalculationGroup + ':' +
                    '        </span>' +
                    '        <div class="quiqqer-products-price-settings-groups-values"></div>' +
                    '    </label>' +
                    '</div>' +
                    '<div class="quiqqer-products-price-settings-ignoreForPriceCalculation">' +
                    '    <label>' +
                    '        <input type="checkbox" name="ignoreForPriceCalculation"/> ' + localePriceCalcTitle +
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

            this.$Ignore.checked = !!this.getAttribute('ignoreForPriceCalculation');
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function (self, Node) {
            this.$Input = Node;
            this.$Elm = this.create();

            let data = {};

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
                groups: this.$Groups.getValue(),
                ignoreForPriceCalculation: this.$Ignore.checked ? 1 : 0
            });
        }
    });
});
