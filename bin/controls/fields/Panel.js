/**
 * Field management
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
    'package/quiqqer/products/bin/controls/fields/FieldTypeSelect',

    'css!package/quiqqer/products/bin/controls/fields/Panel.css'

], function (QUI, QUIPanel, QUIButton, QUIConfirm, Grid, QUILocale,
             Handler, CreateField, UpdateField, FieldSearch, FieldTypeSelect) {
    "use strict";

    const lg = 'quiqqer/products',
        Fields = new Handler();

    return new Class({

        Extends: QUIPanel,
        Type: 'package/quiqqer/products/bin/controls/fields/Panel',

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
                icon: 'fa fa-file-text-o'
            });

            this.$Search = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize,
                onShow: this.$onResize
            });
        },

        /**
         * Resize the panel
         *
         * @return {Promise}
         */
        $onResize: function () {
            return this.$Search?.resize();
        },

        /**
         * Refresh the panel
         *
         * @return {Promise}
         */
        refresh: function () {
            this.Loader.show();
            return this.$Search.refresh().finally(() => {
                this.Loader.hide();
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            this.getElm().classList.add('quiqqer-products-fields-panel');
            const deleteLabel = QUILocale.get(lg, 'fields.panel.button.delete');
            const Create = new QUIButton({
                name: 'add',
                text: QUILocale.get(lg, 'fields.panel.button.create'),
                textimage: 'fa fa-plus',
                events: {
                    onClick: this.createChild
                }
            });

            const Edit = new QUIButton({
                name: 'edit',
                text: QUILocale.get(lg, 'fields.panel.button.edit'),
                textimage: 'fa fa-edit',
                disabled: true,
                events: {
                    onClick: () => {
                        const selected = this.$Search.getSelected();

                        if (selected.length === 1) {
                            this.updateChild(selected[0]);
                        }
                    }
                }
            });

            const Delete = new QUIButton({
                name: 'delete',
                'class': 'quiqqer-products-fields-delete',
                text: deleteLabel,
                title: deleteLabel,
                icon: 'fa fa-trash',
                disabled: true,
                position: 'right',
                events: {
                    onClick: () => {
                        const selected = this.$Search.getSelected();

                        if (selected.length) {
                            this.deleteChild(selected);
                        }
                    }
                }
            });
            // field search
            this.$Search = new FieldSearch({
                multiple: true,
                buttons: [Create, Edit, Delete],
                showFieldTypeFilter: false,
                events: {
                    onRefreshBegin: () => {
                        this.$setSelectionState(0);
                        this.Loader.show();
                    },

                    onRefresh: () => {
                        this.$setSelectionState(0);
                        this.Loader.hide();
                    },

                    onRefreshError: () => this.Loader.hide(),
                    onClick: () => this.$setSelectionState(this.$Search.getSelected().length),
                    onSubmit: () => {
                        const selected = this.$Search.getSelected();

                        if (selected.length === 1) {
                            this.updateChild(selected[0]);
                        }
                    }
                }
            }).inject(this.getContent());

            for (const Button of [Create, Edit, Delete]) {
                const Elm = Button.getElm();
                Elm.style.removeProperty('outline');
                Elm.firstElementChild?.setAttribute('aria-hidden', 'true');
            }
            Delete.getElm().setAttribute('aria-label', deleteLabel);

            const label = QUILocale.get(lg, 'quiqqer.products.control.search.title');
            this.$Filter = new FieldTypeSelect({
                name: 'filter',
                'class': 'quiqqer-products-fields-filter',
                text: label,
                filterLabel: label,
                menuCorner: 'topRight',
                events: {
                    onFilterChange: (Filter, value) => {
                        this.$Search.setAttribute('fieldTypeFilter', value);
                        this.$Search.getGrid().options.page = 1;
                        this.refresh();
                    }
                }
            });
            this.addButton(this.$Filter);
            const FilterElm = this.$Filter.getElm();
            FilterElm.tabIndex = 0;
            FilterElm.style.removeProperty('outline');
            FilterElm.firstElementChild?.setAttribute('aria-hidden', 'true');
            FilterElm.setAttribute('aria-haspopup', 'menu');
            FilterElm.setAttribute('aria-expanded', 'false');
            const Arrow = document.createElement('span');
            Arrow.className = 'qui-button-drop fa fa-chevron-down';
            Arrow.setAttribute('aria-hidden', 'true');
            FilterElm.appendChild(Arrow);
            this.$Filter.getContextMenu(Menu => {
                Menu.addEvents({
                    onShow: () => {
                        const button = FilterElm.getBoundingClientRect();
                        const menu = Menu.getElm().getBoundingClientRect();
                        Menu.setPosition(
                            Math.max(8, Math.min(button.right - menu.width, window.innerWidth - menu.width - 8)) + window.scrollX,
                            button.bottom + window.scrollY + 8
                        );
                        FilterElm.setAttribute('aria-expanded', 'true');
                    },
                    onHide: () => FilterElm.setAttribute('aria-expanded', 'false')
                });
            });
        },

        $setSelectionState: function (count) {
            const Grid = this.$Search?.getGrid();

            if (!Grid) {
                return;
            }

            Grid.getButton('edit')[count === 1 ? 'enable' : 'disable']();
            Grid.getButton('delete')[count > 0 ? 'enable' : 'disable']();
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const self = this;

            this.refresh().then(function () {
                self.Loader.hide();
            });
        },

        /**
         * Opens the create child dialog
         */
        createChild: function () {
            const self = this;

            this.Loader.show();

            this.createSheet({
                title: QUILocale.get(lg, 'fields.create.title'),
                events: {
                    onShow: function (Sheet) {
                        Sheet.getContent().setStyle('padding', 20);

                        const Field = new CreateField({
                            events: {
                                onLoaded: function () {
                                    self.Loader.hide();
                                }
                            }
                        }).inject(Sheet.getContent());

                        Sheet.addButton(
                            new QUIButton({
                                text: QUILocale.get('quiqqer/core', 'save'),
                                textimage: 'fa fa-save',
                                events: {
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
            const self = this;

            this.Loader.show();

            require([
                'package/quiqqer/products/bin/controls/fields/windows/Field'
            ], function (FieldWindow) {
                new FieldWindow({
                    fieldId: fieldId,
                    events: {
                        onOpen: function () {
                            self.Loader.hide();
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number|Array} fieldId
         */
        deleteChild: function (fieldId) {
            const self = this;

            if (typeOf(fieldId) !== 'array') {
                fieldId = [fieldId];
            }

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.window.delete.title'),
                text: QUILocale.get(lg, 'fields.window.delete.text'),
                information: QUILocale.get(lg, 'fields.window.delete.description', {
                    fields: fieldId.join(',')
                }),
                autoclose: false,
                maxHeight: 400,
                maxWidth: 600,
                icon: 'fa fa-trashcan',
                texticon: 'fa fa-trashcan',
                events: {
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
