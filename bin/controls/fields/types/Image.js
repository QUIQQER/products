/**
 * @module package/quiqqer/products/bin/controls/fields/types/Image
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/utils/String
 * @require controls/projects/project/media/Popup
 * @require Ajax
 */
define('package/quiqqer/products/bin/controls/fields/types/Image', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/String',
    'controls/projects/project/media/Popup',
    'Ajax'

], function (QUI, QUIControl, QUIStringUtils, MediaPopup, QUIAjax) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Image',

        Binds: [
            '$onImport',
            'openMedia'
        ],

        options: {
            productFolder: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input   = null;
            this.$Preview = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$Elm = new Element('div', {
                'class': 'field-container-field field-container',
                styles : {
                    padding : 0,
                    position: 'relative'
                }
            }).wraps(Elm);

            this.$Input      = Elm;
            this.$Input.type = 'text';

            this.$Input.addClass('field-container-field');

            this.$Input.setStyles({
                border: 'none',
                width : 'calc(100% - 50px)'
            });

            new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-picture-o"></span>',
                styles : {
                    borderWidth: '0 0 0 1px',
                    cursor     : 'pointer',
                    lineHeight : 30,
                    textAlign  : 'center',
                    width      : 50
                },
                events : {
                    click: this.openMedia
                }
            }).inject(this.$Elm);

            this.$Preview = new Element('div', {
                styles: {
                    backgroundPosition: 'center center',
                    backgroundRepeat  : 'none',

                    height    : '100%',
                    left      : 0,
                    lineHeight: 40,
                    position  : 'absolute',
                    textAlign : 'center',
                    top       : 0,
                    width     : 50
                }
            }).inject(this.$Elm);

            this.refresh();
        },

        /**
         * refresh the preview
         */
        refresh: function () {
            var self  = this,
                value = this.$Input.value;

            if (value === '') {
                this.$Preview.setStyle('display', 'none');
                this.$Input.setStyle('paddingLeft', '0.75em');
                return;
            }

            this.$Preview.setStyle('display', null);
            this.$Input.setStyle('paddingLeft', 60);

            this.$Preview.set('html', '<span class="fa fa-spinner fa-spin"></span>');

            QUIAjax.get([
                'ajax_media_url_rewrited',
                'ajax_media_url_getPath'
            ], function (result, path) {
                var previewUrl = (URL_DIR + result).replace('//', '/');

                // self.$Path.set('html', path);
                // self.$Path.set('title', path);

                // load the image
                require(['image!' + previewUrl], function () {
                    self.$Preview.set('html', '');

                    self.$Preview.setStyle(
                        'background',
                        'url(' + previewUrl + ') no-repeat center center'
                    );
                }, function () {
                    self.$Preview.set('html', '<span class="fa fa-warning"></span>');
                });
            }, {
                fileurl: value,
                params : JSON.encode({
                    height: 40,
                    width : 40
                })
            });
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
                fileid : fileid,
                project: project,
                events : {
                    onSubmit: function (Window, imageData) {
                        self.$Input.value = imageData.url;
                        self.refresh();
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
