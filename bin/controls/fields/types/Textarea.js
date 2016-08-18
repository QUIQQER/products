/**
 * @module package/quiqqer/products/bin/controls/fields/types/TextareaSettings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/Textarea', [

    'qui/QUI',
    'qui/controls/Control',
    'Editors'

], function (QUI, QUIControl, Editors) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Textarea',

        Binds: [
            '$onInject'
        ],

        options: {
            value: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Editor = null;

            this.addEvents({
                onInject : this.$onInject,
                onDestroy: function () {
                    if (this.$Editor) {
                        this.$Editor.destroy()
                    }
                }.bind(this)
            });
        },

        /**
         * event : on import
         */
        $onInject: function () {
            var Elm = this.getElm();

            Elm.setStyles({
                'float': 'left',
                height : '100%'
            });

            Editors.getEditor().then(function (Editor) {

                this.$Editor = Editor;

                Editor.setContent(this.getAttribute('value'));
                Editor.inject(Elm);

            }.bind(this));
        },

        getValue: function () {
            return this.getAttribute('value');
        },

        /**
         * Save the data from the editor to the object
         */
        save: function () {
            this.setAttribute('value', this.$Editor.getContent());

            return this.getValue();
        }
    });
});
