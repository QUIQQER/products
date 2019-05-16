/**
 * @module package/quiqqer/products/bin/controls/products/GenerateVariantsWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/GenerateVariantsWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/products/variants/GenerateVariants',
    'Ajax',
    'Locale'

], function (QUI, QUIConfirm, GenerateVariants, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/GenerateVariantsWindow',

        Binds: [
            '$onOpen'
        ],

        options: {
            productId: false,
            maxWidth : 600,
            maxHeight: 800,
            autoclose: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                icon     : 'fa fa-magic',
                title    : QUILocale.get(lg, 'variants.generating.window.title'),
                ok_button: {
                    text     : QUILocale.get(lg, 'variants.generating.window.button.generate'),
                    textimage: 'fa fa-magic'
                }
            });

            this.$List = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * events: on open
         */
        $onOpen: function () {
            var self = this;

            this.Loader.show();

            this.getContent().set('html', '');
            this.getContent().setStyles({
                display         : 'flex',
                'flex-direction': 'column'
            });

            new Element('div', {
                html  : QUILocale.get(lg, 'variants.generating.window.description'),
                styles: {
                    paddingBottom: 20
                }
            }).inject(this.getContent());

            var ListContainer = new Element('div', {
                styles: {
                    'flex-grow': 1
                }
            }).inject(this.getContent());

            this.$List = new GenerateVariants({
                productId: this.getAttribute('productId'),
                events   : {
                    onLoad: function () {
                        self.Loader.hide();
                    }
                }
            }).inject(ListContainer);

            this.$List.resize();
        },

        /**
         * event: on submit
         */
        $onSubmit: function () {
            var self = this;

            this.Loader.show(
                QUILocale.get(lg, 'variants.generating.window.generating')
            );

            this.$List.generate().then(function () {
                self.close();
                self.fireEvent('variantCreation');
            }).catch(function () {
                self.Loader.hide();
            });
        }
    });
});