/**
 * @module package/quiqqer/products/bin/controls/products/settings/SortFields
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/settings/SortFields', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'controls/grid/Grid',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, QUISwitch, Grid, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/settings/SortFields',

        Binds: [
            '$onImport',
            '$onSwitchChange'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Site = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$confGroup = this.$Input.name;

            // is it in site?
            var PanelNode = this.$Input.getParent('.qui-panel');

            if (PanelNode) {
                var Panel = QUI.Controls.getById(PanelNode.get('data-quiid'));

                if (Panel.getType() === 'controls/projects/project/site/Panel') {
                    this.$Site = Panel.getSite();
                }
            }

            // create
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    width  : '100%'
                }
            }).wraps(this.$Input);

            if (this.$Elm.getParent('.field-container')) {
                new Element('div', {
                    'class': 'field-container-field field-container-field-no-padding'
                }).wraps(this.$Elm);
            }

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
                    width  : Math.round(size.x)
                }
            }).inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                height     : 300,
                width      : Math.round(size.x),
                perPage    : 150,
                columnModel: [{
                    header   : QUILocale.get(lg, 'settings.window.products.grid.sortFields'),
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
                }]
            });

            this.$Grid.addEvents({
                refresh: function () {
                    this.refresh().catch(function (err) {
                        console.error(err);
                    });
                }.bind(this)
            });

            this.refresh().catch(function (err) {
                console.error(err);
            });
        },

        /**
         * resize the control
         *
         * @return {void|Promise}
         */
        resize: function () {
            var Parent = this.getElm().getParent('.field-container-field');
            var size   = Parent.getSize();

            return this.$Grid.setWidth(size.x);
        },

        /**
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            if (this.$Site) {
                return new Promise(function (resolve, reject) {
                    QUIAjax.get('package_quiqqer_products_ajax_fields_getSortableFieldsForSite', function (fields) {
                        self.$parseFieldData(fields);
                        resolve();
                    }, {
                        'package'  : 'quiqqer/products',
                        onError    : reject,
                        siteId     : self.$Site.getId(),
                        projectData: self.$Site.getProject().encode()
                    });
                });
            }

            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_products_ajax_fields_getSortableFields', function (fields) {
                    self.$parseFieldData(fields);
                    resolve();
                }, {
                    'package': 'quiqqer/products',
                    onError  : reject
                });
            });
        },

        /**
         *
         * @param fields
         */
        $parseFieldData: function (fields) {
            for (var i = 0, len = fields.length; i < len; i++) {
                fields[i].status = new QUISwitch({
                    status : fields[i].sorting,
                    fieldId: fields[i].id,
                    events : {
                        onChange: this.$onSwitchChange
                    }
                });
            }


            this.$Grid.setData({
                data: fields
            });
        },

        /**
         * event: switch change
         */
        $onSwitchChange: function () {
            var controls = QUI.Controls.getControlsInElement(this.$Elm);
            var switches = controls.filter(function (Control) {
                return Control.getType() === 'qui/controls/buttons/Switch';
            });

            var values = [];

            for (var i = 0, len = switches.length; i < len; i++) {
                if (switches[i].getStatus()) {
                    values.push(switches[i].getAttribute('fieldId'));
                }
            }

            this.$Input.value = values.join(',');
        }
    });
});
