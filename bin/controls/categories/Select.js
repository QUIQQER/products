/**
 * Makes an input field to a user selection field
 *
 * @module package/quiqqer/products/bin/controls/categories/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAddCategory [ this, id ]
 * @event onChange [ this ]
 * @event onRemoveCategory [ this, categoryId ]
 * @event onClear [ this ]
 *
 * @todo locale
 * @todo #locale
 */
define('package/quiqqer/products/bin/controls/categories/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/controls/categories/SelectItem',
    'package/quiqqer/products/bin/classes/Categories',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/categories/Select.css'

], function (QUI, QUIControl, QUIButton, QUIConfirm, QUIElementUtils, SelectItem, Handler, Ajax, QUILocale) {
    "use strict";

    var lg         = 'quiqqer/products';
    var Categories = new Handler();

    /**
     * @class controls/usersAndGroups/Input
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/categories/Select',

        Binds: [
            'close',
            'fireSearch',
            'update',

            '$showCreateCategoryDialog',
            '$onCategoryDestroy',
            '$onInputFocus',
            '$onImport'
        ],

        options: {
            max     : false, // max entries
            multiple: true,  // select more than one entry?
            name    : '',    // string
            styles  : false, // object
            label   : false  // text string or a <label> DOMNode Element
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input    = Input || null;
            this.$Elm      = null;
            this.$List     = null;
            this.$Search   = null;
            this.$DropDown = null;

            this.$SearchButton = null;

            this.$search = false;
            this.$values = [];

            this.addEvents({
                onImport      : this.$onImport,
                onSetAttribute: function (attr) {
                    if (attr === 'Site' && !this.$values.length) {
                        this.$showCreateButton();
                    }
                }.bind(this)
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#create
         * @return {HTMLElement} The main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'qui-products-categories-list',
                'data-quiid': this.getId()
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Input.set({
                styles: {
                    opacity : 0,
                    position: 'absolute',
                    zIndex  : 1,
                    left    : 5,
                    top     : 5,
                    cursor  : 'pointer'
                },
                events: {
                    focus: this.$onInputFocus
                }
            });


            this.$List = new Element('div', {
                'class': 'qui-products-categories-list-list'
            }).inject(this.$Elm);

            this.$SearchIcon = new Element('span', {
                'class': 'qui-products-categories-list-searchIcon',
                html   : '<span class="fa fa-shopping-basket"></span>'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'qui-products-categories-list-search',
                placeholder: QUILocale.get(lg, 'control.select.search.categories.placeholder'),
                events     : {
                    keyup: function (event) {
                        if (event.key === 'down') {
                            this.down();
                            return;
                        }

                        if (event.key === 'up') {
                            this.up();
                            return;
                        }

                        if (event.key === 'enter') {
                            this.submit();
                            return;
                        }

                        this.fireSearch();
                    }.bind(this),

                    blur : this.close,
                    focus: this.fireSearch
                }
            }).inject(this.$Elm);

            this.$SearchButton = new QUIButton({
                icon  : 'fa fa-search',
                styles: {
                    width: 50
                },
                events: {
                    onClick: function (Btn) {
                        Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

                        require([
                            'package/quiqqer/products/bin/controls/categories/search/Window'
                        ], function (Window) {
                            new Window({
                                autoclose: true,
                                multiple : self.getAttribute('multiple'),
                                events   : {
                                    onSubmit: function (Win, categorieIds) {
                                        categorieIds.each(function (categorieId) {
                                            self.addCategory(categorieId);
                                        });
                                    }
                                }
                            }).open();

                            Btn.setAttribute('icon', 'fa fa-search');
                        });

                    }
                }
            }).inject(this.$Elm);


            this.$CreateButton = new QUIButton({
                icon  : 'fa fa-sitemap',
                styles: {
                    display: 'none',
                    width  : 50
                },
                alt   : 'Kategorie erstellen',
                events: {
                    onClick: this.$showCreateCategoryDialog
                }
            }).inject(this.$Elm);


            this.$DropDown = new Element('div', {
                'class': 'qui-products-categories-list-dropdown',
                styles : {
                    display: 'none',
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left   : this.$Search.getPosition().x
                }
            }).inject(document.body);

            if (this.getAttribute('label')) {
                var Label = this.getAttribute('label');

                if (typeof this.getAttribute('label').nodeName === 'undefined') {
                    Label = new Element('label', {
                        html: this.getAttribute('label')
                    });
                }

                Label.inject(this.$Elm, 'top');

                if (Label.get('data-desc') && Label.get('data-desc') !== '&nbsp;') {
                    new Element('div', {
                        'class': 'description',
                        html   : Label.get('data-desc'),
                        styles : {
                            marginBottom: 10
                        }
                    }).inject(Label, 'after');
                }
            }

            if (this.getAttribute('max') == 1) {
                this.$Search.setStyle('display', 'none');

                this.$List.setStyles({
                    border: 'none',
                    height: 31,
                    width : 'calc(100% - 50px)'
                });
            }

            // load values
            if (this.$Input.value || this.$Input.value !== '') {
                this.$Input.value.split(',').each(function (categoryId) {
                    self.addCategory(categoryId);
                });
            }

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onImport: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                this.$Input = Elm;
            }

            this.$Elm = null;
            this.create();
        },

        /**
         * Return the current value, list of categories
         *
         * @return {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * fire the search
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#fireSearch
         */
        fireSearch: function () {
            if (this.$Search.value === '') {
                return this.close();
            }

            this.cancelSearch();

            this.$DropDown.set({
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    display: '',
                    left   : this.$Search.getPosition().x,
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    width  : this.$Search.getSize().x,
                    zIndex : QUIElementUtils.getComputedZIndex(this.$Input)
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#cancelSearch
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#close
         */
        close: function () {
            this.cancelSearch();
            this.$DropDown.setStyle('display', 'none');
            this.$Search.value = '';
        },

        /**
         * Clear - Remove all fields
         *
         * @return {Object} this (package/quiqqer/products/bin/controls/categories/Select)
         */
        clear: function () {
            this.$List.set('html', '');
            this.$values = [];

            this.fireEvent('clear', [this]);
            this.$refreshValues();

            return this;
        },

        /**
         * Remove a category from the list
         *
         * @param {Number} categoryId
         */
        removeCategory: function (categoryId) {
            var newValues = [];

            for (var i = 0, len = this.$values.length; i < len; i++) {
                if (this.$values[i] != categoryId) {
                    newValues.push(this.$values[i]);
                }
            }

            this.$values = newValues;

            QUI.Controls.getControlsInElement(this.$List).each(function (Category) {
                if (Category.getAttribute('id') == categoryId) {
                    Category.destroy();
                }
            });

            this.fireEvent('removeCategory', [this, categoryId]);
            this.$refreshValues();

            return this;
        },

        /**
         * trigger a users search and open a category dropdown for selection
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#search
         */
        search: function () {

            var self  = this,
                value = this.$Search.value;

            Categories.search({
                order: 'ASC',
                limit: 5
            }, {
                id    : value,
                fields: value
            }).then(function (result) {

                var i, id, len, nam, entry, Entry,
                    func_mousedown, func_mouseover,

                    DropDown = self.$DropDown;


                DropDown.set('html', '');

                if (!result || !result.length) {
                    new Element('div', {
                        html  : QUILocale.get(lg, 'control.select.no.results'),
                        styles: {
                            'float': 'left',
                            'clear': 'both',
                            padding: 5,
                            margin : 5
                        }
                    }).inject(DropDown);

                    return;
                }

                // events
                func_mousedown = function (event) {
                    var Elm = event.target;

                    if (!Elm.hasClass('qui-products-categories-list-dropdown-entry')) {
                        Elm = Elm.getParent('.qui-products-categories-list-dropdown-entry');
                    }

                    self.addCategory(Elm.get('data-id'));
                };

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.qui-products-categories-list-dropdown-entry-hover'
                    ).removeClass(
                        'qui-products-categories-list-dropdown-entry-hover'
                    );

                    this.addClass('qui-products-categories-list-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = result.length; i < len; i++) {

                    entry = result[i];
                    id    = entry.id;

                    nam = '#' + id + ' - ';
                    nam = nam + QUILocale.get(lg, 'products.category.' + id + '.title');

                    if (value) {
                        nam = nam.toString().replace(
                            new RegExp('(' + value + ')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }

                    Entry = new Element('div', {
                        html     : '<span class="fa fa-sitemap"></span>' +
                            '<span>' + nam + ' (' + id + ')</span>',
                        'class'  : 'box-sizing qui-products-categories-list-dropdown-entry',
                        'data-id': id,
                        events   : {
                            mousedown : func_mousedown,
                            mouseenter: func_mouseover
                        }
                    }).inject(DropDown);
                }
            });
        },

        /**
         * Add a user to the input
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (package/quiqqer/products/bin/controls/categories/Select)
         */
        addCategory: function (id) {
            if (id !== 0 && (!id || id === '')) {
                return this;
            }

            this.$hideCreateButton();

            var max = this.getAttribute('max');

            if (max == 1) {
                // max = 1 -> overwrites the old
                this.$values = [];

                QUI.Controls.getControlsInElement(this.$List).each(function (Entry) {
                    Entry.destroy();
                });
            }

            if (max && this.$values.length > max) {
                return this;
            }

            new SelectItem({
                categoryId: id,
                events    : {
                    onDestroy: this.$onCategoryDestroy
                }
            }).inject(this.$List);

            this.$values.push(id);

            this.fireEvent('addCategory', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#up
         * @return {Object} this (package/quiqqer/products/bin/controls/categories/Select)
         */
        up: function () {
            if (!this.$DropDown) {
                return this;
            }

            if (!this.$DropDown.getFirst()) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-products-categories-list-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'qui-products-categories-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-products-categories-list-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'qui-products-categories-list-dropdown-entry-hover'
            );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#down
         * @return {Object} this (package/quiqqer/products/bin/controls/categories/Select)
         */
        down: function () {
            if (!this.$DropDown) {
                return this;
            }

            if (!this.$DropDown.getFirst()) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-products-categories-list-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'qui-products-categories-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-products-categories-list-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'qui-products-categories-list-dropdown-entry-hover'
            );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#submit
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement(
                '.qui-products-categories-list-dropdown-entry-hover'
            );

            if (Active) {
                this.addCategory(Active.get('data-id'));
            }

            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#focus
         * @return {Object} this (package/quiqqer/products/bin/controls/categories/Select)
         */
        focus: function () {
            if (this.getAttribute('max') == 1) {
                this.$SearchButton.click();
            }

            if (this.$Search) {
                this.$Search.focus();
                return this;
            }

            return this;
        },

        /**
         * Write the ids to the real input field
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#$refreshValues
         */
        $refreshValues: function () {
            this.$Input.value = this.$values.join(',');
            this.$Input.fireEvent('change', [{
                target: this.$Input
            }]);

            this.fireEvent('change', [this]);
        },

        /**
         * event : if a user or a groupd would be destroyed
         *
         * @method package/quiqqer/products/bin/controls/categories/Select#$onCategoryDestroy
         * @param {Object} Item - package/quiqqer/products/bin/controls/categories/Select
         */
        $onCategoryDestroy: function (Item) {
            this.$values = this.$values.erase(
                Item.getAttribute('categoryId')
            );

            this.fireEvent('delete', [this, Item]);
            this.$refreshValues();

            if (!this.$values.length && this.getAttribute('Site')) {
                this.$showCreateButton();
            }
        },

        /**
         * event : on input focus, if the real input field get the focus
         *
         * @param {DOMEvent} event
         */
        $onInputFocus: function (event) {
            if (typeof event !== 'undefined') {
                event.stop();
            }

            this.focus();
        },

        /**
         * Show the create category button
         */
        $showCreateButton: function () {
            this.$CreateButton.getElm().setStyle('display', null);

            if (this.getAttribute('max') == 1) {
                this.$List.setStyles({
                    width: 'calc(100% - 100px)'
                });
            } else {
                this.$List.setStyles({
                    width: 'calc(100% - 50px)'
                });
            }
        },

        /**
         * Hide the create category button
         */
        $hideCreateButton: function () {
            this.$CreateButton.getElm().setStyle('display', 'none');

            if (this.getAttribute('max') == 1) {
                this.$List.setStyles({
                    width: 'calc(100% - 50px)'
                });
            } else {
                this.$List.setStyles({
                    width: '100%'
                });
            }
        },

        /**
         *
         */
        $showCreateCategoryDialog: function () {
            if (!this.getAttribute('Site')) {
                return;
            }

            var self  = this,
                title = this.getAttribute('Site').getAttribute('title');

            self.$CreateButton.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'package/quiqqer/products/bin/controls/categories/search/Window'
            ], function (Window) {
                new Window({
                    autoclose: true,
                    multiple : false,
                    message  : 'Bitte wählen Sie die Übergeordnete Kategorie aus:',
                    events   : {
                        onSubmit: function (Win, categorieIds) {
                            self.$CreateButton.setAttribute('icon', 'fa fa-spinner fa-spin');

                            Ajax.post('package_quiqqer_products_ajax_categories_create', function (category) {
                                self.addCategory(category.id);
                                self.$CreateButton.setAttribute('icon', 'fa fa-sitemap');
                            }, {
                                'package': 'quiqqer/products',
                                parentId : categorieIds[0],
                                params   : JSON.encode({
                                    title: title
                                })
                            });
                        }
                    }
                }).open();

                self.$CreateButton.setAttribute('icon', 'fa fa-sitemap');
            });
        }
    });
});
