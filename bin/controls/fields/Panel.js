/**
 * Field management
 *
 * @module package/quiqqer/products/bin/controls/fields/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require controls/grid/Grid
 * @require Locale
 * @require package/quiqqer/products/bin/classes/Fields
 * @require package/quiqqer/products/bin/controls/fields/Create
 * @require css!package/quiqqer/products/bin/controls/fields/Panel.css
 */
define('package/quiqqer/products/bin/controls/fields/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'package/quiqqer/products/bin/classes/Fields',
    'package/quiqqer/products/bin/controls/fields/Create',
    'package/quiqqer/products/bin/controls/fields/Update',

    'css!package/quiqqer/products/bin/controls/fields/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale,
             Handler, CreateField, UpdateField) {
    "use strict";

    var lg     = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/fields/Panel',

        Binds: [
            'refresh',
            'createChild',
            'deleteChild',
            'updateChild',
            '$onCreate',
            '$onInject',
            '$onResize'
        ],

        initialize: function (options) {

            this.setAttributes({
                title: QUILocale.get(lg, 'products.fields.panel.title'),
                icon : 'fa-file-text-o'
            });

            this.parent(options);

            this.$Grid          = null;
            this.$GridContainer = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        $onResize: function () {
            var size = this.$GridContainer.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            this.Loader.show();
            this.parent();

            return Fields.getList({
                perPage: this.$Grid.options.perPage,
                page   : this.$Grid.options.page
            }).then(function (data) {
                self.$Grid.setData(data);

                var Delete = self.getButtons('delete'),
                    Edit   = self.getButtons('edit');

                Delete.disable();
                Edit.disable();

                self.Loader.hide();
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {

            var self    = this,
                Content = this.getContent();

            // buttons
            this.addButton({
                name     : 'add',
                text     : QUILocale.get('quiqqer/system', 'add'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.createChild
                }
            });

            this.addButton({
                name     : 'edit',
                text     : QUILocale.get('quiqqer/system', 'edit'),
                textimage: 'fa fa-edit',
                disabled : true,
                events   : {
                    onClick: function () {
                        self.updateChild(
                            self.$Grid.getSelectedData()[0].id
                        );
                    }
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton({
                name     : 'delete',
                text     : QUILocale.get('quiqqer/system', 'delete'),
                textimage: 'fa fa-trash',
                disabled : true,
                events   : {
                    onClick: function () {
                        self.deleteChild(
                            self.$Grid.getSelectedData()[0].id
                        );
                    }
                }
            });


            // grid
            this.$GridContainer = new Element('div', {
                'class': 'products-fields-panel-container'
            }).inject(Content);

            var GridContainer = new Element('div', {
                'class': 'products-fields-panel-grid'
            }).inject(this.$GridContainer);

            this.$Grid = new Grid(GridContainer, {
                pagination : true,
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
                    header   : 'Feld-Typ',
                    dataIndex: 'type',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : 'Priorität',
                    dataIndex: 'priority',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Präfix',
                    dataIndex: 'prefix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Suffix',
                    dataIndex: 'suffix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : 'Such-Typ',
                    dataIndex: 'searchtype',
                    dataType : 'text',
                    width    : 200
                }]
            });

            this.$Grid.addEvents({
                onRefresh : this.refresh,
                onClick   : function () {
                    var Delete = self.getButtons('delete'),
                        Edit   = self.getButtons('edit');

                    Delete.enable();
                    Edit.enable();

                },
                onDblClick: function () {
                    self.updateChild(
                        self.$Grid.getSelectedData()[0].id
                    );
                }
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.$onResize();

            this.refresh().then(function () {
                self.Loader.hide();
            });
        },

        /**
         * Opens the create child dialog
         */
        createChild: function () {

            var self = this;

            this.Loader.show();

            this.createSheet({
                title : QUILocale.get(lg, 'fields.create.title'),
                events: {
                    onShow : function (Sheet) {

                        Sheet.getContent().setStyle('padding', 20);

                        var Field = new CreateField({
                            events: {
                                onLoaded: function () {
                                    self.Loader.hide();
                                }
                            }
                        }).inject(Sheet.getContent());

                        Sheet.addButton(
                            new QUIButton({
                                text     : QUILocale.get('quiqqer/system', 'save'),
                                textimage: 'fa fa-save',
                                events   : {
                                    onClick: function () {
                                        self.Loader.show();

                                        Field.submit().then(function () {
                                            Sheet.hide().then(function () {
                                                Sheet.destroy();
                                                self.refresh();
                                            });
                                        });
                                    }
                                }
                            })
                        );
                    },
                    onClose: function (Sheet) {
                        Sheet.destroy();
                    }
                }
            }).show();
        },

        /**
         * Opens the delete child dialog
         *
         * @param {Number} fieldId
         */
        updateChild: function (fieldId) {

            var self = this;

            this.Loader.show();

            this.createSheet({
                title : QUILocale.get(lg, 'fields.update.title', {
                    fieldId: fieldId
                }),
                events: {
                    onShow: function (Sheet) {

                        Sheet.getContent().setStyle('padding', 20);

                        var Field = new UpdateField({
                            fieldId: fieldId,
                            events : {
                                onLoaded: function () {
                                    self.Loader.hide();
                                }
                            }
                        }).inject(Sheet.getContent());

                        Sheet.addButton(
                            new QUIButton({
                                text     : QUILocale.get('quiqqer/system', 'save'),
                                textimage: 'fa fa-save',
                                events   : {
                                    onClick: function () {
                                        self.Loader.show();

                                        Field.submit().then(function () {
                                            Sheet.hide().then(function () {
                                                Sheet.destroy();
                                                self.refresh();
                                            });
                                        });
                                    }
                                }
                            })
                        );
                    }
                }
            }).show();
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number} fieldId
         */
        deleteChild: function (fieldId) {

        }
    });
});
