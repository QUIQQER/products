/**
 * @module package/quiqqer/products/bin/controls/fields/types/ProductAttributeList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/GroupListSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'controls/groups/Select',

    'text!package/quiqqer/products/bin/controls/fields/types/GroupListSettings.html',
    'css!package/quiqqer/products/bin/controls/fields/types/GroupListSettings.css'

], function (QUI, QUIControl, QUILocale, Mustache, GroupsInput, template) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/GroupListSettings',

        Binds: [
            'openAddDialog',
            'openEditDialog',
            'openRemoveDialog',
            '$onInject',
            '$onImport',
            '$buttonReset'
        ],

        options: {
            fieldId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Groups = null;
            this.$Input  = null;
            this.$data   = [];

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
                html  : Mustache.render(template, {}),
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
            this.$Input = this.getElm().getElement('[name="groupIds"]');

            this.$Groups = new GroupsInput({
                styles: {
                    height: 200
                }
            }).inject(this.$Input.getParent());

            this.refresh();
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

            try {
                this.$data = JSON.decode(this.$Input.value);

            } catch (e) {
                console.error(e);
            }

            if (!this.$data) {
                this.$data = [];
            }

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * refresh the grid data dispaly
         */
        refresh: function () {

        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode(this.$data);
        }
    });
});
