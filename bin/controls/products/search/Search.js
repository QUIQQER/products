/**
 * @module package/quiqqer/products/bin/controls/products/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require Ajax
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/products/search/Search.css
 */
define('package/quiqqer/products/bin/controls/products/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'package/quiqqer/products/bin/Fields',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/search/Search.html',
    'css!package/quiqqer/products/bin/controls/products/search/Search.css'

], function (QUI, QUIControl, Grid, Fields, Ajax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Search',

        Binds: [
            '$onCreate',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Result = null;
            this.$Form   = null;
            this.$Grid   = null;


            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-search',
                html   : '<div class="quiqqer-products-search-result"></div>' +
                         '<div class="quiqqer-products-search-form"></div>'
            });

            this.$Result = this.getElm().getElement('.quiqqer-products-search-result');
            this.$Form   = this.getElm().getElement('.quiqqer-products-search-form');

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var Container = new Element('div', {
                'class': 'quiqqer-products-search-result-container'
            }).inject(this.$Result);

            this.$Grid = new Grid(Container, {
                columnModel: [{
                    header   : QUILocale.get(lg, 'product.fields.grid.visible'),
                    dataIndex: 'visible',
                    dataType : 'QUI',
                    width    : 60
                }, {
                    header   : '&nbsp;',
                    dataIndex: 'ownFieldDisplay',
                    dataType : 'node',
                    width    : 30
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'fieldtype'),
                    dataIndex: 'fieldtype',
                    dataType : 'text',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'priority'),
                    dataIndex: 'priority',
                    dataType : 'number',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'prefix'),
                    dataIndex: 'prefix',
                    dataType : 'text',
                    width    : 100
                }, {
                    header   : QUILocale.get(lg, 'suffix'),
                    dataIndex: 'suffix',
                    dataType : 'text',
                    width    : 100
                }, {
                    dataIndex: 'ownField',
                    dataType : 'hidden'
                }]
            });

            this.showSearch();
        },

        /**
         * Show the search
         *
         * @returns {Promise}
         */
        showSearch: function () {
            return this.$hideContainer(this.$Result).then(function () {

                return new Promise(function (resolve, reject) {
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

                            this.$Form.set({
                                html: Mustache.render(template, {
                                    fields        : result,
                                    text_no_fields: 'Keine Suchefelder gefunden'
                                })
                            });

                            QUI.parse(this.$Form).then(function () {
                                var Field;
                                var controls = QUI.Controls.getControlsInElement(this.$Form);

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


                                this.$showContainer(this.$Form).then(resolve, reject);
                            }.bind(this));

                        }.bind(this));

                    }.bind(this), {
                        'package': 'quiqqer/products',
                        onError  : reject
                    });
                }.bind(this));

            }.bind(this));
        },

        /**
         * Show the results
         *
         * @returns {Promise}
         */
        showResults: function () {
            return this.$hideContainer(this.$Result).then(function () {

                return new Promise(function (resolve, reject) {
                    Ajax.get('package_quiqqer_products_ajax_search_backend_getSearchFieldData', function (result) {

                        for (var i = 0, len = result.length; i < len; i++) {
                            console.log(result[i]);
                        }


                        this.$showContainer(this.$Form).then(resolve, reject);

                    }.bind(this), {
                        'package': 'quiqqer/products',
                        onError  : reject
                    });
                }.bind(this));

            }.bind(this));
        },

        /**
         * Hide a container
         *
         * @param {HTMLDivElement} Container
         * @returns {Promise}
         */
        $hideContainer: function (Container) {
            return new Promise(function (resolve) {
                moofx(Container).animate({
                    opacity: 0,
                    top    : -50
                }, {
                    duration: 200,
                    callback: function () {
                        Container.setStyle('display', 'none');
                        resolve();
                    }
                });
            });
        },

        /**
         * Show a container
         *
         * @param {HTMLDivElement} Container
         * @returns {Promise}
         */
        $showContainer: function (Container) {
            Container.setStyle('display', 'block');

            return new Promise(function (resolve) {
                moofx(Container).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 200,
                    callback: resolve
                });
            });
        }
    });
});
