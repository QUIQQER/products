/**
 * @module package/quiqqer/products/bin/controls/fields/types/Folder
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/Folder', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/String',
    'controls/projects/project/media/Popup'

], function (QUI, QUIControl, QUIStringUtils, MediaPopup) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Folder',

        Binds: [
            '$onImport',
            'openMedia'
        ],

        options: {
            productFolder: false
        },

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
            var self    = this,
                value   = this.$Input.value,
                fileid  = false,
                project = false;

            var productFolder = this.getAttribute('productFolder'),
                urlParams     = {};

            if (value === '' && productFolder) {
                urlParams = QUIStringUtils.getUrlParams(productFolder);
            } else if (value !== '') {
                urlParams = QUIStringUtils.getUrlParams(value);
            }

            if ("id" in urlParams) {
                fileid = urlParams.id;
            }

            if ("project" in urlParams) {
                project = urlParams.project;
            }

            new MediaPopup({
                fileid          : fileid,
                project         : project,
                selectable_types: ['folder'],
                events          : {
                    onSubmit: function (Window, imageData) {
                        self.$Input.value = imageData.url;
                    }
                }
            }).open();
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        }
    });
});
