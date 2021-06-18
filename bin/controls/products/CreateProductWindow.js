/**
 * @module package/quiqqer/products/bin/controls/products/CreateProductWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/CreateProductWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale'

], function (QUI, QUIConfirm, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/CreateProductWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            maxHeight         : 600,
            maxWidth          : 800,
            backgroundClosable: false,
            categories        : false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                title        : QUILocale.get(lg, 'products.create.title'),
                icon         : 'fa fa-edit',
                autoclose    : false,
                ok_button    : {
                    text     : QUILocale.get(lg, 'panel.product.window.create.button'),
                    textimage: 'fa fa-plus'
                },
                cancel_button: {
                    text     : QUILocale.get('quiqqer/quiqqer', 'cancel'),
                    textimage: false
                }
            });

            this.$Create = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            this.Loader.show();
            this.getContent().set('html', '');

            require([
                'package/quiqqer/products/bin/controls/products/Create'
            ], (CreateProduct) => {
                this.$Create = new CreateProduct({
                    categories: this.getAttribute('categories'),
                    events    : {
                        onLoaded: () => {
                            this.Loader.hide();
                        }
                    }
                }).inject(this.getContent());
            });
        },

        /**
         * event: on submit
         */
        $onSubmit: function () {
            this.Loader.show();

            this.$Create.submit().then((product) => {
                this.close();
                this.fireEvent('productCreated', [this, product]);
            }).catch((err) => {
                console.error(err);
                this.Loader.hide();
            });
        }
    });
});
