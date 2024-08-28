/**
 * Settings for "AttributeGroup" field type.
 *
 * @module package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale',
    'controls/grid/Grid',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings.html',
    'text!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettingsCreate.html',
    'css!package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings.css'

], function(QUI, QUIControl, QUIConfirm, QUILocale, Grid, Mustache, template, templateCreate) {
    'use strict';

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings',

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

        initialize: function(options) {
            this.parent(options);

            this.$Input = null;
            this.$Grid = null;
            this.$GenerateTags = null;
            this.$EntriesType = null;
            this.$IsImageAttribute = null;
            this.$ExcludeVariantGen = null;

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
        create: function() {
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
        $onInject: function() {
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
                buttons: [
                    {
                        name: 'up',
                        icon: 'fa fa-angle-up',
                        disabled: true,
                        events: {
                            onClick: function() {
                                this.$moveup();
                            }.bind(this)
                        }
                    },
                    {
                        name: 'down',
                        icon: 'fa fa-angle-down',
                        disabled: true,
                        events: {
                            onClick: function() {
                                this.$movedown();
                            }.bind(this)
                        }
                    },
                    {
                        type: 'separator'
                    },
                    {
                        name: 'add',
                        textimage: 'fa fa-plus',
                        text: QUILocale.get('quiqqer/system', 'add'),
                        events: {
                            onClick: this.openAddDialog
                        }
                    },
                    {
                        name: 'edit',
                        textimage: 'fa fa-edit',
                        text: QUILocale.get('quiqqer/system', 'edit'),
                        disabled: true,
                        events: {
                            onClick: function() {
                                const selected = self.$Grid.getSelectedIndices();

                                if (selected.length) {
                                    self.openEditDialog(selected[0]);
                                }
                            }
                        }
                    },
                    {
                        type: 'separator'
                    },
                    {
                        name: 'delete',
                        textimage: 'fa fa-trash',
                        text: QUILocale.get('quiqqer/system', 'delete'),
                        disabled: true,
                        events: {
                            onClick: this.openRemoveDialog
                        }
                    }
                ],
                columnModel: [
                    {
                        header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                        title: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.selected'),
                        dataIndex: 'selected',
                        dataType: 'node',
                        width: 30
                    },
                    {
                        header: QUILocale.get('quiqqer/system', 'title'),
                        dataIndex: 'title',
                        dataType: 'string',
                        width: 200
                    },
                    {
                        header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.valueId'),
                        title: QUILocale.get(
                            lg,
                            'fields.control.productAttributeListSettings.grid.valueId.description'
                        ),
                        dataIndex: 'valueId',
                        dataType: 'string',
                        width: 75
                    },
                    {
                        header: QUILocale.get(lg, 'fields.control.productAttributeListSettings.grid.image'),
                        dataIndex: 'imagePreview',
                        dataType: 'node',
                        width: 75
                    }
                ]
            });

            this.$Grid.setHeight(size.y);
            this.$Grid.setWidth(size.x);

            this.$Grid.addEvents({
                onClick: this.$buttonReset,
                onDblClick: function() {
                    self.$buttonReset();

                    self.openEditDialog(
                        self.$Grid.getSelectedIndices()[0]
                    );
                }
            });

            this.$PriceCalc = new Element('div', {
                'class': 'quiqqer-products-attributeList-settings',
                html: Mustache.render(template, {
                    title: QUILocale.get(lg, 'product.fields.attribute.group.attributeList.title'),
                    generateTags: QUILocale.get(lg, 'product.fields.attributeList.generateTags'),
                    labelType: QUILocale.get(lg, 'product.fields.attributeList.labelType'),
                    labelTypeOptionDefault: QUILocale.get(lg, 'product.fields.attributeList.labelTypeOptionDefault'),
                    labelTypeOptionSize: QUILocale.get(lg, 'product.fields.attributeList.labelTypeOptionSize'),
                    labelTypeOptionColor: QUILocale.get(lg, 'product.fields.attributeList.labelTypeOptionColor'),
                    labelTypeOptionMaterial: QUILocale.get(lg, 'product.fields.attributeList.labelTypeOptionMaterial'),
                    labelIsImageAttribute: QUILocale.get(lg, 'product.fields.attributeList.labelIsImageAttribute'),

                    isUsedForVariantGeneration: QUILocale.get(
                        lg,
                        'product.fields.attributeList.isUsedForVariantGeneration'
                    ),
                    isUsedForVariantGenerationDescription: QUILocale.get(
                        lg,
                        'product.fields.attributeList.isUsedForVariantGenerationDescription'
                    )

                })
            }).inject(this.$Elm, 'top');

            this.$GenerateTags = this.$PriceCalc.getElement('[name="generate_tags"]');
            this.$EntriesType = this.$PriceCalc.getElement('[name="entries_type"]');
            this.$IsImageAttribute = this.$PriceCalc.getElement('[name="is_image_attribute"]');
            this.$ExcludeVariantGen = this.$PriceCalc.getElement('[name="exclude_from_variant_generation"]');

            this.refresh();
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function(self, Node) {
            this.$Input = Node;
            this.$Elm = this.create();

            let data = {},
                result = [];

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ('entries' in data) {
                    for (let i = 0, len = data.entries.length; i < len; i++) {
                        if (!('title' in data.entries[i])) {
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

            if ('generate_tags' in data) {
                this.$GenerateTags.checked = data.generate_tags;
            } else {
                this.$GenerateTags.checked = false;
            }

            if ('entries_type' in data) {
                this.$EntriesType.value = data.entries_type;
            }

            if ('is_image_attribute' in data) {
                this.$IsImageAttribute.checked = data.is_image_attribute;
            } else {
                this.$IsImageAttribute.checked = false;
            }

            if ('is_image_attribute' in data) {
                this.$ExcludeVariantGen.checked = data.exclude_from_variant_generation;
            } else {
                this.$ExcludeVariantGen.checked = false;
            }

            this.$GenerateTags.addEvent('change', this.update);
            this.$EntriesType.addEvent('change', this.update);
            this.$IsImageAttribute.addEvent('change', this.update);
            this.$ExcludeVariantGen.addEvent('change', this.update);
        },

        /**
         * reset the buttons from the grid
         * disable or enable the buttons dependent on selected indices
         */
        $buttonReset: function() {
            const selected = this.$Grid.getSelectedIndices(),
                buttons = this.$Grid.getButtons();

            const Up = buttons.filter(function(Button) {
                return Button.getAttribute('name') === 'up';
            })[0];

            const Down = buttons.filter(function(Button) {
                return Button.getAttribute('name') === 'down';
            })[0];

            const Edit = buttons.filter(function(Button) {
                return Button.getAttribute('name') === 'edit';
            })[0];

            const Delete = buttons.filter(function(Button) {
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
        refresh: function() {
            let i, len, entry, langTitle, Selected;
            const data = [];

            const currentLang = QUILocale.getCurrent();

            const IsSelected = new Element('span', {
                'class': 'fa fa-check'
            });

            const IsNotSelected = new Element('span', {
                'class': 'fa fa-minus'
            });

            for (i = 0, len = this.$data.length; i < len; i++) {
                entry = this.$data[i];

                if (!('title' in entry)) {
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

                // Image
                let imagePreview = '';

                if ('image' in entry && entry.image) {
                    imagePreview = new Element('img', {
                        'class': 'quiqqer-products-AttributeGroupSettings-img-preview',
                        src: entry.image
                    });
                }

                data.push({
                    selected: Selected,
                    title: langTitle,
                    valueId: entry.valueId,
                    imagePreview: imagePreview
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
        openAddDialog: function() {
            const self = this;

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.attributeGroup.create.add.window.title'),
                icon: 'fa fa-plus',
                texticon: false,
                maxHeight: 400,
                maxWidth: 600,
                autoclose: false,
                events: {
                    onOpen: function(Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title: QUILocale.get('quiqqer/system', 'title'),
                            valueId: QUILocale.get(lg, 'fields.control.attributeGroup.create.valueId'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.attributeGroup.create.selected'),
                            labelImage: QUILocale.get(lg, 'fields.control.attributeGroup.create.labelImage')
                        }));

                        Win.Loader.show();

                        QUI.parse(Win.getContent()).then(() => {
                            Win.Loader.hide();
                        });
                    },
                    onSubmit: function(Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        if (Form.elements.valueId.value === '') {
                            Form.elements.valueId.focus();
                            Form.reportValidity();
                            return;
                        }

                        const edit = self.add(
                            Title.getData(),
                            Form.elements.valueId.value,
                            Form.elements.selected.checked,
                            Form.elements.image.value ? Form.elements.image.value : false
                        );

                        if (edit) {
                            Win.close();
                        } else {
                            QUI.getMessageHandler().then(function(MH) {
                                MH.addError(QUILocale.get(lg, 'fields.control.attributeGroup.create.already.exist'));
                            });
                        }
                    }
                }
            }).open();
        },

        /**
         * opens the edit dialog
         *
         * @param {Number} index - row index
         */
        openEditDialog: function(index) {
            if (typeof index === 'undefined') {
                return;
            }

            if (typeof this.$data[index] === 'undefined') {
                return;
            }

            const self = this,
                data = this.$data[index];

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.attributeGroup.edit.window.title'),
                icon: 'fa fa-edit',
                maxHeight: 400,
                maxWidth: 600,
                autoclose: false,
                events: {
                    onOpen: function(Win) {
                        Win.getContent().set('html', Mustache.render(templateCreate, {
                            title: QUILocale.get('quiqqer/system', 'title'),
                            valueId: QUILocale.get(lg, 'fields.control.attributeGroup.create.valueId'),
                            selectedTitle: QUILocale.get(lg, 'fields.control.attributeGroup.create.selected'),
                            labelImage: QUILocale.get(lg, 'fields.control.attributeGroup.create.labelImage')
                        }));

                        const Form = Win.getContent().getElement('form');

                        Form.elements.title.value = JSON.encode(data.title);
                        Form.elements.valueId.value = data.valueId;
                        Form.elements.selected.checked = data.selected;

                        if ('image' in data && data.image) {
                            Form.elements.image.value = data.image;
                        }

                        Win.Loader.show();

                        QUI.parse(Win.getContent()).then(() => {
                            Win.Loader.hide();
                        });
                    },
                    onSubmit: function(Win) {
                        const Form = Win.getContent().getElement('form'),
                            Title = QUI.Controls.getById(
                                Form.elements.title.get('data-quiid')
                            );

                        if (Form.elements.valueId.value === '') {
                            Form.elements.valueId.focus();
                            Form.reportValidity();
                            return;
                        }

                        const edit = self.edit(
                            index,
                            Title.getData(),
                            Form.elements.valueId.value,
                            Form.elements.selected.checked,
                            Form.elements.image.value ? Form.elements.image.value : false
                        );

                        if (edit) {
                            Win.close();
                        } else {
                            QUI.getMessageHandler().then(function(MH) {
                                MH.addError(QUILocale.get(lg, 'fields.control.attributeGroup.create.already.exist'));
                            });
                        }
                    }
                }
            }).open();
        },

        /**
         * open remove dialog
         */
        openRemoveDialog: function() {
            const self = this,
                data = this.$Grid.getSelectedData(),
                indices = this.$Grid.getSelectedIndices();

            const titles = data.map(function(Entry) {
                return Entry.title;
            });

            if (!titles.length) {
                return;
            }

            new QUIConfirm({
                title: QUILocale.get(lg, 'fields.control.attributeGroup.remove.window.title'),
                icon: 'fa fa-trash',
                texticon: 'fa fa-trash',
                text: QUILocale.get(lg, 'fields.control.attributeGroup.remove.window.text'),
                information: titles.join(','),
                maxHeight: 300,
                maxWidth: 450,
                events: {
                    onSubmit: function() {
                        self.remove(indices);
                    }
                }
            }).open();
        },

        /**
         * entry move up
         */
        $moveup: function() {
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
        $movedown: function() {
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
        update: function() {
            this.$Input.value = JSON.encode({
                entries: this.$data,
                generate_tags: this.$GenerateTags.checked,
                entries_type: this.$EntriesType.value,
                is_image_attribute: this.$IsImageAttribute.checked,
                exclude_from_variant_generation: this.$ExcludeVariantGen.checked
            });
        },

        /**
         * Add an entry
         *
         * @param {Object|String} title
         * @param {String} valueId
         * @param {Boolean}  [selected]
         * @param {String} [image]
         */
        add: function(title, valueId, selected, image) {
            selected = selected || false;
            image = image || false;
            valueId = valueId.trim();

            for (let i = 0, len = this.$data.length; i < len; i++) {
                if (this.$data[i].valueId === valueId) {
                    return false;
                }
            }

            this.$data.push({
                title: title,
                valueId: valueId,
                selected: selected,
                image: image
            });

            this.refresh();
            this.update();

            return true;
        },

        /**
         * Remove entries
         *
         * @param {Number|Array} index - Row number(s)
         */
        remove: function(index) {
            if (!this.$Grid) {
                return;
            }

            const newData = [];

            const mustBeDeleted = function(wanted) {
                if ((typeOf(index) === 'string' || typeOf(index) === 'number') && index === wanted) {
                    return true;
                }

                if (typeOf(index) === 'array') {
                    for (let i = 0, len = index.length; i < len; i++) {
                        if (index[i] === wanted) {
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
         * Edit an entry
         *
         * @param {Number} index
         * @param {Object|String} title
         * @param {String} valueId
         * @param {Boolean} [selected]
         * @param {String} [image]
         */
        edit: function(index, title, valueId, selected, image) {
            valueId = valueId.trim();

            let entryFound = 0;

            for (let i = 0, len = this.$data.length; i < len; i++) {
                if (this.$data[i].valueId == valueId) { // == because 1 and "1" has to be checked
                    entryFound++;
                }
            }

            if (entryFound > 1) {
                return false;
            }

            if (entryFound === 1 && this.$data[index].valueId != valueId) {
                return false;
            }

            this.$data[index] = {
                title: title,
                valueId: valueId,
                selected: selected || false,
                image: image || false
            };

            this.refresh();
            this.update();

            return true;
        }
    });
});
