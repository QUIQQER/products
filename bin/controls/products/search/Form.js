/**
 * @module package/quiqqer/products/bin/controls/products/search/Form
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte, nur das suchformular
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/grid/Grid
 * @require package/quiqqer/products/bin/Fields
 * @require Ajax
 * @require Locale
 * @require Mustache
 * @require text!package/quiqqer/products/bin/controls/products/search/Form.html
 * @require css!package/quiqqer/products/bin/controls/products/search/Form.css
 *
 * @event onSearchBegin [this]
 */
define('package/quiqqer/products/bin/controls/products/search/Form', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/search/Form.html',
    'css!package/quiqqer/products/bin/controls/products/search/Form.css'

], function (QUI, QUIControl, QUIButton, Grid, Fields, Ajax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            'search',
            '$onSubmit',
            '$onInject'
        ],

        options: {
            searchfields  : {},
            searchbutton  : true,
            sortOn        : false,
            sortBy        : false,
            limit         : false,
            sheet         : 1,
            freeTextSearch: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Container = null;
            this.$loaded    = false;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * Resize
         *
         * @return {Promise}
         */
        resize: function () {
            if (this.$loaded) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                var interval;

                interval = (function () {
                    if (this.$loaded) {
                        clearInterval(interval);
                        resolve();
                    }
                }.bind(this)).periodical(100);
            }.bind(this));
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLFormElement}
         */
        create: function () {
            this.$Elm = new Element('form', {
                'class': 'quiqqer-products-search-form',
                events : {
                    // submit event, because we have no real submit button
                    keyup: function (event) {
                        if (event.key === 'enter') {
                            this.$onSubmit();
                        }
                    }.bind(this)
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            Ajax.get('package_quiqqer_products_ajax_search_backend_getSearchFieldData', function (result) {
                var fieldsIds = result.map(function (Entry) {
                    return Entry.id;
                });

                Fields.getChildren(fieldsIds).then(function (fields) {
                    var i, id, len, fieldId, Field;

                    var fieldFilter = function (Field) {
                        return Field.id == this;
                    };

                    for (i = 0, len = result.length; i < len; i++) {
                        fieldId = result[i].id;
                        Field   = fields.filter(fieldFilter.bind(result[i].id))[0];

                        result[i].fieldTitle = Field.title;
                    }

                    this.$Elm.set({
                        html: Mustache.render(template, {
                            fields                  : result,
                            freeTextSearch          : this.getAttribute('freeTextSearch'),
                            fieldTitleFreeTextSearch: QUILocale.get(lg, 'searchtype.freeTextSearch.title'),
                            text_no_fields          : QUILocale.get(lg, 'searchtypes.empty')
                        })
                    });

                    this.$Container = this.$Elm.getElement(
                        '.quiqqer-products-search-fieldContainer'
                    );

                    QUI.parse(this.$Elm).then(function () {
                        var Field;
                        var controls = QUI.Controls.getControlsInElement(this.$Container);

                        var getControlByFieldById = function (fieldId) {
                            for (var c = 0, len = controls.length; c < len; c++) {
                                if (controls[c].getAttribute('fieldid') === fieldId) {
                                    return controls[c];
                                }
                            }
                            return false;
                        };

                        for (i = 0, len = result.length; i < len; i++) {
                            id = result[i].id;

                            if (i === 0) {
                                getControlByFieldById(result[i].id).focus();
                            }

                            if (!("searchData" in result[i])) {
                                continue;
                            }

                            Field = getControlByFieldById(result[i].id);

                            if (Field) {
                                Field.setSearchData(result[i].searchData);
                            }
                        }
                    }.bind(this));

                    if (this.getAttribute('searchbutton')) {
                        new QUIButton({
                            textimage: 'fa fa-search',
                            text     : QUILocale.get('quiqqer/system', 'search'),
                            events   : {
                                onClick: this.$onSubmit
                            },
                            styles   : {
                                display : 'block',
                                'float' : 'none',
                                margin  : '10px auto 0',
                                maxWidth: '100%',
                                width   : 200
                            }
                        }).inject(this.$Elm);

                        this.$Elm.addClass(
                            'quiqqer-products-search-form-submitButton'
                        );
                    }

                    this.$loaded = true;

                }.bind(this));

            }.bind(this), {
                'package': 'quiqqer/products'
            });
        },

        /**
         * event : on submit
         */
        $onSubmit: function () {
            this.setAttribute('sheet', 1);
            this.search();
        },

        /**
         * Execute the search
         */
        search: function () {
            this.fireEvent('searchBegin', [this]);

            return new Promise(function (resolve) {

                var i, len, Field, fieldid, sortOn;

                var searchvalues = {},
                    FreeText     = this.$Elm.getElement('[name="search"]');

                switch (this.getAttribute('sortOn')) {
                    case 'price_netto':
                        sortOn = '1';
                        break;

                    case 'status':
                        sortOn = 'active';
                        break;

                    case 'id':
                    case 'productNo':
                    case 'title':
                    case 'priority':
                        sortOn = this.getAttribute('sortOn');
                        break;

                    default:
                        sortOn = '';
                }

                var params = {
                    sheet : this.getAttribute('sheet'),
                    limit : this.getAttribute('limit'),
                    sortOn: sortOn,
                    sortBy: this.getAttribute('sortBy')
                };

                if (FreeText) {
                    params.freetext = FreeText.value;
                }

                var controls = QUI.Controls.getControlsInElement(this.$Elm);

                var searchfields = controls.filter(function (Control) {
                    return Control.getType() === 'package/quiqqer/products/bin/controls/search/SearchField';
                });

                for (i = 0, len = searchfields.length; i < len; i++) {
                    Field   = searchfields[i];
                    fieldid = Field.getFieldId();

                    searchvalues[fieldid] = Field.getSearchValue();
                }

                params.fields = searchvalues;

                Ajax.get('package_quiqqer_products_ajax_search_backend_executeForGrid', function (result) {
                    resolve(result);
                    this.fireEvent('search', [this, result]);

                }.bind(this), {
                    'package'   : 'quiqqer/products',
                    searchParams: JSON.encode(params)
                });

            }.bind(this));
        }
    });
});
