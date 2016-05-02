/**
 * @module package/quiqqer/products/bin/controls/products/search/Settings
 * @author www.pcsg.de (Henning Leutz)
 *
 * Backend suche für produkte
 */
define('package/quiqqer/products/bin/controls/products/search/Settings', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, Grid, QUILocale, Ajax) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/search/Settings',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

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

            new Grid(Container, {
                height     : 300,
                width      : size.x - 100,
                columnModel: [{
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
                    header   : QUILocale.get(lg, 'standardField'),
                    dataIndex: 'isStandard',
                    dataType : 'node',
                    width    : 60
                }, {
                    header   : QUILocale.get(lg, 'requiredField'),
                    dataIndex: 'isRequired',
                    dataType : 'node',
                    width    : 60
                }]
            });
        },

        refresh: function () {
            return new Promise(function (resolve, reject) {
                Ajax.get('', function () {


                    resolve();
                }, {
                    'package': 'quiqqer/products'
                });
            });
        }
    });
});
