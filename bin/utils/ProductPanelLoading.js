/**
 * Keep the product panel visible while masking changes in its form area.
 */
define('package/quiqqer/products/bin/utils/ProductPanelLoading', [
    'Locale',
    'css!package/quiqqer/products/bin/utils/ProductPanelLoading.css'
], function (Locale) {
    'use strict';

    return class ProductPanelLoading {
        constructor(Panel) {
            this.Panel = Panel;
            this.Loader = Panel.Loader;
            this.Elm = this.Loader.getElm();
            this.Root = Panel.getElm();
            this.Content = Panel.getContent();
            this.disabled = new Map();
            this.active = false;
            this.destroyed = false;
            this.saving = false;
            this.Root.classList.add('quiqqer-product-panel');
            this.Elm.classList.add('quiqqer-product-panel-loader');
            this.Elm.setAttribute('data-name', 'productLoader');
            this.Elm.style.opacity = 0;
            this.Elm.style.background = 'transparent';
            this.Loader.setAttribute('opacity', 1);
            this.Loader.setAttribute('styles', {background: 'transparent'});

            this.show = () => {
                if (!this.active) {
                    this.ReturnFocus = document.activeElement;

                    for (const Child of this.Root.children) {
                        if (Child !== this.Elm) {
                            this.disabled.set(Child, Child.inert);
                            Child.inert = true;
                        }
                    }
                }

                this.active = true;
                this.Root.setAttribute('aria-busy', 'true');
                this.mount();
                this.position();

                // The native loader recreates its content after a timeout recovery.
                queueMicrotask(() => {
                    if (!this.destroyed && this.active) {
                        this.mount();
                        this.position();
                    }
                });
            };
            this.hide = () => {
                this.active = false;
                this.saving = false;
                this.Root.setAttribute('aria-busy', 'false');

                for (const [Child, inert] of this.disabled) {
                    Child.inert = inert;
                }

                this.disabled.clear();

                if (
                    this.ReturnFocus?.isConnected
                    && (document.activeElement === document.body || this.Root.contains(document.activeElement))
                ) {
                    this.ReturnFocus.focus({preventScroll: true});
                }
            };
            this.position = () => {
                if (!this.active || !this.Cover) {
                    return;
                }

                const root = this.Root.getBoundingClientRect();
                const content = this.Content.getBoundingClientRect();
                Object.assign(this.Cover.style, {
                    left: (content.left - root.left) + 'px',
                    top: (content.top - root.top) + 'px',
                    width: content.width + 'px',
                    height: content.height + 'px'
                });
            };
            this.onDestroy = () => this.destroy();
            this.Loader.addEvents({show: this.show, hide: this.hide});
            Panel.addEvents({resize: this.position, destroy: this.onDestroy});

            if (typeof ResizeObserver !== 'undefined') {
                this.Observer = new ResizeObserver(this.position);
                this.Observer.observe(this.Root);
                this.Observer.observe(this.Content);
            }
        }

        mount() {
            if (this.Cover?.parentElement !== this.Elm) {
                this.Cover = document.createElement('div');
                this.Cover.className = 'quiqqer-product-panel-loading-cover';
                this.Cover.setAttribute('data-name', 'loadingContent');
                const Card = document.createElement('div');
                Card.className = 'quiqqer-product-panel-loading-card';
                const Spinner = document.createElement('span');
                Spinner.className = 'quiqqer-product-panel-loading-spinner';
                Spinner.setAttribute('aria-hidden', 'true');
                this.Message = document.createElement('p');
                this.Message.setAttribute('role', 'status');
                this.Message.setAttribute('aria-live', 'polite');
                Card.append(Spinner, this.Message);
                this.Cover.appendChild(Card);
                this.Elm.appendChild(this.Cover);
            }

            this.Message.textContent = Locale.get(
                'quiqqer/products',
                this.saving ? 'products.product.saving' : 'products.product.loading'
            );
        }

        setSaving() {
            this.saving = true;
        }

        destroy() {
            this.hide();
            this.destroyed = true;
            this.Observer?.disconnect();
            this.Loader.removeEvent('show', this.show);
            this.Loader.removeEvent('hide', this.hide);
            this.Panel.removeEvent('resize', this.position);
            this.Panel.removeEvent('destroy', this.onDestroy);
        }
    };
});
