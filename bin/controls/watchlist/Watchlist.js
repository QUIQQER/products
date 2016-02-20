/**
 * Merkliste für die HKL USED Homepage
 *
 * @module package/quiqqer/products/bin/controls/watchlist/Watchlist
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/controls/loader/Loader
 * @require qui/controls/windows/Confirm
 * @require qui/utils/Functions
 * @require Locale
 * @require css!package/machines/bin/site/controls/Watchlist.css
 *
 * @event onLoad [this]
 */
define('package/quiqqer/products/bin/controls/watchlist/Watchlist', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Select',
    'qui/controls/loader/Loader',
    'qui/controls/windows/Confirm',
    'qui/controls/windows/Popup',
    'qui/utils/Functions',
    'qui/utils/System',
    'Locale',

    'css!package/quiqqer/products/bin/controls/watchlist/Watchlist.css'

], function (QUI, QUIControl, QUIButton, QUISelect, QUILoader, QUIConfirm, QUIPopup, QUIFunctionUtils, QUISystemUtils, QUILocale) {

    "use strict";

    var lg = 'quiqqer/products';


    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/watchlist/Watchlist',

        Binds: [
            'refresh',
            'download',
            'setViewBricks',
            'setViewList',
            'setViewInfo',
            '$onInject',
            '$onResize',
            '$buttonViewClick',
            '$onMachineError'
        ],

        options: {
            showLoader: true
        },

        initialize: function (options) {

            this.parent(options);

            this.Loader    = new QUILoader();
            this.$Machines = null;
            this.$Buttons  = null;

            this.$machineControls = [];
            this.$__type          = '';

            this.$ButtonInfoView   = null;
            this.$ButtonListView   = null;
            this.$ButtonBricksView = null;

            this.addEvents({
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        create: function () {

            this.$Elm = new Element('div', {
                'class' : 'hklused-control-watchlist',
                html    : '<div class="hklused-control-watchlist-warning"></div>' +
                          '<div class="hklused-control-watchlist-buttons"></div>' +
                          '<div class="hklused-control-watchlist-machines"></div>',
                tabindex: '-1',
                styles  : {
                    outline: 'none'
                }
            });

            this.Loader.inject(this.$Elm);

            this.$Warning = this.$Elm.getElement(
                '.hklused-control-watchlist-warning'
            );

            this.$Warning.setStyle('display', 'none');
            this.$Warning.setStyles({
                display        : 'none',
                'margin-bottom': 10
            });

            if (typeof HKLUSED_SHOW_WARNING !== 'undefined') {
                this.$Warning.addClass(
                    'hklused-machines-internal-warning messages-message box message-error'
                );
                this.$Warning.set(
                    'html',
                    QUILocale.get('hklused/machines', 'internal.data.warning')
                );
                this.$Warning.setStyle('display', '');
            }

            this.$Buttons = this.$Elm.getElement(
                '.hklused-control-watchlist-buttons'
            );

            this.$Machines = this.$Elm.getElement(
                '.hklused-control-watchlist-machines'
            );

            new QUIButton({
                text     : QUILocale.get(lg, 'controls.watchlist.clearButton.text'),
                textimage: 'fa-trash fa',
                events   : {
                    click: this.clear
                }
            }).inject(this.$Buttons);

            new QUIButton({
                text     : QUILocale.get(lg, 'controls.watchlist.download'),
                textimage: 'fa fa-download',
                events   : {
                    click: this.download
                }
            }).inject(this.$Buttons);

            this.$ButtonBricksView = new QUIButton({
                icon  : 'fa fa-th',
                styles: {
                    'float' : 'right',
                    fontSize: 16
                },
                events: {
                    click   : this.$buttonViewClick,
                    onActive: this.setViewBricks
                }
            }).inject(this.$Buttons);

            this.$ButtonListView = new QUIButton({
                icon  : 'fa fa-align-justify',
                styles: {
                    'float' : 'right',
                    fontSize: 16
                },
                events: {
                    click   : this.$buttonViewClick,
                    onActive: this.setViewList
                }
            }).inject(this.$Buttons);

            this.$ButtonInfoView = new QUIButton({
                icon  : 'fa fa-th-list',
                styles: {
                    'float' : 'right',
                    fontSize: 16
                },
                events: {
                    click   : this.$buttonViewClick,
                    onActive: this.setViewInfo
                }
            }).inject(this.$Buttons);

            this.$ButtonBricksView.getElm().addClass('hklused-control-watchlist-view');
            this.$ButtonListView.getElm().addClass('hklused-control-watchlist-view');
            this.$ButtonInfoView.getElm().addClass('hklused-control-watchlist-view');

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {

            var self = this;

            this.refresh().then(function () {
                require([
                    'package/hklused/machines/bin/site/Watchlist'
                ], function (Watchlist) {

                    Watchlist.addEvent('onClear', self.refresh);
                    Watchlist.addEvent('refresh', self.refresh);

                    self.$__resize = QUIFunctionUtils.debounce(self.$onResize, 100);

                    window.addEvent('resize', self.$__resize);

                    self.fireEvent('load', [self]);
                });
            });
        },

        /**
         * event : window resize
         */
        $onResize: function () {
            var type = 'desktop',
                x    = window.getSize().x;

            if (x < 767) {
                type = 'mobile';
            }

            if (type == this.$__type) {
                return;
            }

            if (type === 'mobile') {
                this.$buttonViewClick(this.$ButtonInfoView);
            }

            // resize
            for (var i = 0, len = this.$machineControls.length; i < len; i++) {
                this.$machineControls[i].resize();
            }
        },

        /**
         * Refresh the display
         *
         * @return Promise
         */
        refresh: function () {

            var self = this;

            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            this.$Machines.set('html', '');

            return new Promise(function (resolve, reject) {

                require([
                    'package/hklused/machines/bin/site/Watchlist',
                    'package/hklused/machines/bin/site/controls/MachineDisplay'
                ], function (Watchlist, MachineDisplay) {

                    var i, len;
                    var machines = Watchlist.getMachineIds();

                    for (i = 0, len = machines.length; i < len; i++) {

                        self.$machineControls.push(
                            new MachineDisplay({
                                mid          : machines[i],
                                isOnWatchList: true,
                                events       : {
                                    onError: self.$onMachineError
                                }
                            }).inject(self.$Machines)
                        );
                    }

                    if (!machines.length) {
                        self.getElm().set(
                            'html',
                            QUILocale.get(lg, 'message.watchlist.no.entry')
                        );
                    }

                    self.$ButtonInfoView.click();

                    if (self.getAttribute('showLoader')) {
                        self.Loader.hide();
                    }

                    resolve();

                }, reject);
            });
        },

        /**
         * Create a purchase window
         */
        createPurchaseWindow: function () {

            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            return new Promise(function (resolve, reject) {

                require([
                    'package/hklused/machines/bin/site/Watchlist'
                ], function (Watchlist) {

                    if (!Watchlist.getMachines().length) {

                        reject(
                            QUILocale.get(lg, 'message.watchlist.no.machines')
                        );

                        return;
                    }

                    require([
                        'package/hklused/machines/bin/site/controls/PurchaseWindow'
                    ], function (PurchaseWindow) {

                        resolve(new PurchaseWindow({
                            machines: Watchlist.getMachineIds()
                        }));

                    }, function () {
                        reject(
                            QUILocale.get(lg, 'exception.purchase.unknown.error')
                        );
                    });
                });
            });
        },

        /**
         * opens the clear dialog
         */
        clear: function () {

            new QUIConfirm({
                icon         : 'fa fa-trash',
                title        : QUILocale.get(lg, 'controls.watchlist.clear.window.title'),
                text         : QUILocale.get(lg, 'controls.watchlist.clear.window.text'),
                texticon     : 'fa fa-trash',
                information  : QUILocale.get(lg, 'controls.watchlist.clear.window.information'),
                maxHeight    : 300,
                maxWidth     : 450,
                cancel_button: {
                    text     : QUILocale.get(lg, 'controls.watchlist.clear.window.cancel'),
                    textimage: 'fa fa-remove'
                },
                ok_button    : {
                    text     : QUILocale.get(lg, 'controls.watchlist.clear.window.submit'),
                    textimage: 'fa fa-trash'
                },
                events       : {
                    onSubmit: function (Win) {

                        Win.Loader.show();

                        require([
                            'package/hklused/machines/bin/site/Watchlist'
                        ], function (Watchlist) {

                            Watchlist.clear();
                            Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Download der Merkliste
         */
        download: function () {

            var self = this;

            new QUIPopup({
                icon           : 'fa fa-download',
                title          : QUILocale.get(lg, 'controls.watchlist.download'),
                closeButtonText: QUILocale.get(lg, 'controls.watchlist.btn.close'),
                maxHeight      : 300,
                maxWidth       : 450,
                events         : {
                    onOpen: function (Win) {

                        var Content = Win.getContent();

                        new QUIButton({
                            text  : QUILocale.get(lg, 'controls.watchlist.pricelist.download'),
                            icon  : 'fa fa-download',
                            styles: {
                                clear  : 'both',
                                'float': 'left',
                                width  : '100%'
                            },
                            events: {
                                onClick: function () {
                                    self.$download('pricelist', Win);
                                }
                            }
                        }).inject(Content);


                        new QUIButton({
                            text  : QUILocale.get(lg, 'controls.watchlist.detaillist.download'),
                            icon  : 'fa fa-download',
                            styles: {
                                clear  : 'both',
                                'float': 'left',
                                margin : '20px 0 0 0',
                                width  : '100%'
                            },
                            events: {
                                onClick: function () {
                                    self.$download('box', Win);
                                }
                            }
                        }).inject(Content);

                    }
                }
            }).open();
        },

        /**
         * internal download
         *
         * @param {String} type
         * @param {Object} Win - qui/controls/windows/Window
         */
        $download: function (type, Win) {
            var format = 'pricelist';

            if (type === 'box') {
                format = 'box';
            }

            var ids = JSON.encode(window.Watchlist.getMachineIds()),
                ios = QUISystemUtils.iOSversion();

            if (ios && ios[0]) {
                require(['qui/utils/Elements'], function (ElementUtils) {
                    var Link = new Element('a', {
                        href: URL_OPT_DIR + 'hklused/exportPdf/bin/pdf.php?' +
                              'machineIds=' + ids +
                              '&format=' + format +
                              '&lang=' + QUILocale.getCurrent()
                    });

                    ElementUtils.simulateEvent(Link, 'click');
                });

                return;
            }


            var iframeId = Math.floor(Date.now() / 1000);

            Win.Loader.show();
            Win.setAttribute('frameId', iframeId);

            var Frame = new Element('iframe', {
                id    : 'download-iframe-' + iframeId,
                styles: {
                    left    : -1000,
                    height  : 10,
                    position: 'absolute',
                    top     : -1000,
                    width   : 10
                }
            }).inject(document.body);

            Frame.set({
                'src'          : URL_OPT_DIR + 'hklused/exportPdf/bin/pdf.php?' +
                                 'machineIds=' + ids +
                                 '&format=' + format +
                                 '&lang=' + QUILocale.getCurrent() +
                                 '&iFrameId=' + iframeId,
                'data-iframeid': iframeId
            });
        },

        /**
         * event : click at view button
         *
         * @param {Object} Button - qui/controls/buttons/Button
         */
        $buttonViewClick: function (Button) {

            this.$ButtonInfoView.setNormal();
            this.$ButtonListView.setNormal();
            this.$ButtonBricksView.setNormal();

            Button.setActive();
        },

        /**
         * Set the view to brick view
         */
        setViewBricks: function () {

            for (var i = 0, len = this.$machineControls.length; i < len; i++) {
                this.$machineControls[i].brickView();
            }
        },

        /**
         * Set the view to list view
         */
        setViewList: function () {
            for (var i = 0, len = this.$machineControls.length; i < len; i++) {
                this.$machineControls[i].listView();
            }
        },

        /**
         * Set the view to normal info view
         */
        setViewInfo: function () {
            for (var i = 0, len = this.$machineControls.length; i < len; i++) {
                this.$machineControls[i].infoView();
            }
        },

        /**
         * event : on control destroy
         */
        $onDestroy: function () {
            if (typeof window.Watchlist !== 'undefined') {
                window.Watchlist.removeEvent('onClear', this.refresh);
                window.Watchlist.removeEvent('refresh', this.refresh);
            }

            if (typeof this.$__resize === 'function') {
                window.removeEvent('resize', this.$__resize);
            }
        },

        /**
         * event : on machine error
         * removed the machien from the list
         */
        $onMachineError: function (Machine) {
            require([
                'package/hklused/machines/bin/site/Watchlist'
            ], function (Watchlist) {

                Watchlist.removeMachine(
                    Machine.getAttribute('mid')
                );

            });
        }
    });
});
