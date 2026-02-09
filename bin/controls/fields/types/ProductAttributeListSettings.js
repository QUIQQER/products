define('package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'controls/lang/InputMultiLang',
    'package/quiqqer/products/bin/utils/Calc',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings.html',
    'text!package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettingsCreate.html',
    'css!package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings.css'

], function (QUI, QUIControl, QUIConfirm, QUILocale, Grid, InputMultiLang, Calc, Mustache, template, templateCreate) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings',

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
            this.$data = [];

            this.$Grid = null;
            this.$Priority = null;
            this.$CalcBasis = null;

            // price container
            this.$PriceCalc = null;
            this.$DisplayDiscounts = null;
            this.$GenerateTags = null;
            this.$UserInput = null;

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
                'class': 'quiqqer-products-attributeList-settings-title',
                html: QUILocale.get(lg, 'product.fields.attributeList.entry.title'),
                styles: {
                    marginTop: 20
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
                    icon: 'fa fa-angle-up',
                    disabled: true,
                    events: {
                        onClick: function () {
                            this.$moveup();
                            // this.$refreshSorting();
                        }.bind(this)
                    }
                }, {
                    name: 'down',
                    icon: 'fa fa-angle-down',
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
                            const selected = self.$Grid.getSelectedIndices();

                            if (selected.length) {
                                self.openEditDialog(selected[0]);
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
                    header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                    title: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                    dataIndex: 'selected',
                    dataType: 'node',
                    width: 60
                }, {
                    header: QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType: 'string',
                    width: 180
                }, {
                    header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.sum'),
                    dataIndex: 'sum',
                    dataType: 'number',
                    width: 100
                }, {
                    header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.type'),
                    dataIndex: 'type',
                    dataType: 'node',
                    width: 100
                }, {
                    header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.userinput'),
                    title: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.userinput'),
                    dataIndex: 'userinputIcon',
                    dataType: 'node',
                    width: 80
                }, {
                    dataIndex: 'userinput',
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
                        self.$Grid.getSelectedIndices()[0]
                    );
                }
            });

            this.$PriceCalc = new Element('div', {
                'class': 'quiqqer-products-attributeList-settings',
                html: Mustache.render(template, {
                    title: QUILocale.get(lg, 'product.fields.attributeList.title'),
                    priority: QUILocale.get(lg, 'product.fields.attributeList.priority'),
                    discounts: QUILocale.get(lg, 'product.fields.attributeList.discounts'),
                    userInput: QUILocale.get(lg, 'product.fields.attributeList.userInput'),
                    generateTags: QUILocale.get(lg, 'product.fields.attributeList.generateTags'),

                    calcBasis: QUILocale.get(lg, 'product.fields.attributeList.calcBasis'),
                    calcBasisNetto: QUILocale.get(lg, 'product.fields.attributeList.calcBasis.netto'),
                    calcBasisCalcPrice: QUILocale.get(lg, 'product.fields.grid.calcBasis.calculationBasisCalcPrice')
                })
            }).inject(this.$Elm, 'top');

            this.$Priority = this.$PriceCalc.getElement('[name="price_priority"]');
            this.$CalcBasis = this.$PriceCalc.getElement('[name="price_calculation_basis"]');
            this.$DisplayDiscounts = this.$PriceCalc.getElement('[name="display_discounts"]');
            this.$GenerateTags = this.$PriceCalc.getElement('[name="generate_tags"]');
            this.$UserInput = this.$PriceCalc.getElement('[name="userinput"]');

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

            let data = {},
                result = [];

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ("entries" in data) {
                    for (let i = 0, len = data.entries.length; i < len; i++) {
                        if (!("title" in data.entries[i])) {
                            continue;
                        }

                        if (!("sum" in data.entries[i])) {
                            continue;
                        }

                        if (!("type" in data.entries[i])) {
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


            if ("priority" in data) {
                this.$Priority.value = data.priority;
            }

            if ("calculation_basis" in data) {
                this.$CalcBasis.value = data.calculation_basis;
            }

            if ("display_discounts" in data) {
                this.$DisplayDiscounts.checked = data.display_discounts;
            } else {
                this.$DisplayDiscounts.checked = false;
            }

            if ("generate_tags" in data) {
                this.$GenerateTags.checked = data.generate_tags;
            } else {
                this.$GenerateTags.checked = false;
            }

            if ("userinput" in data) {
                this.$UserInput.checked = data.userinput;
            } else {
                this.$UserInput.checked = false;
            }

            this.$Priority.addEvent('change', this.update);
            this.$CalcBasis.addEvent('change', this.update);
            this.$DisplayDiscounts.addEvent('change', this.update);
            this.$GenerateTags.addEvent('change', this.update);
            this.$UserInput.addEvent('change', this.update);
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
            let i, len, entry, langTitle, type, userInputIcon;
            const data = [];

            const currentLang = QUILocale.getCurrent();

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry = this.$data[i];

                if (!("title" in entry)) {
                    continue;
                }

                if (!("sum" in entry)) {
                    continue;
                }

                if (!("type" in entry)) {
                    continue;
                }

                if (!("userinput" in entry)) {
                    entry.userinput = false;
                }

                langTitle = '---';
                userInputIcon = new Element('span', {
                    html: '&nbsp;'
                });

                if (typeof entry.title[currentLang] !== 'undefined') {
                    langTitle = entry.title[currentLang];
                }

                if (entry.userinput) {
                    userInputIcon.addClass('fa fa-user');
                    userInputIcon.set('html', '');
                }

                // currency percent
                switch (parseInt(entry.type)) {
                    case Calc.CALCULATION_PERCENTAGE:
                        type = new Element('span', {
                            'class': 'fa fa-percent'
                        });
                        break;

                    // case Calc.CALCULATION_COMPLEMENT:
                    default:
                        type = new Element('span', {
                            'class': 'fa fa-money'
                        });
                        break;
                }

                data.push({
                    title: langTitle,
                    sum: entry.sum,
                    type: type,
                    selected: new Element('span', {
                        'class': entry.selected ? 'fa fa-check-square-o' : 'fa fa-square-o'
                    }),
                    userinput: entry.userinput,
                    userinputIcon: userInputIcon
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
            const self = this;

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.productAttributeList.add.window.title'),
                icon: 'fa fa-plus',
                texticon: false,
                maxHeight: 400,
                maxWidth: 600,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title: QUILocale.get('quiqqer/system', 'title'),
                            priceTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.priceTitle'),
                            deduction: QUILocale.get(lg, 'fields.control.productAttributeList.create.deduction'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.selected'),
                            userInputTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.userinput')
                        }));

                        const Form = Win.getContent().getElement('form');

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.add(
                            Title.getData(),
                            Form.elements.sum.value,
                            Form.elements.type.value,
                            Form.elements.selected.checked,
                            Form.elements.userinput.checked
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

            const self = this,
                data = this.$data[index];

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.productAttributeList.edit.window.title'),
                icon: 'fa fa-edit',
                maxHeight: 400,
                maxWidth: 600,
                events: {
                    onOpen: function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title: QUILocale.get('quiqqer/system', 'title'),
                            priceTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.priceTitle'),
                            deduction: QUILocale.get(lg, 'fields.control.productAttributeList.create.deduction'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.selected'),
                            userInputTitle: QUILocale.get(lg, 'fields.control.productAttributeList.create.userinput')
                        }));

                        const Form = Win.getContent().getElement('form');

                        Form.elements.title.value = JSON.encode(data.title);
                        Form.elements.sum.value = data.sum;
                        Form.elements.type.value = data.type;
                        Form.elements.selected.checked = data.selected;
                        Form.elements.userinput.checked = data.userinput;

                        new InputMultiLang().imports(Form.elements.title);
                    },
                    onSubmit: function (Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        self.edit(
                            index,
                            Title.getData(),
                            Form.elements.sum.value,
                            parseInt(Form.elements.type.value),
                            Form.elements.selected.checked,
                            Form.elements.userinput.checked
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
                data = this.$Grid.getSelectedData(),
                indices = this.$Grid.getSelectedIndices();

            const titles = data.map(function (Entry) {
                return Entry.title;
            });

            if (!titles.length) {
                return;
            }

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.productAttributeList.remove.window.title'),
                icon: 'fa fa-trash',
                texticon: 'fa fa-trash',
                text: QUILocale.get(lg, 'fields.control.productAttributeList.remove.window.text'),
                information: titles.join(','),
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
            const from = this.$Grid.getSelectedIndices();

            if (from === 0) {
                return;
            }

            const to = from - 1;

            this.$data.splice(to, 0, this.$data.splice(from, 1)[0]);
            this.$Grid.moveup();
            this.update();
        },

        /**
         * entry move down
         */
        $movedown: function () {
            const from = this.$Grid.getSelectedIndices();

            if (from === this.$data.length - 1) {
                return;
            }

            const to = from + 1;

            this.$data.splice(to, 0, this.$data.splice(from, 1)[0]);
            this.$Grid.movedown();
            this.update();
        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode({
                entries: this.$data,
                priority: this.$Priority.value,
                calculation_basis: this.$CalcBasis.value,
                display_discounts: this.$DisplayDiscounts.checked,
                generate_tags: this.$GenerateTags.checked,
                userinput: this.$UserInput.checked
            });
        },

        /**
         * Add an entry
         *
         * @param {Object|String} title
         * @param {Number} sum
         * @param {String} type
         * @param {Boolean} selected
         * @param {Boolean} userinput
         */
        add: function (title, sum, type, selected, userinput) {
            switch (parseInt(type)) {
                case Calc.CALCULATION_PERCENTAGE:
                    type = Calc.CALCULATION_PERCENTAGE;
                    break;

                // case Calc.CALCULATION_COMPLEMENT:
                default:
                    type = Calc.CALCULATION_COMPLEMENT;
                    break;
            }

            // if this is selected, the orthers must be deselected
            if (Boolean(selected)) {
                for (let i = 0, len = this.$data.length; i < len; i++) {
                    this.$data[i].selected = false;
                }
            }

            this.$data.push({
                title: title,
                sum: sum,
                type: type,
                selected: Boolean(selected),
                userinput: Boolean(userinput)
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

            const newData = [];

            const mustBeDeleted = function (wanted) {
                if ((typeOf(index) === 'string' || typeOf(index) === 'number') &&
                    index == wanted) {
                    return true;
                }

                if (typeOf(index) === 'array') {
                    for (let i = 0, len = index.length; i < len; i++) {
                        if (index[i] == wanted) {
                            return true;
                        }
                    }
                }

                return false;
            };

            for (let i = 0, len = this.$data.length; i < len; i++) {
                if (mustBeDeleted(i) === false) {
                    newData.push(this.$data[i]);
                }
            }

            this.$data = newData;

            this.refresh();
            this.update();
        },

        /**
         *
         * @param {Number} index
         * @param {Object|String} title
         * @param {Number} sum
         * @param {String} type
         * @param {Boolean} selected
         * @param {String} userinput
         */
        edit: function (index, title, sum, type, selected, userinput) {
            // if selected, then all others unselected
            if (selected) {
                this.$data.each(function (entry, key) {
                    this.$data[key].selected = false;
                }.bind(this));
            }

            this.$data[index] = {
                title: title,
                sum: sum,
                type: type,
                selected: selected,
                userinput: userinput
            };

            this.refresh();
            this.update();
        }
    });
});
