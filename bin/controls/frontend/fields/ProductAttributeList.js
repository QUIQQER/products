/**
 * @module package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 *
 * @event onChange [{Object} self, {Number} fieldId]
 */
define('package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/frontend/fields/Field'

], function (QUI, FieldControl) {
    "use strict";

    return new Class({
        Extends: FieldControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/ProductAttributeList',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$UserInput = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm();

            this.$fieldId = Elm.get('data-field').toInt();

            Elm.addEvent('change', function () {
                var value  = Elm.value,
                    Option = Elm.getElement('option[value="' + value + '"]');

                if (self.$UserInput) {
                    self.$UserInput.destroy();
                }

                self.$hideUserInput().then(function () {
                    if (Option.get('data-userinput') && Option.get('data-userinput').toInt()) {
                        return self.$showUserInput();
                    }

                    return Promise.resolve();
                }).then(function () {
                    self.fireEvent('change', [self]);
                    self.getElm().focus();
                });
            });

            Elm.disabled = false;
        },

        /**
         * Return the field value
         *
         * @returns {*|string}
         */
        getValue: function () {
            return this.getElm().value;
        },

        /**
         * Hide user input
         *
         * @returns {Promise}
         */
        $hideUserInput: function () {
            this.getElm().disabled = true;

            return new Promise(function (resolve) {
                if (!this.$UserInput) {
                    this.getElm().disabled = false;
                    return resolve();
                }

                moofx(this.$UserInput).animate({
                    height : 0,
                    opacity: 0
                }, {
                    duration: 250,
                    callback: function () {
                        this.getElm().disabled = false;
                        this.$UserInput.destroy();
                        this.$UserInput = null;
                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        },

        /**
         * Show user input
         *
         * @returns {Promise}
         */
        $showUserInput: function () {
            this.getElm().disabled = true;

            return new Promise(function (resolve) {
                if (!this.$UserInput) {
                    this.$UserInput = new Element('input', {
                        type   : 'text',
                        'class': 'field-userinput',
                        styles : {
                            display : 'none',
                            opacity : 0,
                            position: 'relative',
                            width   : '100%'
                        }
                    }).inject(this.getElm(), 'after');
                }

                var height = this.$UserInput.measure(function () {
                    return this.getSize().y;
                });

                this.$UserInput.setStyles({
                    display: null,
                    height : 0
                });

                moofx(this.$UserInput).animate({
                    height : height,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: function () {
                        this.getElm().disabled = false;
                        resolve();
                    }.bind(this)
                });
            }.bind(this));
        }
    });
});
