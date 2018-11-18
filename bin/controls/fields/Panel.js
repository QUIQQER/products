/**
 * Field management
 *
 * @module package/quiqqer/products/bin/controls/fields/Panel
 * @author www.pcsg.de (Henning Leutz)
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
    'package/quiqqer/products/bin/controls/fields/search/Search',

    'css!package/quiqqer/products/bin/controls/fields/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale,
             Handler, CreateField, UpdateField, FieldSearch) {
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
            this.parent(options);

            this.setAttributes({
                title: QUILocale.get(lg, 'products.fields.panel.title'),
                icon : 'fa fa-file-text-o'
            });

            this.$Search = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize,
                onShow  : this.$onResize
            });
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        $onResize: function () {
            this.$Search.resize();
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            this.Loader.show();
            return this.$Search.refresh().then(function () {
                this.Loader.hide();
            }.bind(this));
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            var self = this;

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
                            self.$Search.getSelected()[0]
                        );
                    }
                }
            });

            this.addButton({
                type: 'separator'
            });

            this.addButton({
                name     : 'delete',
                text     : QUILocale.get('quiqqer/system', 'delete'),
                textimage: 'fa fa-trash',
                disabled : true,
                events   : {
                    onClick: function () {
                        self.deleteChild(self.$Search.getSelected());
                    }
                }
            });


            // field search
            this.$Search = new FieldSearch({
                multiple: true,
                events  : {
                    onClick: function () {
                        var Delete = self.getButtons('delete'),
                            Edit   = self.getButtons('edit');

                        Delete.enable();
                        Edit.enable();
                    },
                    submit : function () {
                        self.updateChild(self.$Search.getSelected()[0]);
                    }
                }
            }).inject(this.getContent());
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

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
         * @param {Number|Array} fieldId
         */
        deleteChild: function (fieldId) {
            var self = this;

            if (typeOf(fieldId) !== 'array') {
                fieldId = [fieldId];
            }

            new QUIConfirm({
                title      : QUILocale.get(lg, 'fields.window.delete.title'),
                text       : QUILocale.get(lg, 'fields.window.delete.text'),
                information: QUILocale.get(lg, 'fields.window.delete.description', {
                    fields: fieldId.join(',')
                }),
                autoclose  : false,
                maxHeight  : 400,
                maxWidth   : 600,
                icon       : 'fa fa-trashcan',
                texticon   : 'fa fa-trashcan',
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        Fields.deleteChildren(fieldId).then(function () {
                            Win.close();
                            self.refresh();
                        }).catch(function () {
                            Win.Loader.hide();
                        });
                    }
                }
            }).open();
        }
    });
});
