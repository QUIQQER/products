/**
 * @todo not finish
 * @todo #locale
 * @todo locale
 */
define('package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/groups/Select',
    'css!package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings.css'

], function (QUI, QUIControl, GroupSelect) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/PriceByTimePeriodSettings',

        Binds: [
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false,
            groups: []
        },

        initialize: function (options) {
            this.parent(options);

            this.$Groups = null;

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

            new Element('div', {
                'class': 'quiqqer-products-priceByTimePeriod-settings',
                html: '<div class="quiqqer-products-priceByTimePeriod-settings-groups">' +
                    '    <label>' +
                    '        <span class="quiqqer-products-priceByTimePeriod-settings-groups-text">' +
                    '            Gruppenzuweisung:' +
                    '        </span>' +
                    '        <div class="quiqqer-products-priceByTimePeriod-settings-groups-values"></div>' +
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
                this.$Elm.getElement('.quiqqer-products-priceByTimePeriod-settings-groups-values')
            );

            // values
            if (this.getAttribute('groups')) {
                this.getAttribute('groups').toString().split(',').each(function (gid) {
                    this.$Groups.addItem(gid);
                }.bind(this));
            }
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
                groups: this.$Groups.getValue()
            });
        }
    });
});
