/**
 * @module package/quiqqer/products/bin/controls/fields/types/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/utils/Calc',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings.html',
    'text!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettingsCreate.html',
    'css!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings.css'

], function (QUI, QUIControl, QUIConfirm, QUILocale, Grid, InputMultiLang, Calc, Mustache, template, templateCreate) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings',

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

            this.$Input        = null;
            this.$Grid         = null;
            this.$GenerateTags = null;

            this.$data = [];

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
                'class': 'quiqqer-products-attributeList-settings-title',
                html   : QUILocale.get(lg, 'product.fields.attributeList.entry.title'),
                styles : {
                    marginTop: 20
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
                buttons    : [{
                    name    : 'up',
                    icon    : 'fa fa-angle-up',
                    disabled: true,
                    events  : {
                        onClick: function () {
                            this.$moveup();
                        }.bind(this)
                    }
                }, {
                    name    : 'down',
                    icon    : 'fa fa-angle-down',
                    disabled: true,
                    events  : {
                        onClick: function () {
                            this.$movedown();
                        }.bind(this)
                    }
                }, {
                    type: 'separator'
                }, {
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
                    header   : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                    title    : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                    dataIndex: 'selected',
                    dataType : 'node',
                    width    : 30
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.valueId'),
                    title    : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.valueId.description'),
                    dataIndex: 'valueId',
                    dataType : 'string',
                    width    : 200
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

            this.$PriceCalc = new Element('div', {
                'class': 'quiqqer-products-attributeList-settings',
                html   : Mustache.render(template, {
                    title       : QUILocale.get(lg, 'product.fields.attribute.group.attributeList.title'),
                    generateTags: QUILocale.get(lg, 'product.fields.attributeList.generateTags')
                })
            }).inject(this.$Elm, 'top');

            this.$GenerateTags = this.$PriceCalc.getElement('[name="generate_tags"]');

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

            if ("generate_tags" in data) {
                this.$GenerateTags.checked = data.generate_tags;
            } else {
                this.$GenerateTags.checked = false;
            }

            this.$GenerateTags.addEvent('change', this.update);
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
         * refresh the grid data display
         */
        refresh: function () {
            var i, len, entry, langTitle, Selected;
            var data = [];

            var currentLang = QUILocale.getCurrent();

            var IsSelected = new Element('span', {
                'class': 'fa fa-check'
            });

            var IsNotSelected = new Element('span', {
                'class': 'fa fa-minus'
            });

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry = this.$data[i];

                if (!("title" in entry)) {
                    continue;
                }

                langTitle = '---';

                if (typeof entry.title[currentLang] !== 'undefined') {
                    langTitle = entry.title[currentLang];
                }

                if (typeof entry.selected !== 'undefined' && entry.selected) {
                    Selected = IsSelected.clone();
                } else {
                    Selected = IsNotSelected.clone();
                }


                data.push({
                    selected: Selected,
                    title   : langTitle,
                    valueId : entry.valueId
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

            new QUIConfirm({
                title    : QUILocale.get(lg, 'fields.control.attributeGroup.create.add.window.title'),
                icon     : 'fa fa-plus',
                texticon : false,
                maxHeight: 400,
                maxWidth : 600,
                autoclose: false,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title        : QUILocale.get('quiqqer/system', 'title'),
                            valueId      : QUILocale.get(lg, 'fields.control.attributeGroup.create.valueId'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.attributeGroup.create.selected')
                        }));

                        var Form = Win.getContent().getElement('form');

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        var Form  = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        if (Form.elements.valueId.value === '') {
                            Form.elements.valueId.focus();
                            Form.reportValidity();
                            return;
                        }

                        self.add(
                            Title.getData(),
                            Form.elements.valueId.value,
                            Form.elements.selected.checked
                        );

                        Win.close();
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

            new QUIConfirm({
                title    : QUILocale.get(lg, 'fields.control.attributeGroup.edit.window.title'),
                icon     : 'fa fa-edit',
                maxHeight: 400,
                maxWidth : 600,
                autoclose: false,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title        : QUILocale.get('quiqqer/system', 'title'),
                            valueId      : QUILocale.get(lg, 'fields.control.attributeGroup.create.valueId'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.attributeGroup.create.selected')
                        }));

                        var Form = Win.getContent().getElement('form');

                        Form.elements.title.value      = JSON.encode(data.title);
                        Form.elements.valueId.value    = data.valueId;
                        Form.elements.selected.checked = data.selected;

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        var Form  = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        if (Form.elements.valueId.value === '') {
                            Form.elements.valueId.focus();
                            Form.reportValidity();
                            return;
                        }

                        self.edit(
                            index,
                            Title.getData(),
                            Form.elements.valueId.value,
                            Form.elements.selected.checked
                        );

                        Win.close();
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
                title      : QUILocale.get(lg, 'fields.control.attributeGroup.remove.window.title'),
                icon       : 'fa fa-trash',
                texticon   : 'fa fa-trash',
                text       : QUILocale.get(lg, 'fields.control.attributeGroup.remove.window.text'),
                information: titles.join(','),
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
                entries      : this.$data,
                generate_tags: this.$GenerateTags.checked
            });
        },

        /**
         * Add an entry
         *
         * @param {Object|String} title
         * @param {String} valueId
         * @param {Boolean}  [selected]
         */
        add: function (title, valueId, selected) {
            selected = selected || false;

            this.$data.push({
                title   : title,
                valueId : valueId,
                selected: selected
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
                if ((typeOf(index) === 'string' || typeOf(index) === 'number') && index === wanted) {
                    return true;
                }

                if (typeOf(index) === 'array') {
                    for (var i = 0, len = index.length; i < len; i++) {
                        if (index[i] === wanted) {
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
         * Edit an entry
         *
         * @param {Number} index
         * @param {Object|String} title
         * @param {String} valueId
         * @param {Boolean} [selected]
         */
        edit: function (index, title, valueId, selected) {
            this.$data[index] = {
                title   : title,
                valueId : valueId,
                selected: selected || false
            };

            this.refresh();
            this.update();
        }
    });
});
