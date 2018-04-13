/**
 * @module package/quiqqer/products/bin/controls/fields/types/Vat
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/Vat', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/tax/bin/classes/TaxTypes',
    'Locale'

], function (QUI, QUIControl, TaxHandler, QUILocale) {
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

            this.$Select = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this,
                Elm  = this.getElm();

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
            this.$Select = new Element('select', {
                'class': 'field-container-field',
                'html' : '<option value=""></option>',
                name   : Elm.name
            }).inject(Elm, 'after');

            // Wenn im Produkt
            if (this.getElm().getParent('.product-update')) {
                this.$Select.getElement('option').set({
                    html: QUILocale.get('quiqqer/products', 'field.vat.type.default')
                });
            }

            Tax.getList().then(function (result) {
                var i, len, html, value;

                var selectValue = '';

                if (Elm.value.match(':')) {
                    selectValue = Elm.value;
                }

                for (i = 0, len = result.length; i < len; i++) {
                    html  = result[i].groupTitle + ' : ' + result[i].title;
                    value = result[i].groupId + ':' + result[i].id;

                    if (result[i].id == Elm.value) {
                        selectValue = value;
                    }

                    new Element('option', {
                        html : html,
                        value: value
                    }).inject(self.$Select);
                }

                self.$Select.value = selectValue;

                Elm.destroy();

                Loader.set(
                    'html',
                    '<span class="fa fa-percent"></span>'
                );
            });
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
