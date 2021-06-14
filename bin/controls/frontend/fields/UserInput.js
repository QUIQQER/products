/**
 * Frontend control for fields of type "UserInput"
 *
 * @module package/quiqqer/products/bin/controls/frontend/fields/UserInput
 * @author www.pcsg.de (Patrick Müller)
 *
 * @event onChange [{Object} self, {Number} fieldId]
 */
define('package/quiqqer/products/bin/controls/frontend/fields/UserInput', [

    'package/quiqqer/products/bin/controls/frontend/fields/Field',
    'qui/controls/windows/Confirm',

    'Locale',
    'Ajax',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/frontend/fields/UserInput.Input.html',
    'css!package/quiqqer/products/bin/controls/frontend/fields/UserInput.css'

], function (ProductFieldControl, QUIConfirm, QUILocale, QUIAjax, Mustache, templateInput) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({
        Extends: ProductFieldControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/UserInput',

        Binds: [
            '$onImport',
            '$openEditWindow'
        ],

        options: {
            field_title: '',
            input_type : 'input',
            max_length : 100
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input          = null;
            this.$Overlay        = null;
            this.$editWindowOpen = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const Elm = this.getElm();

            this.$fieldId = Elm.get('data-field-id').toInt();

            this.$Input   = Elm.getElement('input');
            this.$Overlay = Elm.getElement('.quiqqer-products-field-userinput-overlay-text');

            this.$Overlay.addEvent('click', () => {
                this.$Input.focus();
            });

            this.$Input.addEvent('focus', () => {
                this.$Input.blur();
                this.$openEditWindow();
            });
        },

        /**
         * Open text prompt for field
         */
        $openEditWindow: function () {
            if (this.$editWindowOpen) {
                return;
            }

            this.$editWindowOpen = true;

            let CancelBtn = false;

            if (!this.$Input.required) {
                CancelBtn = {
                    text     : QUILocale.get(lg, 'controls.UserInput.getText.btn.cancel'),
                    textimage: 'icon-remove fa fa-remove'
                };
            }

            const isInput = this.getAttribute('input_type') === 'input';

            new QUIConfirm({
                maxHeight: isInput ? 225 : 500,
                maxWidth : 800,

                autoclose         : false,
                backgroundClosable: false,
                titleCloseButton  : !this.$Input.required,

                title: QUILocale.get(lg, 'controls.UserInput.getText.title', {
                    fieldTitle: this.getAttribute('field_title')
                }),
                icon : 'fa fa-edit',

                cancel_button: CancelBtn,
                ok_button    : {
                    text     : QUILocale.get(lg, 'controls.UserInput.getText.btn.submit'),
                    textimage: 'icon-ok fa fa-check'
                },
                events       : {
                    onOpen  : (Win) => {
                        Win.setContent(Mustache.render(templateInput, {
                            infoText  : QUILocale.get(lg, 'controls.UserInput.getText.tpl.infoText', {
                                fieldTitle: this.getAttribute('field_title')
                            }),
                            labelInput: QUILocale.get(lg, 'controls.UserInput.getText.tpl.labelInput', {
                                fieldTitle   : this.getAttribute('field_title'),
                                maxCharacters: this.getAttribute('max_length')
                            }),
                            isInput   : this.getAttribute('input_type') === 'input',
                            maxLength : this.getAttribute('max_length')
                        }));

                        const TextInput = Win.getContent().getElement('[name="userInput"]');

                        if (this.$Input.value !== '') {
                            TextInput.value = JSON.decode(this.$Input.value);
                        }

                        TextInput.focus();
                    },
                    onSubmit: (Win) => {
                        const inputValue = Win.getContent().getElement('[name="userInput"]').value.trim();
                        let overlayText  = '<span class="fa fa-edit"></span>' +
                            QUILocale.get(lg, 'UserInputFrontendView.input_placeholder');

                        if (inputValue === '') {
                            this.$Input.value = '';
                        } else {
                            this.$Input.value = JSON.encode(inputValue);
                            overlayText       = inputValue.replace("\n", " ");
                        }

                        this.$Overlay.innerHTML = overlayText;

                        Win.close();
                    },
                    onClose : () => {
                        this.$editWindowOpen = false;
                    }
                }
            }).open();
        },

        /**
         * Get field value
         *
         * @return {String}
         */
        getValue: function () {
            return this.$Input.value;
        }
    });
});
