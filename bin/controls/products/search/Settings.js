/**
 * @module package/quiqqer/products/bin/controls/products/search/Settings
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend such einstellungen
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Switch
 * @require controls/grid/Grid
 * @require Locale
 * @require Ajax
 * @require package/quiqqer/products/bin/Fields
 */
define('package/quiqqer/products/bin/controls/products/search/Settings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'controls/grid/Grid',
    'Locale',
    'Ajax',
    'package/quiqqer/products/bin/Fields'

], function (QUI, QUIControl, QUISwitch, Grid, QUILocale, Ajax, Fields) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Settings',

        Binds: [
            '$onImport',
            '$onSwitchChange',
            'refresh'
        ],

        options: {
            ids: []
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input     = null;
            this.$confGroup = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on create
         */
        $onImport: function () {
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$confGroup = this.$Input.name;

            // create
            this.$Elm = new Element('div', {
                styles: {
                    'float'  : 'left',
                    marginTop: 20,
                    width    : '100%'
                }
            }).wraps(this.$Input);

            // label
            var Row   = this.$Input.getParent('.qui-xml-panel-row-item');
            var Label = document.getElement('[for="' + this.$Input.id + '"]');

            if (Label) {
                Label.setStyle('width', '100%');
            }

            if (Row) {
                Row.setStyle('width', '100%');
            }


            // size
            var size = this.$Elm.getSize();

            // grid container
            var Container = new Element('div', {
                styles: {
                    'float': 'left',
                    width  : size.x
                }
            }).inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                height     : 300,
                width      : size.x - 100,
                perPage    : 150,
                columnModel: [{
                    header   : QUILocale.get(lg, 'settings.window.products.grid.searchstatus'),
                    dataIndex: 'status',
                    dataType : 'QUI',
                    width    : 60
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
                    header   : QUILocale.get(lg, 'searchtype'),
                    dataIndex: 'search_type',
                    dataType : 'text',
                    width    : 200
                }]
            });

            this.$Grid.addEvents({
                refresh: this.refresh
            });

            this.$Grid.refresh();
        },

        /**
         * Get the available search fields
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('package_quiqqer_products_ajax_search_backend_getSearchFields', function (result) {
                    var fieldIds = Object.keys(result),
                        ids      = [],
                        values   = this.$Input.value.split(',');

                    values.each(function (value) {
                        ids.push(parseInt(value));
                    });

                    this.setAttribute('ids', ids);

                    Fields.getChildren(fieldIds).then(function (fieldlist) {
                        var fieldsData = fieldlist.map(function (entry) {
                            var Switch = new QUISwitch({
                                status : ids.contains(entry.id),
                                fieldId: entry.id,
                                events : {
                                    onChange: this.$onSwitchChange
                                }
                            });

                            return {
                                status     : Switch,
                                id         : entry.id,
                                title      : entry.title,
                                fieldtype  : entry.type,
                                search_type: entry.search_type
                            };
                        }.bind(this));

                        this.$Grid.setData({
                            data: fieldsData
                        });

                        resolve();
                    }.bind(this));

                }.bind(this), {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            }.bind(this));
        },

        /**
         * Set fields to the config
         *
         * @param {Array} searchFields
         * @returns {Promise}
         */
        setFields: function (searchFields) {
            return new Promise(function (resolve, reject) {
                var call = '';
                switch (this.$confGroup) {
                    case 'search.frontend':
                        call = 'package_quiqqer_products_ajax_search_frontend_setGlobalSearchFields';
                        break;

                    case 'search.backend':
                        call = 'package_quiqqer_products_ajax_search_backend_setSearchFields';
                        break;

                    case 'search.freetext':
                        call = 'package_quiqqer_products_ajax_search_global_setSearchFields';
                        break;

                    default:
                        return reject();
                }

                Ajax.post(call, resolve, {
                    'package'   : 'quiqqer/products',
                    onError     : reject,
                    searchFields: JSON.encode(searchFields)
                });
            }.bind(this));
        },

        /**
         * event : switch status change
         *
         * @apram {Object} CurrentSwitch
         */
        $onSwitchChange: function (CurrentSwitch) {
            var controls = QUI.Controls.getControlsInElement(this.$Elm);
            var switches = controls.filter(function (Control) {
                return Control.getType() === 'qui/controls/buttons/Switch';
            });

            var i, len, fieldId;
            var values = {};

            for (i = 0, len = switches.length; i < len; i++) {
                fieldId = switches[i].getAttribute('fieldId');

                values[fieldId] = switches[i].getStatus();
            }

            CurrentSwitch.disable();

            this.setFields(values).then(function (result) {
                var keys = [];

                for (var i in result) {
                    if (result.hasOwnProperty(i) && result[i]) {
                        keys.push(i);
                    }
                }

                this.$Input.value = keys.join(',');
                this.refresh();

            }.bind(this));
        }
    });
});
