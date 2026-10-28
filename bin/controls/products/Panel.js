/**
 * Product management
 */
define('package/quiqqer/products/bin/controls/products/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'package/quiqqer/products/bin/controls/products/DeleteProductWindow',
    'qui/controls/contextmenu/Separator',
    'Locale',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/productsearch/bin/controls/products/search/Search',

    'css!package/quiqqer/products/bin/controls/products/Panel.css'

], function (QUI, QUIPanel, QUIButton, DeleteProductWindow, QUIMenuSeparator, QUILocale, Products, Search) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/Panel',

        Binds: [
            'refresh',
            'createChild',
            'deleteChild',
            'updateChild',
            '$onShow',
            '$onHide',
            '$onCreate',
            '$onResize',
            '$onInject'
        ],

        initialize: function (options) {
            this.setAttributes({
                icon : 'fa fa-shopping-bag',
                title: QUILocale.get(lg, 'products.panel.title')
            });

            this.parent(options);

            this.$Search = null;
            this.$ButtonAdd = null;
            this.$initialLoad = true;
            this.$currentSearch = '';

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize,
                onInject: this.$onInject,
                onShow  : this.$onShow,
                onHide  : this.$onHide
            });
        },

        /**
         * Return the embedded backend search, or null before injection.
         *
         * @return {Object|null}
         */
        getSearch: function () {
            return this.$Search;
        },

        /**
         * Refresh the product list.
         *
         * @return {Promise}
         */
        refresh: function () {
            this.parent();
            this.$setActionState(0);
            return this.$Search.search();
        },

        $onResize: function () {
            return this.$Search?.resize() ?? Promise.resolve();
        },

        $onCreate: function () {
            this.getElm().classList.add('quiqqer-products-panel');
            const Content = this.getContent();
            Content.classList.add('quiqqer-products-productPanel');
            Content.style.padding = '0';
            Content.style.visibility = 'hidden';
            Content.setAttribute('aria-busy', 'true');
            this.Loader.show();

            this.$ButtonAdd = new QUIButton({
                name: 'add',
                text: QUILocale.get(lg, 'category.panel.button.products.add'),
                textimage: 'fa fa-plus',
                events: {onClick: this.createChild}
            });
            this.$Actions = this.$createActions();
            this.$createToolbar();
            this.getButtonBar().getElm().style.visibility = 'hidden';
            QUI.fireEvent('quiqqerProductsPanelCreate', [this]);
        },

        $createActions: function () {
            const Actions = new QUIButton({
                name      : 'actions',
                text      : QUILocale.get(lg, 'btn.actions'),
                menuCorner: 'topRight',
                position: 'right'
            });

            Actions.appendChild({
                name    : 'edit',
                text    : QUILocale.get('quiqqer/core', 'edit'),
                icon    : 'fa fa-edit',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.updateChild(
                            this.$Search.getSelected()[0]
                        );
                    }
                }
            });

            Actions.appendChild({
                name    : 'copy',
                text    : QUILocale.get('quiqqer/core', 'copy'),
                icon    : 'fa fa-copy',
                disabled: true,
                events  : {
                    onClick: () => {
                        this.copyChild(
                            this.$Search.getSelected()[0]
                        );
                    }
                }
            });

            Actions.appendChild(
                new QUIMenuSeparator()
            );

            Actions.appendChild({
                name: 'toggleActive',
                text: QUILocale.get('quiqqer/core', 'activate'),
                icon: 'fa fa-check',
                disabled: true,
                events: {onClick: () => this.toggleActiveChildren()}
            });

            Actions.appendChild(
                new QUIMenuSeparator()
            );

            Actions.appendChild({
                name    : 'delete',
                text    : QUILocale.get('quiqqer/core', 'delete'),
                icon    : 'fa fa-trash',
                disabled: true,
                events  : {
                    onClick: (Btn) => {
                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        this.deleteChild(this.$Search.getSelected()).catch(function (Exception) {
                            if (Exception?.getType?.() === 'qui/controls/messages/Error') {
                                return;
                            }

                            console.error(Exception);
                        }).then(() => {
                            Btn.setAttribute('textimage', 'fa fa-trashcan');
                        });
                    }
                }
            });

            return Actions;
        },

        $createToolbar: function () {
            this.$CategoriesButton = new QUIButton({
                name: 'categories',
                text: QUILocale.get(lg, 'categories.panel.title'),
                events: {
                    onClick: () => this.$Search?.toggleCategories()
                }
            });
            const SearchButton = new QUIButton({
                name: 'search',
                events: {onClick: () => this.$submitSearch()}
            });
            this.$FilterButton = new QUIButton({
                name: 'filter',
                text: QUILocale.get(lg, 'quiqqer.products.control.search.title'),
                events: {onClick: () => this.$openFilters()}
            });

            [this.$CategoriesButton, SearchButton, this.$FilterButton].forEach(Button => this.addButton(Button));
            this.$prepareToolbarButton(this.$CategoriesButton, 'fa fa-sitemap');
            this.$prepareToolbarButton(SearchButton, 'fa fa-search');
            this.$prepareToolbarButton(this.$FilterButton, 'fa fa-filter');
            const FilterArrow = document.createElement('span');
            FilterArrow.className = 'qui-button-drop fa fa-chevron-down';
            FilterArrow.setAttribute('aria-hidden', 'true');
            this.$FilterButton.getElm().appendChild(FilterArrow);
            this.$CategoriesButton.getElm().setAttribute('aria-expanded', 'false');
            this.$FilterButton.getElm().setAttribute('aria-haspopup', 'dialog');
            this.$FilterButton.getElm().setAttribute('aria-expanded', 'false');
            this.$FilterButton.getElm().classList.add('quiqqer-products-toolbar-filter');

            const Separator = document.createElement('span');
            Separator.className = 'quiqqer-products-toolbar-separator';
            Separator.setAttribute('aria-hidden', 'true');
            this.$CategoriesButton.getElm().after(Separator);

            const SearchGroup = document.createElement('form');
            SearchGroup.className = 'quiqqer-products-toolbar-search';
            SearchGroup.setAttribute('role', 'search');
            SearchGroup.dataset.name = 'product-search';
            const searchLabel = QUILocale.get(lg, 'controls.products.search');
            this.$SearchInput = document.createElement('input');
            this.$SearchInput.type = 'search';
            this.$SearchInput.placeholder = searchLabel;
            this.$SearchInput.setAttribute('aria-label', searchLabel);
            this.$SearchInput.dataset.name = 'search-input';
            SearchGroup.appendChild(this.$SearchInput);
            const SearchElm = SearchButton.getElm();
            SearchElm.setAttribute('aria-label', searchLabel);
            SearchElm.before(SearchGroup);
            SearchGroup.appendChild(SearchElm);
            SearchGroup.addEventListener('submit', event => {
                event.preventDefault();
                this.$submitSearch();
            });
            this.$SearchInput.addEventListener('input', () => {
                if (!this.$SearchInput.value && this.$currentSearch) {
                    this.$submitSearch();
                }
            });

            const Toolbar = this.$CategoriesButton.getElm().parentElement;
            Toolbar.classList.add('quiqqer-products-toolbar');
            Toolbar.parentElement.classList.add('quiqqer-products-toolbar-viewport');
        },

        $prepareToolbarButton: function (Button, icon) {
            const Elm = Button.getElm();
            Elm.removeAttribute('tabindex');
            Elm.style.outline = '';
            const Icon = document.createElement('span');
            Icon.className = icon;
            Icon.setAttribute('aria-hidden', 'true');
            Elm.prepend(Icon);
        },

        $submitSearch: function () {
            if (!this.$Search) {
                return Promise.resolve();
            }

            this.$currentSearch = this.$SearchInput.value;
            this.$Search.setFreeText(this.$currentSearch);
            return this.refresh().catch(error => {
                this.Loader.hide();
                console.error(error);
            });
        },

        $openFilters: function () {
            if (!this.$Search) {
                return;
            }

            this.$FilterButton.getElm().setAttribute('aria-expanded', 'true');
            this.$Search.openFilters(this.$FilterButton.getElm()).catch(error => {
                this.$FilterButton.getElm().setAttribute('aria-expanded', 'false');
                console.error(error);
            });
        },

        $setActionState: function (count) {
            this.$Actions.getChildren().forEach(Action => {
                const name = Action.getAttribute('name');

                if (!['edit', 'copy', 'toggleActive', 'delete'].includes(name)) {
                    return;
                }

                if (name === 'toggleActive' && count > 0) {
                    const deactivate = this.$selectionIsActive();
                    Action.setAttribute('text', QUILocale.get('quiqqer/core', deactivate ? 'deactivate' : 'activate'));
                    Action.setAttribute('icon', deactivate ? 'fa fa-remove' : 'fa fa-check');
                }

                const enabled = ['edit', 'copy'].includes(name) ? count === 1 : count > 0;

                if (enabled) {
                    Action.enable();
                } else {
                    Action.disable();
                }
            });
        },

        $onInject: function () {
            const searchOptions = {
                context: 'products.panel',
                layout: 'panel',
                freeTextSearch: false,
                injectShow: false,
                gridOptions: {buttons: [this.$ButtonAdd, this.$Actions]},
                events: {
                    onClick: () => this.$setActionState(this.$Search.getSelected().length),
                    onDblClick: () => this.updateChild(this.$Search.getSelected()[0]),
                    onSearchBegin: () => {
                        this.$setActionState(0);
                        this.Loader.show();
                    },
                    onSearch: () => {
                        if (!this.$initialLoad) {
                            this.Loader.hide();
                        }
                    },
                    onSearchError: () => this.Loader.hide(),
                    onCategoriesToggle: (Search, visible) => {
                        this.$CategoriesButton.getElm().setAttribute('aria-expanded', String(visible));
                    },
                    onFiltersClose: () => {
                        const Button = this.$FilterButton.getElm();
                        Button.setAttribute('aria-expanded', 'false');
                    }
                }
            };

            QUI.fireEvent('quiqqerProductsPanelSearchCreate', [this, searchOptions]);
            this.$Search = new Search(searchOptions);
            this.$Search.inject(this.getContent());
            this.$Search.whenReady().then(() => {
                this.$CategoriesButton.getElm().setAttribute('aria-controls', this.$Search.getCategoriesId());
                QUI.fireEvent('quiqqerProductsPanelSearchReady', [this, this.$Search]);
                return this.refresh();
            }).then(() => {
                const showing = this.$Search.show();
                // A later grid resize can replace its animation without settling this promise.
                this.$finishInitialLoad();
                return showing;
            }).catch(error => {
                this.$finishInitialLoad();
                console.error(error);
            });
        },

        $finishInitialLoad: function () {
            this.$initialLoad = false;
            this.getContent().style.visibility = '';
            this.getContent().setAttribute('aria-busy', 'false');
            this.getButtonBar().getElm().style.visibility = '';
            this.Loader.hide();
        },

        $selectionIsActive: function () {
            const rows = this.$Search.getResult().getGrid().getSelectedData();
            return rows.length > 0 && rows.every(row => Number(row.active) === 1);
        },

        toggleActiveChildren: function () {
            if (this.$selectionIsActive()) {
                return this.deactivateChildren();
            }

            return this.activateChildren();
        },

        $onShow: function () {
            if (this.$initialLoad) {
                return;
            }

            this.$Search?.show().then(() => this.$Search.resize());
        },

        $onHide: function () {
            this.$Search?.hide();
        },

        /**
         * Opens the create child dialog
         */
        createChild: function () {
            this.Loader.show();

            require([
                'package/quiqqer/products/bin/controls/products/CreateProductWindow'
            ], (CreateProductWindow) => {
                const categories = this.$Search.getSelectedCategories();

                new CreateProductWindow({
                    categories: categories,
                    events    : {
                        onProductCreated: (Win, product) => {
                            this.refresh();
                            this.updateChild(product.id);
                        }
                    }
                }).open();

                this.Loader.hide();
            });
        },

        /**
         * Opens the product panel
         *
         * @param {Number} productId
         */
        updateChild: function (productId) {
            this.Loader.show();

            Products.openProduct(productId).then(() => {
                this.Loader.hide();
            });
        },

        /**
         * Activate all marked products
         */
        activateChildren: function () {
            this.Loader.show();

            return Products.activateChildren(
                this.$Search.getSelected()
            ).then(() => {
                this.Loader.hide();
                this.refresh();
            }).catch(() => {
                this.Loader.hide();
                this.refresh();
            });
        },

        /**
         * Deactivate all marked products
         */
        deactivateChildren: function () {
            this.Loader.show();

            return Products.deactivateChildren(
                this.$Search.getSelected()
            ).then(() => {
                this.Loader.hide();
                this.refresh();
            }).catch(() => {
                this.Loader.hide();
                this.refresh();
            });
        },

        /**
         * Opens the product panel
         *
         * @param {Number} productId
         */
        copyChild: function (productId) {
            return Products.copy(productId).then(newProductId => {
                if (newProductId === null) {
                    return;
                }

                this.Loader.show();
                return Products.openProduct(newProductId).then(() => this.refresh());
            }).finally(() => {
                this.Loader.hide();
            });
        },

        /**
         * Opens the delete dialog
         *
         * @param {Number|Array} productIds
         * @return {Promise}
         */
        deleteChild: function (productIds) {
            const ids = typeof productIds === 'number' ? [productIds] : productIds;

            if (!Array.isArray(ids) || !ids.length) {
                return Promise.reject(new Error('No products selected'));
            }

            return Products.getChildren(ids).then(products => {
                if (!products.length) {
                    throw new Error('No products found');
                }

                new DeleteProductWindow({
                    products: products,
                    events: {
                        onDeleted: () => this.refresh()
                    }
                }).open();
            });
        }
    });
});
