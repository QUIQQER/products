/**
 * @module package/quiqqer/products/bin/controls/frontend/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * Such control für das Frontend
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/Fields
 * @require Ajax
 * @require Locale
 * @require URL_OPT_DIR + bin/mustache/mustache.min.js
 */
define('package/quiqqer/products/bin/controls/frontend/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Fields',
    'Ajax',
    'Locale',
    URL_OPT_DIR + 'bin/mustache/mustache.min.js'

], function (QUI, QUIControl, Fields, QUIAjax, QUILocale, Mustache) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/search/Search',

        Binds: [
            '$onImport'
        ],

        options: {
            siteid : false,
            project: false,
            lang   : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Form = null;

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
                            fields        : result,
                            text_no_fields: 'Keine Suchefelder gefunden'
                        })
                    });

                    QUI.parse(Container).then(function () {
                        var i, id, len, Field;
                        var controls = QUI.Controls.getControlsInElement(Container);

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
console.log(Field);
console.log(result[i].searchData);
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
                                }
                            });
                        }).delay(200);

                    }.bind(this));

                }.bind(this));
            }.bind(this));
        },

        /**
         * Return the fields for the site
         *
         * @returns {Promise}
         */
        $getFields: function () {
            return new Promise(function (resolve) {

                console.log(this.getAttribute('siteid'));

                QUIAjax.get('package_quiqqer_products_ajax_search_frontend_getSearchFieldData', resolve, {
                    'package': 'quiqqer/products',
                    siteId   : this.getAttribute('siteid'),
                    project  : JSON.encode({
                        name: this.getAttribute('project'),
                        lang: this.getAttribute('lang')
                    })
                });
            }.bind(this));
        }
    });
});
