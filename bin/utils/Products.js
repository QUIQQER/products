/**
 * Products utils
 */
define('package/quiqqer/products/bin/utils/Products', [

    'Mustache',
    'Locale',

    'text!package/quiqqer/products/bin/controls/products/CreateField.html',

], function (Mustache, QUILocale, templateField) {
    "use strict";

    const lg = 'quiqqer/products';

    return {

        renderDataField: function (field) {
            let help  = false,
                title = QUILocale.get(lg, 'products.field.' + field.id + '.title');
            
            if (QUILocale.exists(lg, 'products.field.' + field.id + '.workingtitle')) {
                title = QUILocale.get(lg, 'products.field.' + field.id + '.workingtitle');
            }

            if (QUILocale.exists(lg, 'products.field.' + field.id + '.description')) {
                help = QUILocale.get(lg, 'products.field.' + field.id + '.description');
            } else if (field.help && field.help !== '') {
                help = field.help;
            }

            const FieldElm = new Element('tr', {
                'class'       : 'field',
                html          : Mustache.render(templateField, {
                    fieldTitle: title,
                    fieldHelp : help,
                    fieldName : 'field-' + field.id,
                    control   : field.jsControl
                }),
                'data-fieldid': field.id
            });

            const HelpIcon = FieldElm.getElement('.field-container-item-help');

            if (HelpIcon) {
                HelpIcon.addEvent('click', (event) => {
                    event.stop();
                });
            }

            return FieldElm;
        },

        /**
         * Generate SHA 256 hash from string.
         *
         * @param {String} str
         * @return {Promise<string>}
         */
        getSha256Hash: async function(str) {
            const encoder = new TextEncoder();
            const data = encoder.encode(str);
            const hashBuffer = await crypto.subtle.digest("SHA-256", data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
        }
    };
});