/**
 * @module package/quiqqer/products/bin/controls/products/EditableInheritedFieldListWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldListWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldList',
    'Ajax',
    'Locale'

], function (QUI, QUIConfirm, Products, EditableFieldList, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldListWindow',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            productId: false,
            maxWidth : 800,
            maxHeight: 800,
            autoclose: false
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('icon', 'fa fa-exchange');
            this.setAttribute(
                'title',
                QUILocale.get('quiqqer/products', 'variants.EditableFieldList.window.title')
            );

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
            this.Loader.show();
            this.getContent().set('html', '');
            this.getContent().setStyles({
                display         : 'flex',
                'flex-direction': 'column'
            });

            new Element('div', {
                html  : QUILocale.get('quiqqer/products', 'variants.EditableFieldList.window.description'),
                styles: {
                    paddingBottom: 20
                }
            }).inject(this.getContent());

            new Element('label', {
                html  : '<input type="checkbox" name="reset-fields-to-global" /> ' +
                    QUILocale.get('quiqqer/products', 'variants.EditableFieldList.window.reset.to.global'),
                styles: {
                    cursor       : 'pointer',
                    paddingBottom: 20
                }
            }).inject(this.getContent());

            const ListContainer = new Element('div', {
                styles: {
                    'flex-grow': 1
                }
            }).inject(this.getContent());

            this.$List = new EditableFieldList({
                productId: this.getAttribute('productId'),
                events   : {
                    onLoad: () => {
                        this.Loader.hide();
                    }
                }
            }).inject(ListContainer);

            const Reset = this.getContent().getElement('[name="reset-fields-to-global"]');

            Reset.addEvent('change', () => {
                if (Reset.checked) {
                    this.$List.disable();
                    return;
                }

                this.$List.enable();
            });

            const Product = Products.get(this.getAttribute('productId'));

            Product.load().then((Product) => {
                let attributes = Product.getAttributes();

                if (attributes.inheritedVariantFields === false &&
                    attributes.editableVariantFields === false) {

                    Reset.checked = true;
                    this.$List.disable();
                }

                this.$List.resize();
            });
        },

        /**
         * event: on submit
         */
        $onSubmit: function () {
            const Reset = this.getContent().getElement('[name="reset-fields-to-global"]');

            this.Loader.show();

            if (Reset.checked) {
                const Product = Products.get(this.getAttribute('productId'));

                Product.resetInheritedFields().then(() => {
                    this.close();
                    this.fireEvent('save', [this]);
                }).catch(() => {
                    this.Loader.hide();
                });

                return;
            }

            this.$List.save().then(() => {
                this.close();
                this.fireEvent('save', [this]);
            }).catch(() => {
                this.Loader.hide();
            });
        }
    });
});