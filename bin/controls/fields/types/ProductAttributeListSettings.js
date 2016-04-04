/**
 * @module package/quiqqer/products/bin/controls/fields/types/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'controls/lang/InputMultiLang',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettingsCreate.html',
    'css!package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings.css'

], function (QUI, QUIControl, QUIConfirm, QUILocale, Grid, InputMultiLang, Mustache, templateCreate) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings',

        Binds: [
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

            this.$Grid  = null;
            this.$Input = null;
            this.$data  = [];

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

            var Container = new Element('div', {
                styles: {
                    'float': 'left',
                    height : 300
                }
            }).inject(this.$Elm);

            var self = this,
                size = this.$Elm.getSize();

            this.$Grid = new Grid(Container, {
                buttons    : [{
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
                    type: 'seperator'
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
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.price'),
                    dataIndex: 'price',
                    dataType : 'number',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.discountType'),
                    dataIndex: 'discount',
                    dataType : 'node',
                    width    : 60
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

            try {
                this.$data = JSON.decode(this.$Input.value);
            } catch (e) {
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

            var Edit = buttons.filter(function (Button) {
                return Button.getAttribute('name') == 'edit';
            })[0];

            var Delete = buttons.filter(function (Button) {
                return Button.getAttribute('name') == 'delete';
            })[0];


            if (selected.length == 1) {
                Edit.enable();
                Delete.enable();
                return;
            }

            Edit.disable();

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
            var i, len, entry, langTitle, discount;
            var data = [];

            var currentLang = QUILocale.getCurrent();

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry = this.$data[i];

                if (!("title" in entry)) {
                    continue;
                }

                if (!("price" in entry)) {
                    continue;
                }

                if (!("discount" in entry)) {
                    continue;
                }

                langTitle = '---';

                if (typeof entry.title[currentLang] !== 'undefined') {
                    langTitle = entry.title[currentLang];
                }

                // currency percent
                switch (entry.discount) {
                    case 'percent':
                        discount = new Element('span', {
                            'class': 'fa fa-percent'
                        });
                        break;

                    default:
                        discount = new Element('span', {
                            'class': 'fa fa-money'
                        });
                        break;
                }


                data.push({
                    title   : langTitle,
                    price   : entry.price,
                    discount: discount
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
                title    : 'Eintrag für die Auswahlliste hinzufügen',
                icon     : 'fa fa-plus',
                texticon : false,
                maxHeight: 300,
                maxWidth : 450,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate));

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
                            Form.elements.price.value,
                            Form.elements.discount.value
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

            new QUIConfirm({
                title    : 'Eintrag aus der Auswahlliste editieren',
                icon     : 'fa fa-edit',
                maxHeight: 300,
                maxWidth : 450,
                events   : {
                    onOpen  : function (Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate));

                        var Form = Win.getContent().getElement('form');

                        Form.elements.title.value    = JSON.encode(data.title);
                        Form.elements.price.value    = data.price;
                        Form.elements.discount.value = data.discount;

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
                            Form.elements.price.value,
                            Form.elements.discount.value
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
                title      : 'Eintrag aus der Auswahlliste entfernen?',
                icon       : 'fa fa-trash',
                texticon   : 'fa fa-trash',
                text       : 'Möchten Sie folgende Einträge wirklich löschen?',
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
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode(this.$data);
        },

        /**
         *
         * @param {Object|String} title
         * @param {Number} price
         * @param {String} discount
         */
        add: function (title, price, discount) {
            this.$data.push({
                title   : title,
                price   : price,
                discount: discount
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
         *
         * @param {Number} index
         * @param {Object|String} title
         * @param {Number} price
         * @param {String} discount
         */
        edit: function (index, title, price, discount) {
            this.$data[index] = {
                title   : title,
                price   : price,
                discount: discount
            };

            this.refresh();
            this.update();
        }
    });
});
