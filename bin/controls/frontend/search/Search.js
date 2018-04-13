/**
 * @module package/quiqqer/products/bin/controls/frontend/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Such control für das Frontend
 */
define('package/quiqqer/products/bin/controls/frontend/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/String',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/Search',
    'Ajax',
    'Locale',
    URL_OPT_DIR + 'bin/mustache/mustache.min.js'

], function (QUI, QUIControl, QUIStringUtils, Fields, Search, QUIAjax, QUILocale, Mustache) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/search/Search',

        Binds: [
            '$onImport',
            '$onChange'
        ],

        options: {
            siteid        : false,
            project       : false,
            lang          : false,
            freeTextSearch: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Form   = null;
            this.$fields = [];
            this.$loaded = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$Form = Elm.getElement('form');

            this.$Form.setStyles({
                height: this.$Form.getSize().y
            });

            this.$Form.addEvents({
                submit: function (event) {
                    event.stop();
                    this.$onChange();
                }.bind(this),

                keyup: function (event) {
                    if (event.key === 'enter') {
                        event.stop();
                        this.$onChange();
                    }
                }.bind(this)
            });

            this.setAttribute('project', QUIQQER_PROJECT.name);
            this.setAttribute('lang', QUIQQER_PROJECT.lang);
            this.setAttribute('siteid', parseInt(QUIQQER_SITE.id));

            if (Elm.get('data-project')) {
                this.setAttribute('project', Elm.get('data-project'));
            }

            if (Elm.get('data-lang')) {
                this.setAttribute('lang', Elm.get('data-lang'));
            }

            if (Elm.get('data-siteid')) {
                this.setAttribute('siteid', parseInt(Elm.get('data-siteid')));
            }


            this.$getFields().then(function (result) {
                require([
                    'text!package/quiqqer/products/bin/controls/frontend/search/Search.Form.html'
                ], function (template) {
                    var Container     = new Element('div');
                    var FormContainer = this.$Form.getElement('.inner-form');

                    Container.set({
                        styles: {
                            opacity : 0,
                            position: 'relative',
                            zIndex  : 1
                        },
                        html  : Mustache.render(template, {
                            fields                  : result,
                            freeTextSearch          : this.getAttribute('freeTextSearch'),
                            fieldTitleFreeTextSearch: QUILocale.get(lg, 'searchtype.freeTextSearch.title'),
                            text_no_fields          : QUILocale.get(lg, 'searchtypes.empty.frontend')
                        })
                    });

                    QUI.parse(Container).then(function () {
                        var i, id, len, Field;
                        var self     = this,
                            controls = QUI.Controls.getControlsInElement(Container);

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

                            Field = getControlByFieldById(result[i].id);
                            Field.addEvent('onChange', this.$onChange);

                            this.$fields.push(Field);

                            if (!("searchData" in result[i])) {
                                continue;
                            }

                            if (Field) {
                                Field.setSearchData(result[i].searchData);
                            }
                        }

                        FormContainer.setStyles({
                            height  : '100%',
                            left    : 0,
                            position: 'absolute',
                            top     : 0,
                            width   : '100%',
                            zIndex  : 2
                        });

                        Container.inject(this.$Form);

                        this.$loadFromUrl();

                        moofx(Container).animate({
                            opacity: 1
                        }, {
                            duration: 250
                        });

                        (function () {
                            moofx(FormContainer).animate({
                                opacity: 0
                            }, {
                                duration: 250,
                                callback: function () {
                                    FormContainer.destroy();
                                    self.$loaded = true;
                                }
                            });
                        }).delay(200);

                    }.bind(this));

                }.bind(this));
            }.bind(this));
        },

        /**
         * @return {Object}
         */
        getFieldValues: function () {
            var i, len, Field;
            var values = {};

            for (i = 0, len = this.$fields.length; i < len; i++) {
                Field = this.$fields[i];

                values[Field.getFieldId()] = Field.getSearchValue();
            }

            return values;
        },

        /**
         * Return the free text search value
         *
         * @return {String}
         */
        getFreeTextSearch: function () {
            var FreeText = this.getElm().getElement('[name="search"]');

            if (FreeText) {
                return FreeText.value;
            }

            return '';
        },

        /**
         * Return the fields for the site
         *
         * @returns {Promise}
         */
        $getFields: function () {
            return Search.getFieldData(this.getAttribute('siteid'), {
                name: this.getAttribute('project'),
                lang: this.getAttribute('lang')
            });
        },

        /**
         * event on field change
         */
        $onChange: function () {
            if (!this.$loaded) {
                return;
            }

            if (typeof window.history === 'undefined') {
                return;
            }

            var loc   = '',
                query = this.$buildUrlQuery();

            if ("origin" in location) {
                loc = location.origin;
            }

            var url = loc + location.pathname;

            if (query !== '') {
                url = url + '?' + query;
            }

            window.history.pushState({}, "", url);

            this.fireEvent('change', [this]);
        },

        /**
         * Build the url search query
         *
         * @returns {String}
         */
        $buildUrlQuery: function () {
            var params   = {},
                values   = this.getFieldValues(),
                freetext = this.getFreeTextSearch();

            if (freetext !== '') {
                params.search = JSON.encode(freetext);
            }

            var key, val;
            for (key in values) {
                if (!values.hasOwnProperty(key)) {
                    continue;
                }

                val = values[key];

                if (val === '') {
                    continue;
                }

                params[key] = JSON.encode(val);
            }

            return Object.toQueryString(params);
        },

        /**
         * load the field values from the url
         */
        $loadFromUrl: function () {
            var params = QUIStringUtils.getUrlParams(
                window.location.toString()
            );

            for (var key in params) {
                if (params.hasOwnProperty(key)) {
                    try {
                        params[key] = JSON.decode(decodeURIComponent(params[key]));
                    } catch (e) {
                    }
                }
            }

            console.log('$loadFromUrl');
            console.log(params);
        }
    });
});
