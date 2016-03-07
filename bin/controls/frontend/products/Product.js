/**
 * Product view
 * Display a product
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/products/bin/Products
 */
define('package/quiqqer/products/bin/controls/frontend/products/Product', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/Products',
    URL_OPT_DIR + 'bin/hammerjs/hammer.min.js'

], function (QUI, QUIControl, QUIElementUtils, Products, Hammer) {

    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/frontend/products/Product',

        Binds: [
            '$onInject',
            '$onImport',
            '$tabClick'
        ],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$TabContainer = null;

            this.$Tabbar = null;
            this.$tabs   = null;
            this.$Touch  = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Product = Products.get(this.getAttribute('productId'));
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            this.$Tabbar       = Elm.getElement('.product-data-more-tabs');
            this.$Sheets       = Elm.getElement('.product-data-more-sheets');
            this.$TabContainer = Elm.getElement('.product-data-more');

            this.$tabs = this.$Tabbar.getElements('.product-data-more-tabs-tab');

            this.$tabs.addEvents({
                mouseenter: function () {
                    this.addClass('hover');
                },
                mouseleave: function () {
                    this.removeClass('hover');
                },
                click     : this.$tabClick
            });

            this.$Touch = new Hammer(this.$TabContainer);

            this.$Touch.on('swipe', function (ev) {
                if (ev.offsetDirection == 4) {
                    this.prevTab();
                    return;
                }

                if (ev.offsetDirection == 2) {
                    this.nextTab();
                }
            }.bind(this));
        },

        /**
         * Activate the next tab
         */
        nextTab: function () {
            var Active = this.$Tabbar.getElement('.active');
            var Next   = Active.getNext();

            if (!Next) {
                Next = this.$Tabbar.getFirst();
            }

            Active.removeClass('active');
            Next.addClass('active');

            var ActiveSheet = this.$getSheet(Active.get('data-type'));
            var NextSheet   = this.$getSheet(Next.get('data-type'));

            return Promise.all([
                this.$hideTabToLeft(ActiveSheet),
                this.$showFromRight(NextSheet)
            ]);
        },

        /**
         * Activate the previous tab
         */
        prevTab: function () {
            var Active = this.$Tabbar.getElement('.active');
            var Prev   = Active.getPrevious();

            if (!Prev) {
                Prev = this.$Tabbar.getLast();
            }

            Active.removeClass('active');
            Prev.addClass('active');

            var ActiveSheet = this.$getSheet(Active.get('data-type'));
            var PrevSheet   = this.$getSheet(Prev.get('data-type'));

            return Promise.all([
                this.$hideTabToRight(ActiveSheet),
                this.$showFromLeft(PrevSheet)
            ]);
        },

        /**
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $hideTabToLeft: function (Node) {
            if (!Node) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    left   : -50,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Node.setStyles('display', 'none');
                        resolve();
                    }
                });
            });
        },

        /**
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $hideTabToRight: function (Node) {
            if (!Node) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    left   : 50,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Node.setStyles('display', 'none');
                        resolve();
                    }
                });
            });
        },

        /**
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $showFromLeft: function (Node) {
            if (!Node) {
                return Promise.resolve();
            }

            Node.setStyles({
                display: 'inline',
                left   : -50,
                opacity: 0
            });

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: resolve
                });
            });
        },

        /**
         *
         * @param {HTMLDivElement} Node
         * @returns {Promise}
         */
        $showFromRight: function (Node) {
            if (!Node) {
                return Promise.resolve();
            }

            Node.setStyles({
                display: 'inline',
                left   : 50,
                opacity: 0
            });

            return new Promise(function (resolve) {
                moofx(Node).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: resolve
                });
            });
        },

        /**
         * Return the sheet of the name
         *
         * @param name
         * @returns {HTMLDivElement|null}
         */
        $getSheet: function (name) {
            return this.$Sheets.getElement('[data-type="' + name + '"]');
        },

        /**
         * event: tab click
         */
        $tabClick: function (event) {
            var Target      = event.target,
                type        = Target.get('data-type'),
                TargetSheet = this.$getSheet(type);

            var Active      = this.$Tabbar.getElement('.active'),
                ActiveSheet = this.$getSheet(Active.get('data-type'));

            var activeIndex = QUIElementUtils.getChildIndex(Active),
                targetIndex = QUIElementUtils.getChildIndex(Target);

            Active.removeClass('active');
            Target.addClass('active');

            if (activeIndex < targetIndex) {
                return Promise.all([
                    this.$hideTabToLeft(TargetSheet),
                    this.$showFromRight(ActiveSheet)
                ]);
            }

            return Promise.all([
                this.$hideTabToRight(TargetSheet),
                this.$showFromLeft(ActiveSheet)
            ]);
        }
    });
});
