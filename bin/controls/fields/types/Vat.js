define('package/quiqqer/products/bin/controls/fields/types/Vat', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/tax/bin/classes/TaxTypes',
    'Locale'

], function (QUI, QUIControl, TaxHandler, QUILocale) {
    "use strict";

    const Tax = new TaxHandler();

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/Vat',

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
            const self = this,
                Elm = this.getElm();

            Elm.type = 'hidden';

            // loader
            const Loader = new Element('span', {
                'class': 'field-container-item',
                html: '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    lineHeight: 30,
                    textAlign: 'center',
                    width: 50
                }
            }).inject(Elm, 'after');

            //vat select
            this.$Select = new Element('select', {
                'class': 'field-container-field',
                'html': '<option value=""></option>',
                events: {
                    change: function () {
                        Elm.value = self.$Select.value;
                    }
                }
                //name   : Elm.name
            }).inject(Elm, 'after');

            // Wenn im Produkt
            const isInProduct = !!this.getElm().getParent('.product-update');
            const defaultOption = this.$Select.getElement('option');

            if (isInProduct) {
                defaultOption.set({
                    html: QUILocale.get('quiqqer/products', 'field.vat.type.default')
                });
            }

            Tax.getList().then(function (result) {
                let i, len, html, value;
                let selectValue = '';

                if (Elm.value !== '') {
                    selectValue = Elm.value;
                }

                for (i = 0, len = result.length; i < len; i++) {
                    html = result[i].groupTitle + ' : ' + result[i].title;

                    if (isInProduct) {
                        value = result[i].id;
                    } else {
                        value = result[i].groupId + ':' + result[i].id;
                    }

                    if (result[i].id == Elm.value) {
                        selectValue = value;
                    }

                    new Element('option', {
                        html: html,
                        value: value
                    }).inject(self.$Select);
                }

                if (selectValue != -1) {
                    self.$Select.value = selectValue;
                } else {
                    defaultOption.selected = 'selected';
                }

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
            return this.$Select.value;
        }
    });
});
