/**
 * @module package/quiqqer/products/bin/controls/products/OverwriteableFieldList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldListWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldList',
    'Ajax',
    'Locale'

], function (QUI, QUIConfirm, OverwriteableFieldList, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/OverwriteableFieldListWindow',

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

            this.setAttribute('icon', 'fa fa-exchange');
            this.setAttribute(
                'title',
                QUILocale.get('quiqqer/products', 'variants.OverwriteableFieldList.window.title')
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
            var self = this;

            this.Loader.show();
            this.getContent().set('html', '');
            this.getContent().setStyles({
                display         : 'flex',
                'flex-direction': 'column'
            });

            new Element('div', {
                html  : QUILocale.get('quiqqer/products', 'variants.OverwriteableFieldList.window.description'),
                styles: {
                    paddingBottom: 20
                }
            }).inject(this.getContent());

            var ListContainer = new Element('div', {
                styles: {
                    'flex-grow': 1
                }
            }).inject(this.getContent());

            this.$List = new OverwriteableFieldList({
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

            this.Loader.show();

            this.$List.save().then(function () {
                self.close();
            }).catch(function () {
                self.Loader.hide();
            });
        }
    });
});