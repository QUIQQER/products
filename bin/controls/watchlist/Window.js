/**
 * @module package/quiqqer/products/bin/controls/watchlist/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require package/hklused/machines/bin/site/controls/Watchlist
 * @require Locale
 */
define('package/quiqqer/products/bin/controls/watchlist/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/watchlist/Watchlist',
    'Locale'

], function (QUI, QUIControl, QUIConfirm, WatchlistControl, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/watchlist/Window',

        Binds: [
            '$onOpen',
            '$onClose',
            '$onSubmit'
        ],

        initialize: function (options) {
            // default
            this.setAttributes({
                title        : QUILocale.get(lg, 'controls.watchlist.window.title'),
                icon         : 'fa fa-file-text-o',
                maxHeight    : 900,
                maxWidth     : 900,
                autoclose    : false,
                texticon     : false,
                cancel_button: {
                    text     : QUILocale.get(lg, 'controls.watchlist.window.closeBtn.text'),
                    textimage: 'fa fa-remove'
                },
                ok_button    : {
                    text     : QUILocale.get(lg, 'controls.watchlist.window.purchaseBtn.text'),
                    textimage: 'fa fa-shopping-cart'
                }
            });

            this.parent(options);

            this.$List = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onClose : this.$onClose,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * window event : on open
         */
        $onOpen: function () {
            var self = this;

            this.Loader.show();
            this.getContent().set('html', '');


            var Buttons = this.getElm().getElement('.qui-window-popup-buttons');

            Buttons.addClass('shadow-box');

            Buttons.getElements('button').setStyles({
                maxWidth: '45%'
            });

            this.$List = new WatchlistControl({
                showLoader: false,
                events    : {
                    onLoad: function () {
                        self.Loader.hide();
                        self.$List.setAttribute('showLoader', false);
                    }
                }
            }).inject(this.getContent());
        },

        /**
         * window event : on submit
         */
        $onSubmit: function () {

            if (!this.$List) {
                return;
            }

            var self = this;

            this.Loader.show();

            this.close().then(function () {
                return self.$List.createPurchaseWindow();

            }).then(function (PurchaseWindow) {
                PurchaseWindow.open();

            }).catch(function (message) {

                QUI.getMessageHandler().then(function (MH) {
                    MH.addError(message);
                });

                self.Loader.hide();
            });
        },

        /**
         * window event : on close
         */
        $onClose: function () {
            if (this.$List) {
                this.$List.destroy();
            }
        }
    });
});
