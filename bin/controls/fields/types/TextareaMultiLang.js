/**
 * @module package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang', [

    'QUIQQER',
    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'Editors',
    'Locale'
    
], function (QUIQQER, QUI, QUIControl, QUISelect, Editors, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/TextareaMultiLang',

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
                        this.$Editor.destroy();
                    }
                }.bind(this)
            });
        },

        /**
         * event : on import
         */
        $onInject: function () {
            const self    = this,
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

            const EditorContainer = Elm.getElement('.editor');
            const LangContainer = Elm.getElement('.language-select');

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

            QUIQQER.getAvailableLanguages().then(function (languages) {
                for (let i = 0, len = languages.length; i < len; i++) {
                    this.$Select.appendChild(
                        QUILocale.get('quiqqer/core', 'language.' + languages[i]),
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

                    let value = this.getAttribute('value');

                    if (typeOf(value) === 'string') {
                        try {
                            value = JSON.decode(value);
                            this.setAttribute('value', value);
                        } catch (e) {
                            value = {};
                            this.setAttribute('value', value);
                        }
                    }

                    if (value && current in value) {
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

            let value = this.getAttribute('value');
            let current = this.getAttribute('current');

            if (!value) {
                value = {};
            }

            value[current] = this.$Editor.getContent();

            this.setAttribute('value', value);
            this.setAttribute('current', lang);

            if (lang in value) {
                this.$Editor.setContent(value[lang]);
            }
        },

        /**
         * Return current value
         *
         * @returns {String}
         */
        getValue: function () {
            return JSON.encode(this.getAttribute('value'));
        },

        /**
         * Save the data
         *
         * @return {String}
         */
        save: function () {
            let value = this.getAttribute('value');
            let current = this.getAttribute('current');

            if (typeOf(value) !== 'object') {
                value = {};
            }

            value[current] = this.$Editor.getContent();

            this.setAttribute('value', value);

            return JSON.encode(value);
        }
    });
});
