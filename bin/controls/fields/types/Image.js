/**
 * @module package/quiqqer/products/bin/controls/fields/types/Image
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 */
define('package/quiqqer/products/bin/controls/fields/types/Image', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/projects/project/media/Popup'

], function (QUI, QUIControl, MediaPopup) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Image',

        Binds: [
            '$onImport',
            'openMedia'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-picture-o"></span>',
                styles : {
                    cursor    : 'pointer',
                    lineHeight: 30,
                    textAlign : 'center',
                    width     : 50
                },
                events : {
                    click: this.openMedia
                }
            }).inject(Elm, 'after');

            Elm.type = 'text';
            Elm.addClass('field-container-field');

            this.$Input = Elm;
        },

        /**
         * opens the media
         */
        openMedia: function () {
            var self  = this,
                value = this.$Input.value;

            console.log(value);

            new MediaPopup({
                events: {
                    onSubmit: function (Window, imageData) {
                        self.$Input.value = imageData.url;
                    }
                }
            }).open();
        }
    });
});
