/**
 * Activate products after optionally confirming activation of their inactive images.
 */
define('package/quiqqer/products/bin/utils/Activation', [
    'Ajax',
    'Locale',
    'qui/controls/windows/SimpleWindow',
    'css!package/quiqqer/products/bin/utils/Activation.css'
], function (Ajax, QUILocale, SimpleWindow) {
    'use strict';

    const lg = 'quiqqer/products';
    let nextId = 0;

    const element = (tag, name, text) => {
        const Element = document.createElement(tag);
        Element.setAttribute('data-name', name);

        if (typeof text === 'string') {
            Element.textContent = text;
        }

        return Element;
    };

    const askForImages = count => new Promise(resolve => {
        const ReturnFocus = document.activeElement;
        const id = ++nextId;
        const Content = element('section', 'activation');
        Content.className = 'quiqqer-products-activation';
        const Body = element('div', 'body');
        Body.className = 'quiqqer-products-activation-body';
        const Icon = element('span', 'icon');
        Icon.className = 'fa fa-image quiqqer-products-activation-icon';
        Icon.setAttribute('aria-hidden', 'true');
        const Heading = element('h2', 'heading', QUILocale.get(lg, 'products.activation.images.question'));
        Heading.id = 'product-activation-question-' + id;
        const informationKey = count === 1
            ? 'products.activation.images.information.single'
            : 'products.activation.images.information';
        const Information = element('p', 'information', QUILocale.get(lg, informationKey, {count}));
        Information.id = 'product-activation-information-' + id;
        const Footer = element('footer', 'actions');
        Footer.className = 'quiqqer-products-activation-actions';
        const Cancel = element('button', 'cancel', QUILocale.get('quiqqer/core', 'cancel'));
        Cancel.className = 'btn btn-link-body';
        const Choices = element('div', 'activationChoices');
        Choices.className = 'quiqqer-products-activation-choices';
        const WithoutImages = element('button', 'withoutImages', QUILocale.get(lg, 'products.activation.images.skip'));
        WithoutImages.className = 'btn btn-light';
        const WithImages = element('button', 'withImages', QUILocale.get(lg, 'products.activation.images.confirm'));
        WithImages.className = 'btn btn-primary';

        for (const Button of [Cancel, WithoutImages, WithImages]) {
            Button.type = 'button';
        }

        Body.append(Icon, Heading, Information);
        Choices.append(WithoutImages, WithImages);
        Footer.append(Cancel, Choices);
        Content.append(Body, Footer);

        const Window = new SimpleWindow({
            maxWidth: 480,
            maxHeight: 440,
            resizable: false,
            'class': 'quiqqer-products-activation-window',
            events: {
                onOpen: () => {
                    Window.getContent().appendChild(Content);
                    const Dialog = Window.getElm();
                    Dialog.setAttribute('role', 'dialog');
                    Dialog.setAttribute('aria-modal', 'true');
                    Dialog.setAttribute('aria-labelledby', Heading.id);
                    Dialog.setAttribute('aria-describedby', Information.id);
                    WithImages.focus({preventScroll: true});
                },
                onClose: () => {
                    resolve(null);

                    if (ReturnFocus?.isConnected) {
                        ReturnFocus.focus({preventScroll: true});
                    }
                }
            }
        });

        Cancel.addEventListener('click', () => Window.close());
        WithoutImages.addEventListener('click', () => {
            resolve(false);
            Window.close();
        });
        WithImages.addEventListener('click', () => {
            resolve(true);
            Window.close();
        });

        Window.getElm().addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                Window.close();
                return;
            }

            if (event.key === 'Tab') {
                const Dialog = Window.getElm();
                const Close = Dialog.querySelector('[data-name="close"]');
                const First = Close && Close.getClientRects().length ? Close : Cancel;

                if (event.shiftKey && (document.activeElement === First || document.activeElement === Dialog)) {
                    event.preventDefault();
                    WithImages.focus();
                } else if (!event.shiftKey && document.activeElement === WithImages) {
                    event.preventDefault();
                    First.focus();
                }
            }
        });

        Window.open();
    });

    return {
        activate: async function (productIds) {
            if (!productIds.length) {
                return;
            }

            const count = await new Promise((resolve, reject) => {
                Ajax.get('package_quiqqer_products_ajax_products_getInactiveImageCount', resolve, {
                    'package': 'quiqqer/products',
                    productIds: JSON.stringify(productIds),
                    onError: reject
                });
            });

            const activateImages = count > 0 ? await askForImages(count) : false;

            if (activateImages === null) {
                return;
            }

            return new Promise((resolve, reject) => {
                Ajax.post('package_quiqqer_products_ajax_products_activate', resolve, {
                    'package': 'quiqqer/products',
                    productId: JSON.stringify(productIds),
                    activateImages: activateImages ? 1 : 0,
                    onError: reject
                });
            });
        }
    };
});
