/**
 * @module package/quiqqer/products/bin/controls/products/settings/ClearProductCache
 * @author www.pcsg.de (Henning Leutz)
 *
 * Cache löschen in den settings
 */
define('package/quiqqer/products/bin/controls/products/settings/ClearProductCache', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIButton, Ajax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/settings/ClearProductCache',

        Binds: [
            '$onClick'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Button = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Button = new QUIButton({
                textimage: 'fa fa-trash',
                text     : QUILocale.get('quiqqer/products', 'settings.window.products.clearcache.button'),
                events   : {
                    onClick: this.$onClick
                }
            }).inject(this.getElm(), 'after');

            this.$Button.getElm().addClass('field-container-field');
        },

        /**
         * event : click
         */
        $onClick: function () {
            this.$Button.setAttribute('textimage', 'fa fa-spinner fa-spin');

            Ajax.get('package_quiqqer_products_ajax_search_clearSearchCache', function () {
                this.$Button.setAttribute('textimage', 'fa fa-trash');
            }.bind(this), {
                'package': 'quiqqer/products'
            });
        }
    });
});
