/**
 * @module package/quiqqer/products/bin/controls/fields/types/GroupList
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/GroupList', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/GroupList',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            new Element('input', {
                'class': 'field-container-field',
                type   : 'text',
                name   : Elm.get('data-name')
            }).inject(Elm, 'after');

            Elm.destroy();
        }
    });
});
