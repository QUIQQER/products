/**
 * @module package/quiqqer/products/bin/controls/fields/types/Vat
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/tax/bin/classes/TaxTypes
 */
define('package/quiqqer/products/bin/controls/fields/types/Vat', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/tax/bin/classes/TaxTypes'

], function (QUI, QUIControl, TaxHandler) {
    "use strict";

    var Tax = new TaxHandler();

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Vat',

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

            // loader
            var Loader = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    lineHeight: 30,
                    textAlign : 'center',
                    width     : 50
                }
            }).inject(Elm, 'after');

            //vat select
            var Select = new Element('select', {
                'class': 'field-container-field',
                'html' : '<option value=""></option>',
                name   : Elm.name
            }).inject(Elm, 'after');

            Tax.getList().then(function (result) {
                var i, len, html, value;

                for (i = 0, len = result.length; i < len; i++) {
                    html  = result[i].groupTitle + ' : ' + result[i].title;
                    value = result[i].id;

                    new Element('option', {
                        html : html,
                        value: value
                    }).inject(Select);
                }

                Select.value = Elm.value;

                Elm.destroy();

                Loader.set(
                    'html',
                    '<span class="fa fa-percent"></span>'
                );
            });
        }
    });
});
