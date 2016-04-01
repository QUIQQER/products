/**
 * @module package/quiqqer/products/bin/controls/fields/types/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'controls/grid/Grid'

], function (QUI, QUIControl, QUILocale, Grid) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings',

        Binds: [
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid  = null;
            this.$Input = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    width  : '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {

            var Container = new Element('div', {
                styles: {
                    'float': 'left',
                    height : 300
                }
            }).inject(this.$Elm);

            var size = this.$Elm.getSize();

            this.$Grid = new Grid(Container, {
                pagination : true,
                buttons    : [{
                    textimage: 'fa fa-plus',
                    text     : QUILocale.get('quiqqer/system', 'add')
                }, {
                    textimage: 'fa fa-edit',
                    text     : QUILocale.get('quiqqer/system', 'edit'),
                    disabled : true
                }, {
                    type: 'seperator'
                }, {
                    textimage: 'fa fa-trashcan',
                    text     : QUILocale.get('quiqqer/system', 'delete'),
                    disabled : true
                }],
                columnModel: [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60
                }]
            });

            this.$Grid.setHeight(size.y);
            this.$Grid.setWidth(size.x);
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function (self, Node) {
            this.$Input = Node;
            this.$Elm   = this.create();

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        }
    });
});
