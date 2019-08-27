/**
 * @modue package/quiqqer/products/bin/controls/search/FieldSelect
 * @author www.pcsg.de (Henning Leutz)
 *
 * Feld Auswahl für eine Produktkategorie oder Produktsuche
 */
define('package/quiqqer/products/bin/controls/search/FieldSelect', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/controls/fields/Select',
    'Ajax'

], function (QUI, QUIControl, Fields, FieldSelect, Ajax) {
    "use strict";

    return new Class({
        Type   : 'package/quiqqer/products/bin/controls/search/FieldSelect',
        Extends: QUIControl,

        Binds: [
            '$onImport',
            '$onSelectChange',
            '$search'
        ],

        options: {
            siteId            : false,
            project           : false,
            Site              : false,
            showsearchableonly: false       // only show fields that are searchable
        },

        initialize: function (options) {
            this.parent(options);

            this.$FieldSelect = null;
            this.$fields      = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * events : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            Elm.type = 'hidden';

            this.$FieldSelect = new FieldSelect({
                disabled          : true,
                search            : this.$search,
                showsearchableonly: this.getAttribute('showsearchableonly'),
                events            : {
                    onChange: this.$onSelectChange
                }
            }).inject(Elm.getParent());

            if (!this.getAttribute('Site')) {
                var Panel    = false,
                    DomPanel = Elm.getParent('.qui-panel');

                if (DomPanel) {
                    Panel = QUI.Controls.getById(DomPanel.get('data-quiid'));

                    if (Panel.getType() === 'controls/projects/project/site/Panel') {
                        this.setAttribute('Site', Panel.getSite());
                    }
                }
            }

            if (Elm.value !== '') {
                try {
                    var ids    = [];
                    var values = JSON.decode(Elm.value);

                    for (var id in values) {
                        if (values.hasOwnProperty(id)) {
                            ids.push(id);
                        }
                    }

                    this.$FieldSelect.addFields(ids);

                } catch (e) {
                }
            }

            if (this.getAttribute('Site')) {
                this.$FieldSelect.enable();
            }
        },

        /**
         * Search fields
         *
         * @param {String} value
         * @param {String} params
         * @returns {Promise}
         */
        $search: function (value, params) {
            return new Promise(function (resolve, reject) {

                this.$getFields().then(function (result) {
                    var list = [];

                    for (var i = 0, len = result.length; i < len; i++) {
                        if (value === '' || !value) {
                            list.push(result[i]);
                            continue;
                        }

                        if (result[i].title.match(value)) {
                            list.push(result[i]);
                        }
                    }

                    resolve(list);

                }, reject);

            }.bind(this));
        },

        /**
         * Return the available fields
         *
         * @returns {*}
         */
        $getFields: function () {
            return new Promise(function (resolve, reject) {
                if (this.$fields) {
                    resolve(this.$fields);
                    return;
                }

                var Site = this.getAttribute('Site');

                if (!Site) {
                    reject(false);
                    return;
                }

                var Project = Site.getProject();

                Ajax.get('package_quiqqer_products_ajax_search_frontend_getSearchFields', function (result) {

                    var fieldIds = [];

                    for (var fieldId in result) {
                        if (result.hasOwnProperty(fieldId)) {
                            fieldIds.push(fieldId);
                        }
                    }

                    Fields.getChildren(fieldIds).then(function (result) {
                        this.$fields = result;
                        resolve(this.$fields);
                    }.bind(this));

                }.bind(this), {
                    'package': 'quiqqer/products',
                    siteId   : Site.getId(),
                    project  : Project.encode(),
                    options  : JSON.encode({
                        showSearchableOnly: this.getAttribute('showsearchableonly')
                    }),
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * event : on select change
         */
        $onSelectChange: function () {
            var value = {};
            var ids   = this.$FieldSelect.getValue().split(',');

            for (var i = 0, len = ids.length; i < len; i++) {
                value[ids[i]] = true;
            }

            this.getElm().value = JSON.encode(value);
            this.fireEvent('change', [this]);
        }
    });
});
