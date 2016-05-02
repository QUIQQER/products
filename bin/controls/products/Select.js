/**
 * Makes an input field to a user selection field
 *
 * @module package/quiqqer/discount/bin/controls/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require package/quiqqer/discount/bin/controls/DiscountDisplay
 * @require Ajax
 * @require Locale
 *
 * @event onAddDiscount [ this, id ]
 * @event onChange [ this ]
 */
define('package/quiqqer/products/bin/controls/products/Select', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/controls/products/SelectItem',
    'package/quiqqer/products/bin/classes/Products',
    'Ajax',
    'Locale',

    'css!package/quiqqer/discount/bin/controls/Select.css'

], function (QUIControl, QUIButton, DiscountDisplay, Handler, Ajax, Locale) {
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

            this.$Elm = new Element('div', {
                'class'     : 'qui-discount-list',
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
                'class': 'qui-discount-list-list'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'qui-discount-list-search',
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
                                        console.log(values);
                                    }
                                }
                            }).open();

                            Btn.setAttribute('icon', 'fa fa-search');
                        });
                    }
                }
            }).inject(this.$Elm);

            this.$DropDown = new Element('div', {
                'class': 'qui-discount-list-dropdown',
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

            this.$DropDown.set({
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    display: '',
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left   : this.$Search.getPosition().x
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
         * trigger a users search and open a discount dropdown for selection
         *
         * @method package/quiqqer/products/bin/controls/products/Select#search
         */
        search: function () {

            var value = this.$Search.value;

            Discounts.search({
                order: 'ASC',
                limit: 5
            }).then(function (result) {
                console.log(result);
            });

            return;

            Ajax.get('ajax_usersgroups_search', function (result, Request) {
                var i, len, nam, type, Entry,
                    func_mousedown, func_mouseover,

                    data     = result.data,
                    value    = Request.getAttribute('value'),
                    Elm      = Request.getAttribute('Elm'),
                    DropDown = Elm.$DropDown;


                DropDown.set('html', '');

                if (!data.length) {
                    new Element('div', {
                        html  : Locale.get('quiqqer/system', 'usersAndGroups.no.results'),
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

                    if (!Elm.hasClass('qui-discount-list-dropdown-entry')) {
                        Elm = Elm.getParent('.qui-discount-list-dropdown-entry');
                    }

                    this.add(
                        Elm.get('data-id'),
                        Elm.get('data-type')
                    );

                }.bind(Elm);

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.qui-discount-list-dropdown-entry-hover'
                    ).removeClass(
                        'qui-discount-list-dropdown-entry-hover'
                    );

                    this.addClass('qui-discount-list-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = data.length; i < len; i++) {
                    nam = data[i].username || data[i].name;

                    if (value) {
                        nam = nam.toString().replace(
                            new RegExp('(' + value + ')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }


                    type = 'group';

                    if (data[i].username) {
                        type = 'user';
                    }

                    Entry = new Element('div', {
                        html       : '<span>' + nam + ' (' + data[i].id + ')</span>',
                        'class'    : 'box-sizing qui-discount-list-dropdown-entry',
                        'data-id'  : data[i].id,
                        'data-name': data[i].username || data[i].name,
                        'data-type': type,
                        events     : {
                            mousedown : func_mousedown,
                            mouseenter: func_mouseover
                        }
                    }).inject(DropDown);

                    if (type == 'group') {
                        new Element('span', {
                            'class': 'fa fa-group',
                            styles : {
                                marginRight: 5
                            }
                        }).inject(Entry, 'top');
                    } else {
                        new Element('span', {
                            'class': 'fa fa-user',
                            styles : {
                                marginRight: 5
                            }
                        }).inject(Entry, 'top');
                    }

                }
            }, {
                Elm   : this,
                value : this.$Search.value,
                params: JSON.encode({
                    order         : 'ASC',
                    limit         : 5,
                    page          : 1,
                    search        : true,
                    searchSettings: {
                        userSearchString: this.$Search.value
                    }
                })
            });
        },

        /**
         * Add a user to the input
         *
         * @method package/quiqqer/products/bin/controls/products/Select#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (package/quiqqer/discount/bin/controls/Select)
         */
        addProduct: function (id) {
            new ProductItem(id, {
                events: {
                    onDestroy: this.$onProductDestroy
                }
            }).inject(this.$List);

            this.$values.push(id);

            this.fireEvent('addDiscount', [this, id]);
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
                '.qui-discount-list-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'qui-discount-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-discount-list-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'qui-discount-list-dropdown-entry-hover'
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
                '.qui-discount-list-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'qui-discount-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-discount-list-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'qui-discount-list-dropdown-entry-hover'
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
                '.qui-discount-list-dropdown-entry-hover'
            );

            if (Active) {
                this.addDiscount(Active.get('data-id'));
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
         * @method package/quiqqer/products/bin/controls/products/Select#$onDiscountDestroy
         * @param {Object} Item - package/quiqqer/products/bin/controls/products/SelectItem
         */
        $onProductDestroy: function (Item) {
            this.$values = this.$values.erase(
                Item.getDiscount().getId()
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
        }
    });
});
