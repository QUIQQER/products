/**
 * @module package/quiqqer/products/bin/controls/fields/types/InputMultiLang
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 */
define('package/quiqqer/products/bin/controls/fields/types/InputMultiLang', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'css!package/quiqqer/products/bin/controls/fields/types/InputMultiLang.css'

], function (QUI, QUIControl, QUIAjax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/InputMultiLang',

        Binds: [
            'toggle',
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Container = null;
            this.$Button    = null;
            this.$Input     = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm(),
                path = URL_BIN_DIR + '16x16/flags/';

            this.$Button = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    textAlign: 'center',
                    width    : 50
                }
            }).inject(Elm, 'after');

            this.$Container = new Element('div', {
                'class': 'field-container-field'
            }).inject(Elm, 'after');

            this.$Input = new Element('input', {
                type: 'hidden',
                name: Elm.get('data-name')
            }).inject(Elm, 'after');


            Elm.destroy();

            QUIAjax.get('ajax_system_getAvailableLanguages', function (languages) {

                var i, len, lang, LangContainer, InputField;
                var current = QUILocale.getCurrent(),
                    data    = JSON.decode(Elm.value);

                if (!data) {
                    data = {};
                }

                // current language to the top
                languages.sort(function (a, b) {
                    if (a == current) {
                        return -1;
                    }

                    if (b == current) {
                        return 1;
                    }

                    return 0;
                });

                var onChange = function () {
                    self.refreshData();
                };

                for (i = 0, len = languages.length; i < len; i++) {
                    lang = languages[i];

                    LangContainer = new Element('div', {
                        'class': 'quiqqer-products-field-inputmultilang-entry',
                        html   : '<img src="' + path + lang + '.png" />' +
                                 '<input type="text" name="' + lang + '" />'
                    }).inject(self.$Container);

                    InputField = LangContainer.getElement('input');

                    if (i > 0) {
                        LangContainer.setStyles({
                            display: 'none',
                            opacity: 0
                        });
                    }

                    if (lang in data) {
                        InputField.value = data[lang];
                    }

                    InputField.addEvent('change', onChange);
                }

                self.$Button.set({
                    html  : '<span class="fa fa-arrow-circle-o-right"></span>',
                    styles: {
                        cursor: 'pointer'
                    }
                });

                self.$Button.addEvent('click', self.toggle);
            });
        },

        /**
         * Toggle the open status
         */
        toggle: function () {
            if (this.$Button.getElement('span').hasClass('fa-arrow-circle-o-right')) {
                this.open();
            } else {
                this.close();
            }
        },

        /**
         * shows all translation entries
         */
        open: function () {
            var self = this,
                list = this.$Container.getElements(
                    '.quiqqer-products-field-inputmultilang-entry'
                );

            var First = list.shift();

            list.setStyles({
                display: null,
                height : 0
            });

            moofx(First).animate({
                height: 40
            });

            moofx(list).animate({
                height : 40,
                opacity: 1
            }, {
                duration: 200,
                callback: function () {
                    self.$Button.getElement('span').addClass('fa-arrow-circle-o-down');
                    self.$Button.getElement('span').removeClass('fa-arrow-circle-o-right');
                }
            });
        },

        /**
         * shows all translation entries
         */
        close: function () {
            var self = this,
                list = this.$Container.getElements(
                    '.quiqqer-products-field-inputmultilang-entry'
                );

            var First = list.shift();

            First.setStyle('height', null);

            moofx(list).animate({
                height : 0,
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    self.$Button.getElement('span').removeClass('fa-arrow-circle-o-down');
                    self.$Button.getElement('span').addClass('fa-arrow-circle-o-right');
                }
            });
        },

        /**
         * Updates the data to the input field
         */
        refreshData: function () {
            var fields = this.$Container.getElements('input').map(function (Field) {
                var result         = {};
                result[Field.name] = Field.value;

                return result;
            });

            this.$Input.value = JSON.encode(fields);
        }
    });
});
