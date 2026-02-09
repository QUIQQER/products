define('package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/utils/Calc',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings.Create.html',
    'css!package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings.css'

], function (QUI, QUIControl, QUIConfirm, QUILocale, Grid, InputMultiLang, Calc, Mustache, templateCreate) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings',

        Binds: [
            'update',
            'openAddDialog',
            'openEditDialog',
            'openRemoveDialog',
            '$onInject',
            '$onImport',
            '$buttonReset'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Data = {};
            this.$Entries = {};

            this.$Grid = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    width: '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const Parent = this.$Elm.getParent('.field-options');

            if (Parent) {
                Parent.setStyle('padding', 0);
            }

            new Element('div', {
                'class': 'quiqqer-products-unitselect-settings-title',
                html: QUILocale.get(lg, 'product.fields.unitSelect.entry.title'),
                styles: {
                    margin: '10px 0 0 10px'
                }
            }).inject(this.$Elm);

            const Width = new Element('div', {
                styles: {
                    'float': 'left',
                    margin: 10,
                    width: 'calc(100% - 20px)'
                }
            }).inject(this.$Elm);

            const Container = new Element('div', {
                styles: {
                    'float': 'left',
                    height: 300,
                    width: '100%'
                }
            }).inject(Width);

            const self = this,
                size = Width.getSize();

            this.$Grid = new Grid(Container, {
                perPage: 150,
                buttons: [{
                    name: 'up',
                    textimage: 'fa fa-angle-up',
                    disabled: true,
                    events: {
                        onClick: function () {
                            this.$moveup();
                            // this.$refreshSorting();
                        }.bind(this)
                    }
                }, {
                    name: 'down',
                    textimage: 'fa fa-angle-down',
                    disabled: true,
                    events: {
                        onClick: function () {
                            this.$movedown();
                            // this.$Grid.movedown();
                            // this.$refreshSorting();
                        }.bind(this)
                    }
                }, {
                    type: 'separator'
                }, {
                    name: 'add',
                    textimage: 'fa fa-plus',
                    text: QUILocale.get('quiqqer/system', 'add'),
                    events: {
                        onClick: this.openAddDialog
                    }
                }, {
                    name: 'edit',
                    textimage: 'fa fa-edit',
                    text: QUILocale.get('quiqqer/system', 'edit'),
                    disabled: true,
                    events: {
                        onClick: function () {
                            const selected = self.$Grid.getSelectedData();

                            if (selected.length) {
                                self.openEditDialog(selected[0].id);
                            }
                        }
                    }
                }, {
                    type: 'separator'
                }, {
                    name: 'delete',
                    textimage: 'fa fa-trash',
                    text: QUILocale.get('quiqqer/system', 'delete'),
                    disabled: true,
                    events: {
                        onClick: this.openRemoveDialog
                    }
                }],
                columnModel: [{
                    header: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.id'),
                    title: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.id'),
                    dataIndex: 'id',
                    dataType: 'string',
                    width: 50
                }, {
                    header: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.default'),
                    title: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.default'),
                    dataIndex: 'default',
                    dataType: 'node',
                    width: 60
                }, {
                    header: QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType: 'string',
                    width: 75
                }, {
                    header: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.quantityInput'),
                    dataIndex: 'quantityInputCheck',
                    dataType: 'node',
                    width: 100
                }, {
                    header: QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.defaultQuantity'),
                    dataIndex: 'defaultQuantity',
                    dataType: 'integer',
                    width: 100
                }, {
                    dataIndex: 'pos',
                    dataType: 'number',
                    hidden: true
                }]
            });

            this.$Grid.setHeight(size.y);
            this.$Grid.setWidth(size.x);

            this.$Grid.addEvents({
                onClick: this.$buttonReset,
                onDblClick: function () {
                    self.$buttonReset();

                    self.openEditDialog(
                        self.$Grid.getSelectedData()[0].id
                    );
                }
            });

            this.refresh();
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function (self, Node) {
            this.$Input = Node;
            this.$Elm = this.create();

            try {
                this.$Data = JSON.decode(this.$Input.value);

                if (typeOf(this.$Data.entries) !== 'object' && !this.$Data.entries.length) {
                    this.$Entries = {};
                } else {
                    this.$Entries = this.$Data.entries;
                }
            } catch (e) {
                console.error(this.$Input.value);
                console.error(e);
            }

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * reset the buttons from the grid
         * disable or enable the buttons dependent on selected indices
         */
        $buttonReset: function () {
            const selected = this.$Grid.getSelectedIndices(),
                buttons = this.$Grid.getButtons();

            const Up = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'up';
            })[0];

            const Down = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'down';
            })[0];

            const Edit = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'edit';
            })[0];

            const Delete = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'delete';
            })[0];


            if (selected.length === 1) {
                Edit.enable();
                Delete.enable();
                Up.enable();
                Down.enable();
                return;
            }

            Edit.disable();
            Up.disable();
            Down.disable();

            if (selected.length > 1) {
                Delete.enable();
                return;
            }

            Delete.disable();
        },

        /**
         * refresh the grid data dispaly
         */
        refresh: function () {
            let entry, langTitle;
            const data = [];

            const currentLang = QUILocale.getCurrent();

            for (let id in this.$Entries) {
                if (!this.$Entries.hasOwnProperty(id)) {
                    continue;
                }

                entry = this.$Entries[id];
                langTitle = '---';

                if (typeof entry.title[currentLang] !== 'undefined') {
                    langTitle = entry.title[currentLang];
                }

                data.push({
                    id: id,
                    title: langTitle,
                    default: new Element('span', {
                        'class': entry.default ? 'fa fa-check-square-o' : 'fa fa-square-o'
                    }),
                    quantityInputCheck: new Element('span', {
                        'class': entry.quantityInput ? 'fa fa-check-square-o' : 'fa fa-square-o'
                    }),
                    defaultQuantity: entry.defaultQuantity ? entry.defaultQuantity : '',
                    pos: entry.pos
                });
            }

            // sort by pos
            data.sort(function (a, b) {
                return a.pos - b.pos;
            });

            this.$Grid.setData({
                data: data
            });
        },

        /**
         * dialogs
         */

        /**
         * Opens the add dialog
         */
        openAddDialog: function () {
            const self = this;

            const lgPrefix = 'fields.control.UnitSelectSettings.';

            new QUIConfirm({
                title: QUILocale.get(lg, lgPrefix + 'add.title'),
                icon: 'fa fa-plus',
                texticon: false,
                maxHeight: 400,
                maxWidth: 600,
                autoclose: false,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            idLabel: QUILocale.get(lg, lgPrefix + 'add.idLabel'),
                            titleLabel: QUILocale.get(lg, lgPrefix + 'add.titleLabel'),
                            defaultLabel: QUILocale.get(lg, lgPrefix + 'add.defaultLabel'),
                            quantityInputLabel: QUILocale.get(lg, lgPrefix + 'add.quantityInputLabel'),
                            defaultQuantityLabel: QUILocale.get(lg, lgPrefix + 'add.defaultQuantityLabel')
                        }));

                        const Form = Win.getContent().getElement('form');

                        new InputMultiLang().imports(Form.elements.title);
                        Form.elements.id.focus();
                    },
                    onSubmit: function (Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.add(
                            Form.elements.id.value,
                            Title.getData(),
                            Form.elements.default.checked,
                            Form.elements.quantityInput.checked,
                            Form.elements.defaultQuantity.value.trim()
                        ).then(function () {
                            Win.close();
                        }, function (errorMsg) {
                            QUI.getMessageHandler().then(function (MH) {
                                MH.addError(errorMsg);
                            });
                        });
                    }
                }
            }).open();
        },

        /**
         * opens the edit dialog
         *
         * @param {String} id - entry identifier
         */
        openEditDialog: function (id) {
            if (!(id in this.$Entries)) {
                return;
            }

            const self = this,
                Data = this.$Entries[id];

            const lgPrefix = 'fields.control.UnitSelectSettings.';

            new QUIConfirm({
                title: QUILocale.get(lg, lgPrefix + 'edit.title'),
                icon: 'fa fa-edit',
                texticon: false,
                maxHeight: 400,
                maxWidth: 600,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            idLabel: QUILocale.get(lg, lgPrefix + 'add.idLabel'),
                            titleLabel: QUILocale.get(lg, lgPrefix + 'add.titleLabel'),
                            defaultLabel: QUILocale.get(lg, lgPrefix + 'add.defaultLabel'),
                            quantityInputLabel: QUILocale.get(lg, lgPrefix + 'add.quantityInputLabel'),
                            defaultQuantityLabel: QUILocale.get(lg, lgPrefix + 'add.defaultQuantityLabel')
                        }));

                        const Form = Win.getContent().getElement('form');

                        Form.elements.id.value = id;
                        Form.elements.id.disabled = true;

                        Form.elements.title.value = JSON.encode(Data.title);
                        Form.elements.default.checked = Data.default;
                        Form.elements.quantityInput.checked = Data.quantityInput;
                        Form.elements.defaultQuantity.value = Data.defaultQuantity ? Data.defaultQuantity : '';

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.edit(
                            id,
                            Title.getData(),
                            Form.elements.default.checked,
                            Form.elements.quantityInput.checked,
                            Form.elements.defaultQuantity.value.trim()
                        );
                    }
                }
            }).open();
        },

        /**
         * open remove dialog
         */
        openRemoveDialog: function () {
            const self = this,
                data = this.$Grid.getSelectedData();

            const indices = data.map(function (Entry) {
                return Entry.id;
            });

            if (!indices.length) {
                return;
            }

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.UnitSelectSettings.remove.title'),
                icon: 'fa fa-trash',
                texticon: 'fa fa-trash',
                text: QUILocale.get(lg, 'fields.control.UnitSelectSettings.remove.text'),
                information: indices.join(', '),
                maxHeight: 300,
                maxWidth: 450,
                events: {
                    onSubmit: function () {
                        self.remove(indices);
                    }
                }
            }).open();
        },

        /**
         * entry move up
         */
        $moveup: function () {
            const Entry = this.$Grid.getSelectedData()[0];

            if (Entry.pos === 0) {
                return;
            }

            Entry.pos -= 1;
            this.$Entries[Entry.id].pos = Entry.pos;

            for (const id in this.$Entries) {
                if (!this.$Entries.hasOwnProperty(id)) {
                    continue;
                }

                if (id === Entry.id || this.$Entries[id].pos < Entry.pos) {
                    continue;
                }

                this.$Entries[id].pos += 1;
            }

            this.refresh();
            this.update();
        },

        /**
         * entry move down
         */
        $movedown: function () {
            const Entry = this.$Grid.getSelectedData()[0];

            if (Entry.pos === Object.getLength(this.$Entries)) {
                return;
            }

            Entry.pos += 1;
            this.$Entries[Entry.id].pos = Entry.pos;

            for (const id in this.$Entries) {
                if (!this.$Entries.hasOwnProperty(id)) {
                    continue;
                }

                if (id === Entry.id || this.$Entries[id].pos > Entry.pos) {
                    continue;
                }

                this.$Entries[id].pos -= 1;
            }

            this.refresh();
            this.update();
        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode({
                entries: this.$Entries
            });
        },

        /**
         * Add an entry
         *
         * @param {String} id
         * @param {Object|String} title
         * @param {Boolean} isDefault
         * @param {Boolean} quantityInput
         * @param  {Number} defaultQuantity
         * @return {Promise}
         */
        add: function (id, title, isDefault, quantityInput, defaultQuantity) {
            return new Promise(function (resolve, reject) {
                if (id in this.$Entries) {
                    reject(
                        QUILocale.get(lg, 'fields.control.UnitSelectSettings.add.error_duplicate_id')
                    );

                    return;
                }

                if (!id) {
                    reject(
                        QUILocale.get(lg, 'fields.control.UnitSelectSettings.add.empty_id')
                    );

                    return;
                }

                // if this is selected, the orthers must be deselected
                if (Boolean(isDefault)) {
                    for (const entryId in this.$Entries) {
                        if (!this.$Entries.hasOwnProperty(entryId)) {
                            continue;
                        }

                        this.$Entries[entryId].selected = false;
                    }
                }

                this.$Entries[id] = {
                    title: title,
                    'default': Boolean(isDefault),
                    quantityInput: Boolean(quantityInput),
                    defaultQuantity: defaultQuantity,
                    pos: Object.getLength(this.$Entries)
                };

                this.refresh();
                this.update();
                resolve();
            }.bind(this));
        },

        /**
         * Remove entries
         *
         * @param {Array} ids - entry identifiers
         */
        remove: function (ids) {
            if (!this.$Grid) {
                return;
            }

            for (let i = 0, len = ids.length; i < len; i++) {
                delete this.$Entries[ids[i]];
            }

            let pos = 0;

            for (let id in this.$Entries) {
                if (!this.$Entries.hasOwnProperty(id)) {
                    continue;
                }

                this.$Entries[id].pos = pos++;
            }

            this.refresh();
            this.update();
        },

        /**
         * Edit field entry
         *
         * @param {String} id
         * @param {String} title
         * @param {Boolean} isDefault
         * @param {Boolean} quantityInput
         * @param {String|Number} defaultQuantity
         * @param {Number} [pos]
         */
        edit: function (id, title, isDefault, quantityInput, defaultQuantity, pos) {
            // if selected, then all others unselected
            if (isDefault) {
                for (const dataId in this.$Entries) {
                    if (!this.$Entries.hasOwnProperty(dataId)) {
                        continue;
                    }

                    this.$Entries[dataId].default = false;
                }
            }

            this.$Entries[id] = {
                title: title,
                'default': Boolean(isDefault),
                quantityInput: Boolean(quantityInput),
                defaultQuantity: defaultQuantity,
                pos: typeof pos === "undefined" ? this.$Entries[id].pos : pos
            };

            this.refresh();
            this.update();
        }
    });
});
