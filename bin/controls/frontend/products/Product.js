/**
 * Product view
 * Display a product in the content
 */
define('package/quiqqer/products/bin/controls/frontend/products/Product', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/utils/Elements',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Stats'

], function (QUI, QUIControl, QUIElementUtils, Products, Categories, Piwik) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/frontend/products/Product',

        Binds: [
            'nextTab',
            'prevTab',
            'resize',
            '$onInject',
            '$onImport',
            '$tabClick',
            'getProductId',
            'isBuyable'
        ],

        options: {
            closeable: false,
            productId: false,
            galleryLoader: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$TabContainer = null;
            this.$fieldHashes = {};
            this.$availableHashes = {};

            this.$Tabbar = null;
            this.$Touch = null;
            this.$Price = null;
            this.$Gallery = null;

            this.$Next = null;
            this.$Prev = null;

            this.$tabs = null;
            this.$isTouch = !!('ontouchstart' in window);

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });

            QUI.addEvent('resize', this.resize);
        },

        /**
         * Control resize
         * - tab resize and tab scroll buttons
         */
        resize: function () {
            if (!this.$Elm) {
                return;
            }

            if (this.$Next && this.$Prev && !this.$isTouch) {
                const size = this.$TabContainer.getSize();
                const scrollSize = this.$TabContainer.getScrollSize();

                // show scroll buttons
                if (size.x < scrollSize.x) {
                    this.$Next.setStyle('display', 'inline');
                    this.$Prev.setStyle('display', 'inline');
                    this.$TabContainer.setStyle('width', 'calc(100% - 40px)');
                    return;
                }

                this.$Next.setStyle('display', null);
                this.$Prev.setStyle('display', null);
                this.$TabContainer.setStyle('width', null);
            }
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            const self = this,
                productId = this.getAttribute('productId');

            this.$Product = Products.get(productId);

            return new Promise(function (resolve) {
                require(['Ajax'], function (QUIAjax) {
                    QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getProduct', function (result) {
                        const Container = self.create(),
                            Helper = new Element('div', {
                                html: result.html
                            });

                        self.$fieldHashes = result.fieldHashes;
                        self.$availableHashes = result.availableHashes;

                        Container.set('data-qui', self.getType());
                        Container.set('data-productid', productId);
                        Container.className = 'quiqqer-products-product';

                        document.title = result.title;

                        if (result.seoTitle) {
                            document.title = result.seoTitle;
                        }

                        Container.set(
                            'html',
                            result.css +
                            Helper.getChildren('[data-productid]').get('html')
                        );

                        Container.setStyle('margin', 0);

                        let Article = Container.getElement('article');

                        if (!Article) {
                            Article = new Element('div');
                        }

                        if (Article.getChildren('header')) {
                            Article.getChildren('header').setStyle('padding-right', 40);
                        }

                        Article.setStyles({
                            padding: 0
                        });

                        new Element('div', {
                            'class': 'product-close-button',
                            html: '<span class="fa fa-close"></span>',
                            events: {
                                click: function () {
                                    document.title = QUIQQER.title;
                                    self.fireEvent('close');
                                }
                            }
                        }).inject(Container);

                        self.$onImport().then(resolve);
                    }, {
                        'package': 'quiqqer/products',
                        productId: productId,
                        project: JSON.encode(QUIQQER_PROJECT),
                        siteId: QUIQQER_SITE.id
                    });
                });
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            return new Promise(function (resolve) {
                const self = this,
                    Elm = this.getElm(),
                    productId = Elm.get('data-productid');

                this.setAttribute('productId', productId);

                Products.addToVisited(this.getAttribute('productId'));

                // render
                this.$Next = Elm.getElement('.product-data-more-next');
                this.$Prev = Elm.getElement('.product-data-more-prev');

                this.$TabContainer = Elm.getElement('.product-data-more-tabsContainer');
                this.$Tabbar = Elm.getElement('.product-data-more-tabs');

                if (this.$Next) {
                    this.$Next.addEvent('click', this.nextTab);
                }

                if (this.$Prev) {
                    this.$Prev.addEvent('click', this.prevTab);
                }

                QUI.parse(Elm).then(function () {
                    // price
                    const Price = Elm.getElement('.product-data-price-main .qui-products-price-display'),
                        Gallery = Elm.getElement('.quiqqer-gallery-slider');

                    if (Gallery) {
                        this.$Gallery = QUI.Controls.getById(Gallery.get('data-quiid'));
                    }

                    if (this.$Gallery && self.getAttribute('galleryLoader') === false) {
                        this.$Gallery.Loader.hide();
                    }

                    if (Price) {
                        this.$Price = QUI.Controls.getById(Price.get('data-quiid'));
                    }

                    // field events
                    const fields = this.getFieldControls();

                    fields.each(function (Control) {
                        Control.addEvent('onChange', function () {
                            self.calcPrice();
                        });
                    });

                    this.$initTabEvents();
                    this.resize();
                    this.fireEvent('load');
                    resolve();
                }.bind(this));
            }.bind(this));
        },

        /**
         * set and initialize all tab events for the detail tabs
         */
        $initTabEvents: function () {
            const Elm = this.getElm();

            this.$TabContainer = Elm.getElement('.product-data-more-tabsContainer');
            this.$Tabbar = Elm.getElement('.product-data-more-tabs');
            this.$Sheets = Elm.getElement('.product-data-more-sheets');

            if (!this.$Tabbar) {
                return;
            }

            this.$tabs = this.$Tabbar.getElements('.product-data-more-tabs-tab');

            this.$tabs.addEvents({
                mouseenter: function () {
                    this.addClass('hover');
                },
                mouseleave: function () {
                    this.removeClass('hover');
                },
                click: this.$tabClick
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

            if (this.$isTouch) {
                this.$TabContainer.setStyle('overflowX', 'auto');
            }


            // get preview images
            const rowClick = function (event) {
                const Target = event.target;

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

            const images = Elm.getElements(
                '.product-data-files-table-preview [data-zoom="1"]'
            );

            for (let i = 0, len = images.length; i < len; i++) {
                images[i].getParent('tr').addEvent('click', rowClick);
                images[i].getParent('tr').setStyle('cursor', 'pointer');
            }

            if (this.$tabs.length) {
                this.$tabClick({
                    target: this.$tabs[0]
                });
            }
        },

        /**
         * Return all field controls in the product
         *
         * @return {Array}
         */
        getFieldControls: function () {
            const controls = QUI.Controls.getControlsInElement(this.getElm());

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
            const self = this,
                fields = this.getFieldControls(),
                fieldData = [];

            for (let i = 0, len = fields.length; i < len; i++) {
                fieldData.push({
                    fieldId: fields[i].getFieldId(),
                    value: fields[i].getValue()
                });
            }

            if (!self.$Price) {
                return Promise.resolve();
            }

            return Products.calcPrice(this.getAttribute('productId'), fieldData).then(function (result) {
                if (result.price_is_minimal) {
                    self.$Price.enableMinimalPrice();
                } else {
                    self.$Price.disableMinimalPrice();
                }

                self.$Price.setPriceDisplay(result.price_display);
            }).catch(function (err) {
                console.error(err);
            });
        },

        /**
         * Activate the next tab
         *
         * @return {Promise}
         */
        nextTab: function () {
            const Active = this.$Tabbar.getElement('[aria-selected="true"]');
            let Next = Active.getNext();

            if (!Next) {
                Next = this.$Tabbar.getFirst();
            }

            Active.set('aria-selected', 'false');
            Active.removeClass('active');

            Next.set('aria-selected', 'true');
            Next.addClass('active');

            this.$scrollToTab(Next);

            const ActiveSheet = this.$getSheet(Active.get('aria-controls'));
            const NextSheet = this.$getSheet(Next.get('aria-controls'));

            return Promise.all([
                this.$hideTabToLeft(ActiveSheet),
                this.$showFromRight(NextSheet)
            ]);
        },

        /**
         * Activate the previous tab
         *
         * @return {Promise}
         */
        prevTab: function () {
            const Active = this.$Tabbar.getElement('[aria-selected="true"]');
            let Prev = Active.getPrevious();

            if (!Prev) {
                Prev = this.$Tabbar.getLast();
            }

            Active.set('aria-selected', 'false');
            Active.removeClass('active');

            Prev.set('aria-selected', 'true');
            Prev.addClass('active');

            this.$scrollToTab(Prev);

            const ActiveSheet = this.$getSheet(Active.get('aria-controls'));
            const PrevSheet = this.$getSheet(Prev.get('aria-controls'));

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
                    left: -50,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Node.setStyle('display', 'none');
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
                    left: 50,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Node.setStyle('display', 'none');
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
                left: -50,
                opacity: 0
            });

            let height = 310,
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
                    left: 0,
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
                left: 50,
                opacity: 0
            });

            let height = 310,
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
                    left: 0,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: resolve
                });
            });
        },

        /**
         * Scroll to the, shows the tab in the tabbar
         *
         * @param {HTMLLIElement} Tab
         */
        $scrollToTab: function (Tab) {
            new Fx.Scroll(this.$TabContainer).toElement(Tab);
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
            let Target = event.target;

            if ("stop" in event) {
                event.stop();
            }

            if (Target.nodeName === 'A') {
                Target = Target.getParent();
            }


            const TargetSheet = this.$getSheet(Target.get('aria-controls'));

            let Active = this.$Tabbar.getElement('[aria-selected="true"]'),
                ActiveSheet = null,
                activeIndex = 0,
                targetIndex = 0;

            if (Active) {
                ActiveSheet = this.$getSheet(Active.get('aria-controls'));

                activeIndex = QUIElementUtils.getChildIndex(Active);
                targetIndex = QUIElementUtils.getChildIndex(Target);

                Active.set('aria-selected', 'false');
                Active.removeClass('active');
            }

            Target.set('aria-selected', 'true');
            Target.addClass('active');

            this.$scrollToTab(Target);

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
        },

        /**
         * Get ID of the current product
         *
         * @return {Number|Boolean} - May be false if no product is explicitly selected / set
         */
        getProductId: function () {
            return this.getAttribute('productId');
        },

        /**
         * Indicates if this product can be bought in its current configuration.
         *
         * @return {boolean}
         */
        isBuyable: function () {
            return true;
        }
    });
});
