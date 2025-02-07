/**
 * Settings for the "UserInput" field type
 *
 * @module package/quiqqer/products/bin/controls/fields/types/UserInputSettings
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/fields/types/UserInputSettings', [

    'qui/controls/Control',
    'Locale',
    'qui/utils/Form',

    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/UserInputSettings.html',
    'css!package/quiqqer/products/bin/controls/fields/types/UserInputSettings.css'

], function(QUIControl, QUILocale, QUIFormUtils, Mustache, template) {
    'use strict';

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/UserInputSettings',

        Binds: [
            'create',
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false,
            groups: [],

            autoActivateItems: false
        },

        initialize: function(options) {
            this.parent(options);

            this.$CheckboxAutoActivate = null;

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
        create: function() {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-userinput-settings',
                html: Mustache.render(template, {
                    labelInputType: QUILocale.get(lg, 'controls.UserInputSettings.tpl.labelInputType'),
                    labelInputTypeOptionInput: QUILocale.get(
                        lg,
                        'controls.UserInputSettings.tpl.labelInputTypeOptionInput'
                    ),
                    labelInputTypeOptionInputInline: QUILocale.get(
                        lg,
                        'controls.UserInputSettings.tpl.labelInputTypeOptionInputInline'
                    ),
                    labelInputTypeOptionTextarea: QUILocale.get(
                        lg,
                        'controls.UserInputSettings.tpl.labelInputTypeOptionTextarea'
                    ),
                    labelMaxCharacters: QUILocale.get(lg, 'controls.UserInputSettings.tpl.labelMaxCharacters')
                }),
                styles: {
                    'float': 'left',
                    width: '100%'
                }
            });

            this.$Elm.getElements('input').addEvent('change', this.update);
            this.$Elm.getElements('select').addEvent('change', this.update);

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onInject: function() {
            //var Parent = this.$Elm.getParent('.field-options');
            //
            //if (Parent) {
            //    Parent.setStyle('padding', 0);
            //}
            //
            //new Element('div', {
            //    'class': 'quiqqer-products-folder-settings',
            //    html   : '<label>' +
            //        '        <input type="checkbox" name="autoActivateItems"/>' +
            //        '           <span>' + QUILocale.get(lg, 'controls.UserInputSettings.autoActivateItems') + '</span>' +
            //        '    </label>'
            //}).inject(this.$Elm);
            //
            //this.$CheckboxAutoActivate = this.$Elm.getElement('[name="autoActivateItems"]');
            //this.$CheckboxAutoActivate.addEvent('change', this.update);
            //
            //this.$CheckboxAutoActivate.checked = !!this.getAttribute('autoActivateItems');
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function(self, Node) {
            this.$Input = Node;
            this.$Elm = this.create();

            var data = {};

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ('inputType' in data) {
                    this.setAttribute('inputType', data.inputType);
                }

                if ('maxCharacters' in data) {
                    this.setAttribute('maxCharacters', data.inputType);
                }
            } catch (e) {
                console.error(this.$Input.value);
                console.error(e);
            }

            if (!this.$data) {
                this.$data = [];
            }

            this.$Elm.wraps(this.$Input);

            QUIFormUtils.setDataToNode(data, this.$Elm);

            //this.$onInject();
        },

        /**
         * Set the data to the input
         */
        update: function() {
            this.$Input.value = JSON.encode(QUIFormUtils.getDataFromNode(this.$Elm));
        }
    });
});
