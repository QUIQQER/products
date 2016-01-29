/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/classes/Categories
 *
 * @require css!package/quiqqer/products/bin/controls/categories/Create.css
 *
 * @event onCancel
 * @event onSuccess
 * @event onSubmit
 * @event onLoaded
 */
define('package/quiqqer/products/bin/controls/categories/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'Locale',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/categories/Update.html',
    'css!package/quiqqer/products/bin/controls/categories/Update.css'

], function (QUI, QUIControl, QUIButton, QUISwitch, QUILocale,
             Handler, CategorySitemap, Translation, template) {
    "use strict";

    var Categories = new Handler();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/Update',

        Binds: [
            '$onInject'
        ],

        options: {
            categoryId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Titles     = null;
            this.$Categories = null;
            this.$Buttons    = null;
            this.$Id         = null;

            this.$TitlesTranslation     = null;
            this.$CategoriesTranslation = null;

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
            var self = this,
                Elm  = this.parent();

            Elm.set({
                'class': 'category-update',
                html   : template,
                styles : {
                    padding: 20
                }
            });

            this.$Id         = Elm.getElement('.field-id');
            this.$Titles     = Elm.getElement('.category-title');
            this.$Categories = Elm.getElement('.category-description');
            this.$Buttons    = Elm.getElement('.category-update-buttons');


            new QUIButton({
                name  : 'cancel',
                text  : QUILocale.get('quiqqer/system', 'close'),
                events: {
                    onClick: function () {
                        self.cancel();
                    }
                },
                styles: {
                    'float'    : 'none',
                    marginRight: 20
                }
            }).inject(this.$Buttons);

            new QUIButton({
                name  : 'save',
                text  : QUILocale.get('quiqqer/system', 'save'),
                events: {
                    onClick: function () {

                    }
                },
                styles: {
                    'float': 'none'
                }
            }).inject(this.$Buttons);


            return Elm;
        },

        /**
         * Event : on inject
         */
        $onInject: function () {
            var self       = this,
                categoryId = this.getAttribute('categoryId');

            Categories.getChild(categoryId).then(function (data) {

                self.$TitlesTranslation = new Translation({
                    'group': 'quiqqer/products',
                    'var'  : 'products.category.' + categoryId + '.title'
                }).inject(self.$Titles);

                self.$CategoriesTranslation = new Translation({
                    'group': 'quiqqer/products',
                    'var'  : 'products.category.' + categoryId + '.description'
                }).inject(self.$Categories);

                self.$Id.set('html', '#' + data.id);

                // fields
                var publish = self.getElm().getElements(
                    '.category-update-field-publish'
                );

                for (var i = 0, len = publish.length; i < len; i++) {
                    new QUISwitch().inject(publish[i]);
                }

                console.log(data);

                self.fireEvent('loaded');
            });
        },

        /**
         * Save the category
         *
         * @returns {Object}
         */
        save: function () {
            var self       = this,
                categoryId = this.getAttribute('categoryId');

            return new Promise(function (resolve, reject) {

                Promise.all([
                    self.$TitlesTranslation.save(),
                    self.$CategoriesTranslation.save()
                ]).then(function () {
                    Categories.updateChild(categoryId).then(resolve, reject);

                }, reject);

            });
        },

        /**
         * Cancel
         */
        cancel: function () {
            this.fireEvent('cancel', [this]);
        }
    });
});
