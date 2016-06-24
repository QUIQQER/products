/**
 * Makes an input field to a user selection field
 *
 * @module package/quiqqer/products/bin/controls/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require package/quiqqer/products/bin/controls/ProductDisplay
 * @require Ajax
 * @require Locale
 *
 * @event onAddProduct [ this, id ]
 * @event onChange [ this ]
 */
define('package/quiqqer/products/bin/controls/products/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/controls/products/SelectItem',
    'package/quiqqer/products/bin/classes/Products',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/products/Select.css'

], function (QUI, QUIControl, QUIButton, ProductItem, Handler, Ajax, Locale) {
    "use strict";

    var lg       = 'quiqqer/products';
    var Products = new Handler();

    /**
     * @class package/quiqqer/products/bin/controls/products/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/Select',

        Binds: [
            'close',
            'fireSearch',
            'update',

            '$calcDropDownPosition',
            '$onProductDestroy',
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
                onImport: this.$onImport
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @method package/quiqqer/products/bin/controls/products/Select#create
         * @return {HTMLElement} The main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'qui-quiqqer-products-select',
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
                    display : 'none',
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
                'class': 'qui-quiqqer-products-list'
            }).inject(this.$Elm);

            this.$Icon = new Element('span', {
                'class': 'qui-quiqqer-products-list-search-loader',
                html   : '<span class="fa fa-shopping-bag"></span>'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'qui-quiqqer-products-list-search',
                placeholder: Locale.get(lg, 'control.select.search.placeholder'),
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
                            'package/quiqqer/products/bin/controls/products/search/Window'
                        ], function (Search) {
                            new Search({
                                events: {
                                    onSubmit: function (Win, values) {
                                        for (var i = 0, len = values.length; i < len; i++) {
                                            self.addProduct(values[i]);
                                        }
                                    }
                                }
                            }).open();

                            Btn.setAttribute('icon', 'fa fa-search');
                        });
                    }
                }
            }).inject(this.$Elm);

            this.$DropDown = new Element('div', {
                'class': 'qui-quiqqer-products-list-dropdown',
                styles : {
                    display: 'none',
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

                if (Label.get('data-desc') && Label.get('data-desc') != '&nbsp;') {
                    new Element('div', {
                        'class': 'description',
                        html   : Label.get('data-desc'),
                        styles : {
                            marginBottom: 10
                        }
                    }).inject(Label, 'after');
                }
            }


            // load values
            if (this.$Input.value || this.$Input.value !== '') {
                this.addProduct(this.$Input.value);
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
         * fire the search
         *
         * @method package/quiqqer/products/bin/controls/products/Select#fireSearch
         */
        fireSearch: function () {
            if (this.$Search.value === '') {
                return this.close();
            }

            this.cancelSearch();

            this.$Icon.set('html', '<span class="fa fa-spinner fa-spin"></span>');

            this.$DropDown.set({
                styles: {
                    display: '',
                    left   : this.getElm().getPosition().x + 2,
                    width  : this.getElm().getSize().x - 4
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         *
         * @method package/quiqqer/products/bin/controls/products/Select#cancelSearch
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         *
         * @method package/quiqqer/products/bin/controls/products/Select#close
         */
        close: function () {
            this.cancelSearch();

            this.$Icon.set('html', '<span class="fa fa-shopping-bag"></span>');
            this.$DropDown.setStyle('display', 'none');
            this.$Search.value = '';
        },

        /**
         * Return the value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * trigger a users search and open a product dropdown for selection
         *
         * @method package/quiqqer/products/bin/controls/products/Select#search
         */
        search: function () {
            var value = this.$Search.value;

            Products.search({
                freetext: value
            }).then(function (result) {
                var i, len, nam, Entry,
                    func_mousedown, func_mouseover,

                    data     = result,
                    DropDown = this.$DropDown;

                DropDown.set('html', '');

                this.$Icon.set('html', '<span class="fa fa-shopping-bag"></span>');

                if (!data.length) {
                    new Element('div', {
                        html  : Locale.get('quiqqer/system', 'no.results'),
                        styles: {
                            'float': 'left',
                            'clear': 'both',
                            padding: 5,
                            margin : 5
                        }
                    }).inject(DropDown);

                    this.$calcDropDownPosition();
                    return;
                }

                // events
                func_mousedown = function (event) {
                    var Target = event.target;

                    if (!Target.hasClass('qui-quiqqer-products-list-dropdown-entry')) {
                        Target = Target.getParent('.qui-quiqqer-products-list-dropdown-entry');
                    }

                    this.addProduct(Target.get('data-id'));

                }.bind(this);

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.qui-quiqqer-products-list-dropdown-entry-hover'
                    ).removeClass(
                        'qui-quiqqer-products-list-dropdown-entry-hover'
                    );

                    this.addClass('qui-quiqqer-products-list-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = data.length; i < len; i++) {
                    nam = data[i].title;

                    if (value) {
                        nam = nam.toString().replace(
                            new RegExp('(' + value + ')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }

                    Entry = new Element('div', {
                        html     : '<span>' + nam + ' (' + data[i].id + ')</span>',
                        'class'  : 'qui-quiqqer-products-list-dropdown-entry',
                        'data-id': data[i].id,
                        events   : {
                            mousedown : func_mousedown,
                            mouseenter: func_mouseover
                        }
                    }).inject(DropDown);


                    new Element('span', {
                        'class': 'fa fa-shopping-bag',
                        styles : {
                            marginRight: 5
                        }
                    }).inject(Entry, 'top');
                }

                this.$calcDropDownPosition();

            }.bind(this));
        },

        /**
         * Add a user to the input
         *
         * @method package/quiqqer/products/bin/controls/products/Select#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (package/quiqqer/products/bin/controls/Select)
         */
        addProduct: function (id) {
            new ProductItem({
                id    : id,
                events: {
                    onDestroy: this.$onProductDestroy
                }
            }).inject(this.$List);

            this.$values.push(id);

            this.fireEvent('addProduct', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method package/quiqqer/products/bin/controls/products/Select#up
         * @return {Object} this (package/quiqqer/products/bin/controls/products/Select)
         */
        up: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-quiqqer-products-list-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'qui-quiqqer-products-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-quiqqer-products-list-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'qui-quiqqer-products-list-dropdown-entry-hover'
            );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method package/quiqqer/products/bin/controls/products/Select#down
         * @return {Object} this (package/quiqqer/products/bin/controls/products/Select)
         */
        down: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-quiqqer-products-list-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'qui-quiqqer-products-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-quiqqer-products-list-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'qui-quiqqer-products-list-dropdown-entry-hover'
            );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method package/quiqqer/products/bin/controls/products/Select#submit
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement(
                '.qui-quiqqer-products-list-dropdown-entry-hover'
            );

            if (Active) {
                this.addProduct(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method package/quiqqer/products/bin/controls/products/Select#focus
         * @return {Object} this (package/quiqqer/products/bin/controls/products/Select)
         */
        focus: function () {
            if (this.$Search) {
                this.$Search.focus();
            }

            return this;
        },

        /**
         * Write the ids to the real input field
         *
         * @method package/quiqqer/products/bin/controls/products/Select#$refreshValues
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
         * @method package/quiqqer/products/bin/controls/products/Select#$onProductDestroy
         * @param {Object} Item - package/quiqqer/products/bin/controls/products/SelectItem
         */
        $onProductDestroy: function (Item) {
            this.$values = this.$values.erase(
                Item.getAttribute('id')
            );

            this.$refreshValues();
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
         * Calculate the dropdown position
         */
        $calcDropDownPosition: function () {
            var size      = this.$DropDown.getSize();
            var searchPos = this.$Search.getPosition();

            this.$DropDown.setStyles({
                top: searchPos.y - size.y
            });
        }
    });
});
