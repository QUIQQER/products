/**
 * Add to Watchlist button
 *
 * @module package/quiqqer/products/bin/controls/watchlist/ButtonAdd
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/Watchlist
 */
define('package/quiqqer/products/bin/controls/frontend/watchlist/ButtonAdd', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/Watchlist'

], function (QUI, QUIControl, Watchlist) {

    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/watchlist/ButtonAdd',

        Binds: [
            '$onImport',
            '$onInject',
            '$addProductToWatchlist'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Text  = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var Elm = this.getElm(),
                pid = Elm.get('data-pid');

            if (!pid || pid === '') {
                return;
            }

            this.setAttribute('productId', pid);

            this.$Input = Elm.getElement('input');
            this.$Text  = Elm.getElement('.text');

            Elm.addEvent('click', this.$addProductToWatchlist);
            Elm.disabled = false;
        },

        /**
         * event: on inject
         */
        $onInject: function () {

        },

        /**
         * add the product to the watchlist
         */
        $addProductToWatchlist: function () {
            this.getElm().disabled = true;

            this.$Text.setStyles({
                visibility: 'hidden'
            });

            var self  = this,
                count = 0,
                size  = this.getElm().getSize();

            if (this.$Input) {
                this.$Input.setStyles({
                    opacity   : 0,
                    visibility: 'hidden'
                });

                count = this.$Input.value.toInt();
            }

            if (!count) {
                count = 0;
            }

            var Loader = new Element('div', {
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    fontSize  : (size.y / 3).round(),
                    height    : '100%',
                    left      : 0,
                    lineHeight: size.y,
                    position  : 'absolute',
                    textAlign : 'center',
                    top       : 0,
                    width     : '100%'
                }
            }).inject(this.getElm());

            Watchlist.addProduct(
                this.getAttribute('productId'),
                count
            ).then(function () {
                var Span = Loader.getElement('span');

                Span.removeClass('fa-spinner');
                Span.removeClass('fa-spin');

                Span.addClass('success');
                Span.addClass('fa-check');

                (function () {
                    Loader.destroy();

                    self.getElm().disabled = false;

                    if (self.$Input) {

                        self.$Input.setStyle('visibility', null);

                        moofx(self.$Input).animate({
                            opacity: 1
                        });
                    }

                    self.$Text.setStyle('visibility', null);

                    moofx(self.$Text).animate({
                        opacity: 1
                    });

                }).delay(1000);

            }.bind(this));
        }
    });
});
