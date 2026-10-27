/**
 * Shared confirmation for deleting products from the list or an open product panel.
 */
define('package/quiqqer/products/bin/controls/products/DeleteProductWindow', [
    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Locale',
    'package/quiqqer/products/bin/Products'
], function (QUI, Confirm, Locale, Products) {
    'use strict';

    const lg = 'quiqqer/products';

    return new Class({
        Extends: Confirm,
        Type: 'package/quiqqer/products/bin/controls/products/DeleteProductWindow',

        initialize: function (options) {
            options = options || {};
            const products = options.products || [];
            this.$productIds = products.map(product => product.id);
            this.$pending = false;

            const List = document.createElement('ul');

            for (const product of products) {
                const Item = document.createElement('li');
                Item.textContent = product.id + ': ' + product.title;
                List.appendChild(Item);
            }

            this.parent(Object.assign({}, options, {
                title: Locale.get(lg, 'products.window.delete.title'),
                text: Locale.get(lg, 'products.window.delete.text'),
                information: Locale.get(lg, 'products.window.delete.information', {products: List.outerHTML}),
                autoclose: false,
                maxHeight: 400,
                maxWidth: 600,
                icon: 'fa fa-trash',
                texticon: 'fa fa-trash',
                ok_button: {
                    text: Locale.get('quiqqer/core', 'delete'),
                    textimage: 'fa fa-trash'
                }
            }));

            this.addEvents({
                onOpen: () => {
                    const Submit = this.getButton('submit').getElm();
                    Submit.classList.remove('btn-success');
                    Submit.classList.add('btn-danger');
                },
                onSubmit: () => this.$deleteProducts()
            });
        },

        $deleteProducts: function () {
            if (this.$pending || !this.$productIds.length) {
                return Promise.resolve();
            }

            this.$pending = true;
            this.getButton('submit').disable();
            this.Loader.show();

            return Products.deleteChildren(this.$productIds).then(() => {
                this.close();
                this.fireEvent('deleted', [this, this.$productIds]);
                return QUI.getMessageHandler().then(Handler => {
                    Handler.addSuccess(Locale.get(lg, 'message.success.products.delete'));
                });
            }).catch(error => {
                this.$pending = false;
                this.Loader.hide();
                this.getButton('submit').enable();
                console.error(error);
            });
        }
    });
});
