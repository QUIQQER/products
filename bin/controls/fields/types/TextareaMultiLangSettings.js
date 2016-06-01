/**
 * @module package/quiqqer/products/bin/controls/fields/types/TextareaMultiLangSettings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Editors
 */
define('package/quiqqer/products/bin/controls/fields/types/TextareaMultiLangSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'Editors',
    'Locale',
    'package/quiqqer/translator/bin/classes/Translator'

], function (QUI, QUIControl, QUISelect, Editors, QUILocale, TranslatorCls) {
    "use strict";

    var Translator = new TranslatorCls();

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLangSettings',

        Binds: [
            '$onInject'
        ],

        options: {
            value  : {},
            current: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Select = null;
            this.$Editor = null;

            this.addEvents({
                onInject : this.$onInject,
                onDestroy: function () {
                    if (this.$Editor) {
                        this.$Editor.destroy()
                    }
                }.bind(this)
            });
        },

        /**
         * event : on import
         */
        $onInject: function () {
            var self    = this,
                Elm     = this.getElm(),
                current = QUILocale.getCurrent();

            Elm.setStyles({
                'float': 'left',
                height : '100%',
                width  : '100%'
            });

            Elm.set({
                html: '<div class="language-select"></div>' +
                      '<div class="editor"></div>'
            });

            var EditorContainer = Elm.getElement('.editor');
            var LangContainer   = Elm.getElement('.language-select');

            EditorContainer.setStyles({
                height: 'calc(100% - 50px)'
            });

            LangContainer.setStyles({
                padding: '0 0 10px 0',
                display: 'inline-block',
                width  : '100%'
            });

            this.$Select = new QUISelect({
                styles: {
                    margin: 0,
                    width : '100%'
                }
            }).inject(LangContainer);

            Translator.getAvailableLanguages().then(function (languages) {
                for (var i = 0, len = languages.length; i < len; i++) {
                    this.$Select.appendChild(
                        QUILocale.get('quiqqer/system', 'language.' + languages[i]),
                        languages[i],
                        URL_BIN_DIR + '16x16/flags/' + languages[i] + '.png'
                    );
                }

                this.$Select.setValue(current);
                this.setAttribute('current', current);

                this.$Select.addEvents({
                    onChange: function (value) {
                        self.$changeLanguage(value);
                    }
                });

                Editors.getEditor().then(function (Editor) {
                    this.$Editor = Editor;

                    var value = this.getAttribute('value');

                    if (typeOf(value) === 'string') {
                        try {
                            value = JSON.decode(value);
                            this.setAttribute('value', value);
                        } catch (e) {
                            value = {};
                            this.setAttribute('value', value);
                        }
                    }

                    if (current in value) {
                        Editor.setContent(value[current]);
                    }

                    Editor.inject(EditorContainer);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Change language
         *
         * @param {String} lang
         */
        $changeLanguage: function (lang) {
            if (!this.$Editor) {
                return;
            }

            var value   = this.getAttribute('value');
            var current = this.getAttribute('current');

            value[current] = this.$Editor.getContent();

            this.setAttribute('value', value);
            this.setAttribute('current', lang);

            if (lang in value) {
                this.$Editor.setContent(value[lang]);
            }
        },

        getValue: function () {
            return this.getAttribute('value');
        },

        /**
         * Save the data
         *
         * @return {String}
         */
        save: function () {
            var value   = this.getAttribute('value');
            var current = this.getAttribute('current');

            value[current] = this.$Editor.getContent();

            this.setAttribute('value', value);

            return JSON.encode(this.getValue());
        }
    });
});
