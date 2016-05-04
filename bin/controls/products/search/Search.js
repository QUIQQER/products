/**
 * @module package/quiqqer/products/bin/controls/products/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte, nur das suchformular
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require Ajax
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/products/search/Search.css
 *
 * @event onSearchBegin [this]
 */
define('package/quiqqer/products/bin/controls/products/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/search/Search.html',
    'css!package/quiqqer/products/bin/controls/products/search/Search.css'

], function (QUI, QUIControl, QUIButton, Grid, Fields, Ajax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            'search',
            '$onInject'
        ],

        options: {
            searchfields: {},
            searchbutton: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Form = null;
            
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
            return Promise.resize();
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLFormElement}
         */
        create: function () {
            this.$Elm = new Element('form', {
                'class': 'quiqqer-products-search'
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
                            fields        : result,
                            text_no_fields: 'Keine Suchefelder gefunden'
                        })
                    });

                    QUI.parse(this.$Elm).then(function () {
                        var Field;
                        var controls = QUI.Controls.getControlsInElement(this.$Elm);

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
                            text     : 'Suche',
                            events   : {
                                onClick: this.search
                            }
                        }).inject(this.$Elm);
                    }

                }.bind(this));

            }.bind(this), {
                'package': 'quiqqer/products'
            });
        },

        /**
         * Execute the search
         */
        search: function () {
            this.fireEvent('searchBegin', [this]);

            return new Promise(function (resolve) {

                var params   = {},
                    controls = QUI.Controls.getControlsInElement(this.$Elm);

                var searchfields = controls.filter(function (Control) {
                    return Control.getType() === 'package/quiqqer/products/bin/controls/search/SearchField';
                });

                var i, len, Field, fieldid;
                var searchvalues = {};

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
