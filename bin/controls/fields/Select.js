/**
 * Makes an input field to a field selection field
 *
 * @module package/quiqqer/products/bin/controls/fields/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAddField [ this, id ]
 * @event onChange [ this ]
 */
define('package/quiqqer/products/bin/controls/fields/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/controls/fields/SelectItem',
    'package/quiqqer/products/bin/Fields',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/Select.css'

], function (QUI, QUIControl, QUIButton, QUIElementUtils, SelectItem, Fields, Ajax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    /**
     * @class package/quiqqer/products/bin/controls/fields/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/Select',

        Binds: [
            'close',
            'fireSearch',
            'update',

            '$onSelectDestroy',
            '$onInputFocus',
            '$onImport',
            '$onInject'
        ],

        options: {
            disabled: false,
            max     : false, // max entries
            multiple: true,  // select more than one entry?
            name    : '',    // string
            styles  : false, // object
            label   : false, // text string or a <label> DOMNode Element

            // search function function(value, params) @return Promise;
            // resolve( [fieldData, fieldData, fieldData] )
            search: false,

            showsearchableonly: false       // only show fields that are searchable
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
            this.$loaded = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#create
         * @return {HTMLElement|Element} The main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'qui-fields-list',
                'data-quiid': this.getId()
            });

            if (this.getAttribute('multiple')) {
                this.$Elm.addClass('qui-fields-list-multiple');
            }


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
                'class': 'qui-fields-list-list'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'qui-fields-list-search',
                placeholder: QUILocale.get(lg, 'control.select.search.field.placeholder'),
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
                            'package/quiqqer/products/bin/controls/fields/search/Window'
                        ], function (Window) {
                            new Window({
                                autoclose         : true,
                                multiple          : self.getAttribute('multiple'),
                                search            : self.getAttribute('search'),
                                showsearchableonly: self.getAttribute('showsearchableonly'),
                                events            : {
                                    onSubmit: function (Win, fieldIds) {
                                        self.addFields(fieldIds);
                                    }
                                }
                            }).open();

                            Btn.setAttribute('icon', 'fa fa-search');
                        });
                    }
                }
            }).inject(this.$Elm);

            this.$DropDown = new Element('div', {
                'class': 'qui-fields-list-dropdown',
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

            if (parseInt(this.getAttribute('max')) === 1) {
                this.$Search.setStyle('display', 'none');

                this.$List.setStyles({
                    border: 'none',
                    height: 31,
                    width : 'calc(100% - 50px)'
                });
            }


            // load values
            if (this.$Input.value || this.$Input.value !== '') {
                this.$Input.value.split(',').each(function (fieldId) {
                    self.addFields(fieldId);
                });
            }

            if (this.getAttribute('disabled')) {
                this.disable();
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

            if (this.$Input.value !== '' &&
                this.$Input.value !== 0 &&
                this.$Input.value !== "0"
            ) {
                var fields = this.$Input.value.split(',');

                for (var i = 0, len = fields.length; i < len; i++) {
                    this.addField(fields[i]);
                }
            }

            this.$loaded = true;
        },

        /**
         * event: on import
         */
        $onInject: function () {
            this.$loaded = true;
        },

        /**
         * Disable the control
         */
        disable: function () {
            this.setAttribute('disabled', true);
            this.$Elm.addClass('disabled');
            this.$SearchButton.disable();
            this.$Search.set('disabled', true);
        },

        /**
         * Enable the control
         */
        enable: function () {
            this.setAttribute('disabled', false);
            this.$Elm.removeClass('disabled');
            this.$SearchButton.enable();
            this.$Search.set('disabled', false);
        },

        /**
         * Clear - Remove all fields
         *
         * @return {Object} this (package/quiqqer/products/bin/controls/field/Select)
         */
        clear: function () {
            this.$List.set('html', '');
            this.$values = [];

            this.fireEvent('clear', [this]);
            this.$refreshValues();

            return this;
        },

        /**
         * Remove a field from the list
         *
         * @param {Number} fieldId
         */
        removeField: function (fieldId) {
            var newValues = [];

            for (var i = 0, len = this.$values.length; i < len; i++) {
                if (this.$values[i] != fieldId) {
                    newValues.push(this.$values[i]);
                }
            }

            this.$values = newValues;

            QUI.Controls.getControlsInElement(this.$List).each(function (Field) {
                if (Field.getAttribute('id') == fieldId) {
                    Field.destroy();
                }
            });

            this.fireEvent('removeField', [this, fieldId]);
            this.$refreshValues();

            return this;
        },

        /**
         * fire the search
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#fireSearch
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
                    left   : this.$Search.getPosition().x,
                    zIndex : QUIElementUtils.getComputedZIndex(this.$Input)
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#cancelSearch
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#close
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
         * trigger a users search and open a field dropdown for selection
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#search
         */
        search: function () {
            var self  = this,
                value = this.$Search.value;

            var Search = Promise.resolve(false);

            if (typeof this.getAttribute('search') === 'function') {
                Search = this.getAttribute('search')(value, {
                    order: 'ASC',
                    limit: 5
                });

            } else {
                Search = Fields.search({
                    order: 'ASC',
                    limit: 5
                }, {
                    name: value,
                    type: value
                });
            }

            Search.then(function (result) {
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

                    if (!Elm.hasClass('qui-fields-list-dropdown-entry')) {
                        Elm = Elm.getParent('.qui-fields-list-dropdown-entry');
                    }

                    self.addField(Elm.get('data-id'));
                };

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.qui-fields-list-dropdown-entry-hover'
                    ).removeClass(
                        'qui-fields-list-dropdown-entry-hover'
                    );

                    this.addClass('qui-fields-list-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = result.length; i < len; i++) {

                    entry = result[i];
                    id    = entry.id;

                    nam = '#' + id + ' - ';
                    nam = nam + entry.title;

                    if (value) {
                        nam = nam.toString().replace(
                            new RegExp('(' + value + ')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }

                    Entry = new Element('div', {
                        html     : '<span class="fa fa-percent"></span>' +
                            '<span>' + nam + ' (' + id + ')</span>',
                        'class'  : 'box-sizing qui-fields-list-dropdown-entry',
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
         * @method package/quiqqer/products/bin/controls/fields/Select#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (package/quiqqer/products/bin/controls/field/Select)
         */
        addField: function (id) {
            if (id === '' || !id) {
                return this;
            }

            var max = parseInt(this.getAttribute('max'));

            if (max === 1) {
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
                id    : id,
                events: {
                    onDestroy: this.$onSelectDestroy
                }
            }).inject(this.$List);

            this.$values.push(id);

            this.fireEvent('addField', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * same as addField, only a array can be passed
         *
         * @param {Array} ids
         * @return {Object} this (package/quiqqer/products/bin/controls/field/Select)
         */
        addFields: function (ids) {
            if (typeOf(ids) !== 'array') {
                return this;
            }

            var max = parseInt(this.getAttribute('max'));

            if (max === 1) {
                // max = 1 -> overwrites the old
                this.$values = [];

                QUI.Controls.getControlsInElement(this.$List).each(function (Entry) {
                    Entry.destroy();
                });
            }

            ids.each(function (id) {
                if (id === '' || !id) {
                    return;
                }

                new SelectItem({
                    id    : id,
                    events: {
                        onDestroy: this.$onSelectDestroy
                    }
                }).inject(this.$List);

                this.$values.push(id);
            }.bind(this));

            this.fireEvent('addFields', [this, ids]);
            this.$refreshValues();

            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#up
         * @return {Object} this (package/quiqqer/products/bin/controls/fields/Select)
         */
        up: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-fields-list-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'qui-fields-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-fields-list-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'qui-fields-list-dropdown-entry-hover'
            );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#down
         * @return {Object} this (package/quiqqer/products/bin/controls/fields/Select)
         */
        down: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-fields-list-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'qui-fields-list-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-fields-list-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'qui-fields-list-dropdown-entry-hover'
            );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#submit
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement(
                '.qui-fields-list-dropdown-entry-hover'
            );

            if (Active) {
                this.addField(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#focus
         * @return {Object} this (package/quiqqer/products/bin/controls/fields/Select)
         */
        focus: function () {
            if (parseInt(this.getAttribute('max')) === 1) {
                this.$SearchButton.click();
            }

            if (this.$Search) {
                this.$Search.focus();
            }

            return this;
        },

        /**
         * Write the ids to the real input field
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#$refreshValues
         */
        $refreshValues: function () {
            this.$Input.value = this.$values.join(',');

            if (this.$loaded === false) {
                return;
            }

            this.$Input.fireEvent('change', [{
                target: this.$Input
            }]);

            this.fireEvent('change', [this]);
        },

        /**
         * event : if a user or a groupd would be destroyed
         *
         * @method package/quiqqer/products/bin/controls/fields/Select#$onSelectDestroy
         * @param {Object} Item - package/quiqqer/products/bin/controls/fields/SelectItem
         */
        $onSelectDestroy: function (Item) {
            this.$values = this.$values.erase(Item.getAttribute('id'));
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
