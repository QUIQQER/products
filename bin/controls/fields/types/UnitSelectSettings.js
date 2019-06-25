/**
 * @module package/quiqqer/products/bin/controls/fields/types/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Patrick Müller)
 */
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

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/UnitSelectSettings',

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
            this.$data  = [];

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
                    width  : '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var Parent = this.$Elm.getParent('.field-options');

            if (Parent) {
                Parent.setStyle('padding', 0);
            }

            new Element('div', {
                'class': 'quiqqer-products-unitselect-settings-title',
                html   : QUILocale.get(lg, 'product.fields.unitSelect.entry.title'),
                styles : {
                    margin: '10px 0 0 10px'
                }
            }).inject(this.$Elm);

            var Width = new Element('div', {
                styles: {
                    'float': 'left',
                    margin : 10,
                    width  : 'calc(100% - 20px)'
                }
            }).inject(this.$Elm);

            var Container = new Element('div', {
                styles: {
                    'float': 'left',
                    height : 300,
                    width  : '100%'
                }
            }).inject(Width);

            var self = this,
                size = Width.getSize();

            this.$Grid = new Grid(Container, {
                perPage    : 150,
                buttons    : [/*{
                    name     : 'up',
                    textimage: 'fa fa-angle-up',
                    disabled : true,
                    events   : {
                        onClick: function () {
                            this.$moveup();
                            // this.$refreshSorting();
                        }.bind(this)
                    }
                }, {
                    name     : 'down',
                    textimage: 'fa fa-angle-down',
                    disabled : true,
                    events   : {
                        onClick: function () {
                            this.$movedown();
                            // this.$Grid.movedown();
                            // this.$refreshSorting();
                        }.bind(this)
                    }
                }, {
                    type: 'separator'
                },*/ {
                    name     : 'add',
                    textimage: 'fa fa-plus',
                    text     : QUILocale.get('quiqqer/system', 'add'),
                    events   : {
                        onClick: this.openAddDialog
                    }
                }, {
                    name     : 'edit',
                    textimage: 'fa fa-edit',
                    text     : QUILocale.get('quiqqer/system', 'edit'),
                    disabled : true,
                    events   : {
                        onClick: function () {
                            var selected = self.$Grid.getSelectedIndices();

                            if (selected.length) {
                                self.openEditDialog(selected[0]);
                            }
                        }
                    }
                }, {
                    type: 'separator'
                }, {
                    name     : 'delete',
                    textimage: 'fa fa-trash',
                    text     : QUILocale.get('quiqqer/system', 'delete'),
                    disabled : true,
                    events   : {
                        onClick: this.openRemoveDialog
                    }
                }],
                columnModel: [{
                    header   : QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.default'),
                    title    : QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.default'),
                    dataIndex: 'default',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 175
                }, {
                    header   : QUILocale.get(lg, 'fields.control.UnitSelectSettings.grid.quantityInput'),
                    dataIndex: 'quantityInputCheck',
                    dataType : 'node',
                    width    : 100
                }]
            });

            this.$Grid.setHeight(size.y);
            this.$Grid.setWidth(size.x);

            this.$Grid.addEvents({
                onClick   : this.$buttonReset,
                onDblClick: function () {
                    self.$buttonReset();

                    self.openEditDialog(
                        self.$Grid.getSelectedIndices()[0]
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
            this.$Elm   = this.create();

            var data   = {},
                result = [];

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ("entries" in data) {
                    for (var i = 0, len = data.entries.length; i < len; i++) {
                        if (!("title" in data.entries[i])) {
                            continue;
                        }

                        if (!("default" in data.entries[i])) {
                            continue;
                        }

                        if (!("quantityInput" in data.entries[i])) {
                            continue;
                        }

                        result.push(data.entries[i]);
                    }

                    this.$data = result;
                }

            } catch (e) {
                console.error(this.$Input.value);
                console.error(e);
            }

            if (!this.$data) {
                this.$data = [];
            }

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * reset the buttons from the grid
         * disable or enable the buttons dependent on selected indices
         */
        $buttonReset: function () {
            var selected = this.$Grid.getSelectedIndices(),
                buttons  = this.$Grid.getButtons();

            var Up = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'up';
            })[0];

            var Down = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'down';
            })[0];

            var Edit = buttons.filter(function (Button) {
                return Button.getAttribute('name') === 'edit';
            })[0];

            var Delete = buttons.filter(function (Button) {
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
            var i, len, entry, langTitle;
            var data = [];

            var currentLang = QUILocale.getCurrent();

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry     = this.$data[i];
                langTitle = '---';

                if (typeof entry.title[currentLang] !== 'undefined') {
                    langTitle = entry.title[currentLang];
                }

                data.push({
                    title             : langTitle,
                    default           : new Element('span', {
                        'class': entry.default ? 'fa fa-check-square-o' : 'fa fa-square-o'
                    }),
                    quantityInputCheck: new Element('span', {
                        'class': entry.quantityInput ? 'fa fa-check-square-o' : 'fa fa-square-o'
                    })
                });
            }

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
            var self = this;

            var lgPrefix = 'fields.control.UnitSelectSettings.';

            new QUIConfirm({
                title    : QUILocale.get(lg, lgPrefix + 'add.title'),
                icon     : 'fa fa-plus',
                texticon : false,
                maxHeight: 400,
                maxWidth : 600,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            titleLabel        : QUILocale.get(lg, lgPrefix + 'add.titleLabel'),
                            defaultLabel      : QUILocale.get(lg, lgPrefix + 'add.defaultLabel'),
                            quantityInputLabel: QUILocale.get(lg, lgPrefix + 'add.quantityInputLabel')
                        }));

                        var Form = Win.getContent().getElement('form');

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        var Form  = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.add(
                            Title.getData(),
                            Form.elements.default.checked,
                            Form.elements.quantityInput.checked
                        );
                    }
                }
            }).open();
        },

        /**
         * opens the edit dialog
         *
         * @param {Number} index - row index
         */
        openEditDialog: function (index) {
            if (typeof index === 'undefined') {
                return;
            }

            if (typeof this.$data[index] === 'undefined') {
                return;
            }

            var self = this,
                data = this.$data[index];

            var lgPrefix = 'fields.control.UnitSelectSettings.';

            new QUIConfirm({
                title    : QUILocale.get(lg, lgPrefix + 'edit.title'),
                icon     : 'fa fa-edi',
                texticon : false,
                maxHeight: 400,
                maxWidth : 600,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            titleLabel        : QUILocale.get(lg, lgPrefix + 'add.titleLabel'),
                            defaultLabel      : QUILocale.get(lg, lgPrefix + 'add.defaultLabel'),
                            quantityInputLabel: QUILocale.get(lg, lgPrefix + 'add.quantityInputLabel')
                        }));

                        var Form = Win.getContent().getElement('form');

                        Form.elements.title.value           = JSON.encode(data.title);
                        Form.elements.default.checked       = data.default;
                        Form.elements.quantityInput.checked = data.quantityInput;

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        var Form  = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.edit(
                            index,
                            Title.getData(),
                            Form.elements.default.checked,
                            Form.elements.quantityInput.checked
                        );
                    }
                }
            }).open();
        },

        /**
         * open remove dialog
         */
        openRemoveDialog: function () {
            var self    = this,
                data    = this.$Grid.getSelectedData(),
                indices = this.$Grid.getSelectedIndices();

            var titles = data.map(function (Entry) {
                return Entry.title;
            });

            if (!titles.length) {
                return;
            }

            new QUIConfirm({
                title      : QUILocale.get(lg, 'fields.control.UnitSelectSettings.remove.title'),
                icon       : 'fa fa-trash',
                texticon   : 'fa fa-trash',
                text       : QUILocale.get(lg, 'fields.control.UnitSelectSettings.remove.text'),
                information: titles.join(', '),
                maxHeight  : 300,
                maxWidth   : 450,
                events     : {
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
            var from = this.$Grid.getSelectedIndices();

            if (from === 0) {
                return;
            }

            var to = from - 1;

            this.$data.splice(to, 0, this.$data.splice(from, 1)[0]);
            this.$Grid.moveup();
            this.update();
        },

        /**
         * entry move down
         */
        $movedown: function () {
            var from = this.$Grid.getSelectedIndices();

            if (from === this.$data.length - 1) {
                return;
            }

            var to = from + 1;

            this.$data.splice(to, 0, this.$data.splice(from, 1)[0]);
            this.$Grid.movedown();
            this.update();
        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode({
                entries: this.$data
            });
        },

        /**
         * Add an entry
         *
         * @param {Object|String} title
         * @param {Boolean} isDefault
         * @param {Boolean} quantityInput
         */
        add: function (title, isDefault, quantityInput) {
            // if this is selected, the orthers must be deselected
            if (Boolean(isDefault)) {
                for (var i = 0, len = this.$data.length; i < len; i++) {
                    this.$data[i].selected = false;
                }
            }

            this.$data.push({
                title        : title,
                'default'    : Boolean(isDefault),
                quantityInput: Boolean(quantityInput)
            });

            this.refresh();
            this.update();
        },

        /**
         * Remove entries
         *
         * @param {Number|Array} index - Row number(s)
         */
        remove: function (index) {
            if (!this.$Grid) {
                return;
            }

            var newData = [];

            var mustBeDeleted = function (wanted) {
                if ((typeOf(index) === 'string' || typeOf(index) === 'number') &&
                    index == wanted) {
                    return true;
                }

                if (typeOf(index) === 'array') {
                    for (var i = 0, len = index.length; i < len; i++) {
                        if (index[i] == wanted) {
                            return true;
                        }
                    }
                }

                return false;
            };

            for (var i = 0, len = this.$data.length; i < len; i++) {
                if (mustBeDeleted(i) === false) {
                    newData.push(this.$data[i]);
                }
            }

            this.$data = newData;

            this.refresh();
            this.update();
        },

        /**
         * Edit field entry
         *
         * @param {Number} index
         * @param {String} title
         * @param {Boolean} isDefault
         * @param {Boolean} quantityInput
         * @param {Object|String} title
         */
        edit: function (index, title, isDefault, quantityInput) {
            // if selected, then all others unselected
            if (isDefault) {
                this.$data.each(function (entry, key) {
                    this.$data[key].default = false;
                }.bind(this));
            }

            this.$data[index] = {
                title        : title,
                'default'    : Boolean(isDefault),
                quantityInput: Boolean(quantityInput)
            };

            this.refresh();
            this.update();
        }
    });
});
