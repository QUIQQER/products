/**
 * Frontend control for handling media items of "media" product fields.
 *
 * @module package/quiqqer/products/bin/controls/frontend/fields/Folder
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/products/bin/controls/frontend/fields/Folder', [

    'qui/QUI',
    'Ajax',
    'package/quiqqer/products/bin/controls/frontend/fields/Field'

], function (QUI, QUIAjax, FieldControl) {
    "use strict";

    return new Class({
        Extends: FieldControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/fields/Folder',

        Binds: [
            '$onImport',
            '$download'
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
            const Elm = this.getElm();

            Elm.getElements('.product-data-files-table-download a').addEvent('click', (event) => {
                event.stop();

                let ClickedElm = event.target;

                if (ClickedElm.nodeName !== 'A') {
                    ClickedElm = ClickedElm.getParent('a');
                }

                this.$download(ClickedElm.get('data-id'));
            });
        },

        /**
         * Download media item
         *
         * @param {Number} fileId
         */
        $download: function (fileId) {
            const url = QUIAjax.$url + '?' + QUIAjax.parseParams('ajax_media_file_download', {
                project: QUIQQER_PROJECT.name,
                fileid : fileId
            });

            const id = 'download-customer-file-' + String.uniqueID();

            new Element('iframe', {
                src   : window.location.origin + url,
                id    : id,
                styles: {
                    position: 'absolute',
                    top     : -200,
                    left    : -200,
                    width   : 50,
                    height  : 50
                }
            }).inject(document.body);

            (() => {
                document.getElements('#' + id).destroy();
            }).delay(20000);
        }
    });
});
