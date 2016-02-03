/**
 * Category sitemap
 *
 * @module package/quiqqer/products/bin/controls/categories/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Switch
 * @require Locale
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/classes/Categories
 * @require package/quiqqer/products/bin/classes/Fields
 * @require package/quiqqer/products/bin/controls/categories/Sitemap
 * @require package/quiqqer/translator/bin/controls/Update
 * @require text!package/quiqqer/products/bin/controls/categories/Update.html
 * @require css!package/quiqqer/products/bin/controls/categories/Update.css
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
    'controls/grid/Grid',
    'package/quiqqer/products/bin/classes/Categories',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'package/quiqqer/translator/bin/controls/Update',

    'text!package/quiqqer/products/bin/controls/categories/Update.html',
    'css!package/quiqqer/products/bin/controls/categories/Update.css'

], function (QUI, QUIControl, QUIButton, QUISwitch, QUILocale, Grid,
             Handler, FieldsHandler, CategorySitemap, Translation, template) {
    "use strict";

    var lg         = 'quiqqer/products';
    var Categories = new Handler();
    var Fields     = new FieldsHandler();

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

            this.$FieldTable = null;
            this.$SideTable  = null;

            this.$fields = {};
            this.$sites  = [];

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
                    opacity: 0,
                    padding: 20
                }
            });

            this.$Id         = Elm.getElement('.field-id');
            this.$Titles     = Elm.getElement('.category-title');
            this.$Categories = Elm.getElement('.category-description');
            this.$Buttons    = Elm.getElement('.category-update-buttons');


            var SiteContainer = new Element('div', {
                styles: {
                    width: '100%'
                }
            }).inject(
                Elm.getElement('.category-update-site-table')
            );

            this.$SideTable = new Grid(SiteContainer, {
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 100
                }, {
                    header   : QUILocale.get('quiqqer/system', 'project'),
                    dataIndex: 'project',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : QUILocale.get('quiqqer/system', 'language'),
                    dataIndex: 'lang',
                    dataType : 'QUI',
                    width    : 100
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'QUI',
                    width    : 300
                }]
            });


            var FieldContainer = new Element('div', {
                styles: {
                    width: '100%'
                }
            }).inject(
                Elm.getElement('.category-update-fields-table')
            );

            this.$FieldTable = new Grid(FieldContainer, {
                buttons    : [{
                    text     : 'Feld hinzufügen',
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: function () {
                            require([
                                'package/quiqqer/products/bin/controls/fields/Window'
                            ], function (Win) {
                                new Win({
                                    events: {
                                        onSubmit: function (Win, value) {
                                            self.addField(value);
                                        }
                                    }
                                }).open();
                            });
                        }
                    }
                }],
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'category.update.field.grid.publicStatus'),
                    dataIndex: 'publicStatus',
                    dataType : 'QUI',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'category.update.field.grid.searchStatus'),
                    dataIndex: 'searchStatus',
                    dataType : 'QUI',
                    width    : 100
                }]
            });

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

                var i, len, field;

                var fieldGridData = [];

                for (i = 0, len = data.fields.length; i < len; i++) {
                    field = data.fields[i];

                    fieldGridData.push({
                        id          : field.id,
                        title       : QUILocale.get(lg, 'products.field.' + field.id + '.title'),
                        publicStatus: new QUISwitch({
                            status: field.publicStatus
                        }),
                        searchStatus: new QUISwitch({
                            status: field.searchStatus
                        })
                    });
                }

                self.$FieldTable.setData({
                    data: fieldGridData
                });


                // resize
                var size = self.getElm().getSize();

                Promise.all([
                    self.$SideTable.setWidth(size.x - 60),
                    self.$SideTable.setHeight(200),
                    self.$FieldTable.setWidth(size.x - 60),
                    self.$FieldTable.setHeight(300)
                ]).then(function () {
                    self.$SideTable.resize();
                    self.$FieldTable.resize();

                    moofx(self.getElm()).animate({
                        opacity: 1
                    }, {
                        callback: function () {
                            self.fireEvent('loaded');
                        }
                    });
                });
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

                    var data = self.$FieldTable.getData();

                    // fields
                    var i, len, field;
                    var fields = [];

                    for (i = 0, len = data.length; i < len; i++) {
                        field = data[i];

                        fields.push({
                            id          : field.id,
                            publicStatus: field.publicStatus.getStatus(),
                            searchStatus: field.searchStatus.getStatus()
                        });
                    }

                    Categories.updateChild(categoryId, {
                        fields: fields
                    }).then(resolve, reject);

                }, reject);
            });
        },

        /**
         * Add a field to the category
         *
         * @param {Number} fieldId - Field-ID
         * @return {Promise}
         */
        addField: function (fieldId) {
            var self = this;

            return new Promise(function (resolve) {
                Fields.getChild(fieldId).then(function (data) {
                    self.$FieldTable.addRow({
                        id          : fieldId,
                        title       : QUILocale.get(lg, 'products.field.' + fieldId + '.title'),
                        publicStatus: new QUISwitch(),
                        searchStatus: new QUISwitch()
                    });

                    //console.log(data);

                    resolve();
                });
            });
        }
    });
});
