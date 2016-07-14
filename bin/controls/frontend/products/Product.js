/**
 * Product view
 * Display a product in the content
 *
 * @module package/quiqqer/products/bin/controls/frontend/products/Product
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/utils/Elements
 * @require package/quiqqer/products/bin/Products
 * @require URL_OPT_DIR + 'bin/hammerjs/hammer.min.js'
 */
define('package/quiqqer/products/bin/controls/frontend/products/Product', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/Products',
    URL_OPT_DIR + 'bin/hammerjs/hammer.min.js'

], function (QUI, QUIControl, QUIElementUtils, Products, Hammer) {

    "use strict";

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
            this.$Price  = null;

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
            var self = this,
                Elm  = this.getElm();

            this.setAttribute('productId', Elm.get('data-productid'));

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

            // calc tab height
            this.$Sheets.setStyles({
                overflow: 'hidden'
            });

            moofx(this.$Sheets).animate({
                height: this.$Sheets.getScrollSize().y + 10
            }, {
                duration: 200
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


            QUI.parse(Elm).then(function () {
                // price
                var Price = Elm.getElement('.quiqqer-price"');

                if (Price) {
                    this.$Price = QUI.Controls.getById(Price.get('data-quiid'));
                }

                // field events
                var fields = this.getFieldControls();

                fields.each(function (Control) {
                    Control.addEvent('onChange', function () {
                        self.calcPrice();
                    });
                });

                // get preview images
                var rowClick = function (event) {
                    var Target = event.target;

                    if (Target.nodeName === 'IMG') {
                        return;
                    }

                    if (Target.getParent('.product-data-files-table-preview')) {
                        return;
                    }

                    if (Target.hasClass('product-data-files-table-download')) {
                        return;
                    }

                    if (Target.getParent('.product-data-files-table-download')) {
                        return;
                    }

                    this.getElement('.product-data-files-table-preview img').click();
                };

                var images = Elm.getElements(
                    '.product-data-files-table-preview [data-zoom="1"]'
                );

                for (var i = 0, len = images.length; i < len; i++) {
                    images[i].getParent('tr').addEvent('click', rowClick);
                    images[i].getParent('tr').setStyle('cursor', 'pointer');
                }
            }.bind(this));
        },

        /**
         * Return all field controls in the product
         *
         * @return {Array}
         */
        getFieldControls: function () {
            var controls = QUI.Controls.getControlsInElement(this.getElm());

            return controls.filter(function (Control) {
                if (!('isField' in Control)) {
                    return false;
                }

                return Control.isField();
            });
        },

        /**
         * Calculate the product price
         *
         * @returns {Promise}
         */
        calcPrice: function () {
            var self      = this,
                fields    = this.getFieldControls(),
                fieldData = [];

            for (var i = 0, len = fields.length; i < len; i++) {
                fieldData.push({
                    fieldId: fields[i].getFieldId(),
                    value  : fields[i].getValue()
                });
            }

            if (!self.$Price) {
                return Promise.resolve();
            }

            return Products.calcPrice(this.getAttribute('productId'), fieldData).then(function (result) {

                console.log(result);

                self.$Price.setPrice(result.price, result.currency);
            });
        },

        /**
         * Activate the next tab
         */
        nextTab: function () {
            var Active = this.$Tabbar.getElement('[aria-selected="true"]');
            var Next   = Active.getNext();

            if (!Next) {
                Next = this.$Tabbar.getFirst();
            }

            Active.set('aria-selected', 'false');
            Active.removeClass('active');

            Next.set('aria-selected', 'true');
            Next.addClass('active');

            var ActiveSheet = this.$getSheet(Active.get('aria-controls'));
            var NextSheet   = this.$getSheet(Next.get('aria-controls'));

            return Promise.all([
                this.$hideTabToLeft(ActiveSheet),
                this.$showFromRight(NextSheet)
            ]);
        },

        /**
         * Activate the previous tab
         */
        prevTab: function () {
            var Active = this.$Tabbar.getElement('[aria-selected="true"]');
            var Prev   = Active.getPrevious();

            if (!Prev) {
                Prev = this.$Tabbar.getLast();
            }

            Active.set('aria-selected', 'false');
            Active.removeClass('active');

            Prev.set('aria-selected', 'true');
            Prev.addClass('active');

            var ActiveSheet = this.$getSheet(Active.get('aria-controls'));
            var PrevSheet   = this.$getSheet(Prev.get('aria-controls'));

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

            var height     = 310,
                nodeHeight = Node.getSize().y + 10;

            if (nodeHeight > height) {
                height = nodeHeight;
            }

            moofx(this.$Sheets).animate({
                height: height
            }, {
                duration: 200
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

            var height     = 310,
                nodeHeight = Node.getSize().y + 10;

            if (nodeHeight > height) {
                height = nodeHeight;
            }

            moofx(this.$Sheets).animate({
                height: height
            }, {
                duration: 200
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
            return document.id(name);
        },

        /**
         * event: tab click
         */
        $tabClick: function (event) {
            var Target = event.target;

            event.stop();

            if (Target.nodeName == 'A') {
                Target = Target.getParent();
            }


            var TargetSheet = this.$getSheet(Target.get('aria-controls'));

            var Active      = this.$Tabbar.getElement('[aria-selected="true"]'),
                ActiveSheet = this.$getSheet(Active.get('aria-controls'));

            var activeIndex = QUIElementUtils.getChildIndex(Active),
                targetIndex = QUIElementUtils.getChildIndex(Target);

            Active.set('aria-selected', 'false');
            Active.removeClass('active');

            Target.set('aria-selected', 'true');
            Target.addClass('active');

            if (activeIndex < targetIndex) {
                return Promise.all([
                    this.$hideTabToLeft(ActiveSheet),
                    this.$showFromRight(TargetSheet)
                ]);
            }

            return Promise.all([
                this.$hideTabToRight(ActiveSheet),
                this.$showFromLeft(TargetSheet)
            ]);
        }
    });
});
