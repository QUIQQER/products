/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require Locale
 * @require package/quiqqer/products/bin/classes/Categories
 * @require package/quiqqer/products/bin/controls/categories/Sitemap
 * @require package/quiqqer/translator/bin/controls/Create
 * @require css!package/quiqqer/products/bin/controls/categories/Create.css
 *
 * @event onCancel
 * @event onSuccess
 * @event onSubmit
 */
define('package/quiqqer/products/bin/controls/categories/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Locale',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/translator/bin/controls/Create',

    'css!package/quiqqer/products/bin/controls/categories/Create.css'

], function (QUI, QUIControl, QUIButton, QUILocale,
             Handler, CategorySitemap, TranslationCreate) {
    "use strict";

    var Categories = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/Create',

        Binds: [
            'showParentSelect',
            'showTextEdit',
            'submit',
            'cancel'
        ],

        options: {
            parentId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Buttons      = null;
            this.$ParentSelect = null;
            this.$Text         = null;

            this.$id = false;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            var Elm = this.parent();

            Elm.set({
                'class': 'category-create',
                html   : '<div class="category-create-container">' +
                '<div class="category-create-sheet category-create-parentSelect"></div>' +
                '<div class="category-create-sheet category-create-text"></div>' +
                '</div>' +
                '<div class="category-create-buttons"></div>'
            });

            this.$ParentSelect = Elm.getElement('.category-create-parentSelect');
            this.$Text         = Elm.getElement('.category-create-text');
            this.$Buttons      = Elm.getElement('.category-create-buttons');

            this.$Text.setStyles({
                display: 'none',
                opacity: 0,
                top    : -10
            });

            this.$ParentSelect.setStyles({
                display: 'none',
                opacity: 0,
                top    : -10
            });

            this.$TitleTranslate = new TranslationCreate({
                'group'  : 'quiqqer/products',
                'package': 'quiqqer/products',
                editable : true
            });

            this.$DescTranslate = new TranslationCreate({
                'group'  : 'quiqqer/products',
                'package': 'quiqqer/products',
                editable : true
            });

            return Elm;
        },

        /**
         * Event : on inject
         */
        $onInject: function () {
            this.showParentSelect();
        },

        /**
         * Show parent select
         *
         * @return {Promise}
         */
        showParentSelect: function () {
            var self = this;

            this.$Buttons.set('html', '');

            new QUIButton({
                text     : 'Abbrechen', // #locale
                textimage: 'fa fa-remove',
                events   : {
                    onClick: this.cancel
                },
                styles   : {
                    'float': 'left'
                }
            }).inject(this.$Buttons);

            new QUIButton({
                text     : 'Weiter', // #locale
                textimage: 'fa fa-angle-right',
                events   : {
                    onClick: this.showTextEdit
                },
                styles   : {
                    'float': 'right'
                }
            }).inject(this.$Buttons);

            return new Promise(function (reslolve) {

                self.$ParentSelect.set({
                    html  : '<div class="category-create-parentSelect-description">' +
                    'Wählen Sie bitte die Kategorie aus unter welche ' +
                    'die neue Kategorie neu angelegt werden soll' +
                    '</div>', // #locale
                    styles: {
                        display: null
                    }
                });

                moofx(self.$Text).animate({
                    opacity: 0,
                    top    : -10
                }, {
                    callback: function () {

                        self.$Text.setStyle('display', 'none');
                        self.$ParentSelect.setStyle('display', null);

                        moofx(self.$ParentSelect).animate({
                            opacity: 1,
                            top    : 0
                        }, {
                            duration: 200,
                            callback: function () {
                                new CategorySitemap({
                                    selectedId: self.getAttribute('parentId'),
                                    events    : {
                                        onClick: function (Map, value) {
                                            self.setAttribute('parentId', value);
                                        }
                                    }
                                }).inject(self.$ParentSelect);

                                reslolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * Show text edit
         *
         * @return {Promise}
         */
        showTextEdit: function () {

            var self = this;

            this.$Buttons.set('html', '');

            new QUIButton({
                text     : 'Zurück',
                textimage: 'fa fa-angle-left',
                events   : {
                    onClick: this.showParentSelect
                },
                styles   : {
                    'float': 'left'
                }
            }).inject(this.$Buttons);

            new QUIButton({
                text     : 'Kategorie anlegen',
                textimage: 'fa fa-angle-right',
                events   : {
                    onClick: this.submit
                },
                styles   : {
                    'float': 'right'
                }
            }).inject(this.$Buttons);


            return new Promise(function (reslolve, reject) {

                if (self.getAttribute('parentId') === false) {
                    return reject();
                }

                self.$Text.set({
                    html  : '<div class="category-create-text-description">' +
                    'Geben Sie bitte einen Titel und Kurzbeschreibung für die Kategorie an' +
                    '</div>',
                    styles: {
                        display: null
                    }
                });


                var Text = new Element('label', {
                    'class': 'field-container category-create-text-title',
                    html   : '<span class="field-container-item">Kategorien-Titel</span>' +
                    '<div class="field-container-field"></div>'
                }).inject(self.$Text);

                var Desc = new Element('div', {
                    'class': 'field-container category-create-text-title',
                    html   : '<span class="field-container-item">Kategorien-Beschreibung</span>' +
                    '<div class="field-container-field"></div>'
                }).inject(self.$Text);


                self.$TitleTranslate.inject(Text.getElement('.field-container-field'));
                self.$DescTranslate.inject(Desc.getElement('.field-container-field'));


                moofx(self.$ParentSelect).animate({
                    opacity: 0,
                    top    : -10
                }, {
                    duration: 200,
                    callback: function () {

                        self.$ParentSelect.setStyle('display', 'none');
                        self.$Text.setStyle('display', null);

                        moofx(self.$Text).animate({
                            opacity: 1,
                            top    : 0
                        }, {
                            duration: 200,
                            callback: function () {
                                reslolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * cancel the creation
         */
        cancel: function () {
            this.fireEvent('cancel');
        },

        /**
         * submit the data and create a new category
         *
         * @returns {Object}
         */
        submit: function () {
            var self = this;

            self.fireEvent('submit');

            return new Promise(function (resolve, reject) {

                var parentId = self.getAttribute('parentId');

                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('showMessages', false);

                }).then(function () {
                    // category creation
                    return Categories.createChild(parentId);

                }).then(function (childData) {
                    // title / desc translation
                    self.$TitleTranslate.setAttribute(
                        'var',
                        'products.category.' + childData.id + '.title'
                    );

                    self.$DescTranslate.setAttribute(
                        'var',
                        'products.category.' + childData.id + '.description'
                    );

                    self.$TitleTranslate.setAttribute('package', 'quiqqer/products');
                    self.$DescTranslate.setAttribute('package', 'quiqqer/products');

                    self.$TitleTranslate.createTranslation().then(function () {
                        return self.$DescTranslate.createTranslation();

                    }).then(function () {
                        return QUI.getMessageHandler();

                    }).then(function (MH) {
                        MH.setAttribute('showMessages', true);

                        MH.addSuccess(
                            QUILocale.get('quiqqer/products', 'message.category.successfully.created')
                        );

                        self.fireEvent('success', [self, childData]);
                        resolve(childData);
                    });

                }).catch(function () {
                    QUI.getMessageHandler().then(function (MH) {
                        MH.setAttribute('showMessages', true);
                        reject();
                    });
                });

            });
        }
    });
});
