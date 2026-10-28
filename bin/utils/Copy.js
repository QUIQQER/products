/**
 * Copy products with an explicit choice of independent, shared or empty media.
 */
define('package/quiqqer/products/bin/utils/Copy', [
    'Ajax',
    'Locale',
    'qui/controls/windows/SimpleWindow',
    'package/quiqqer/products/bin/Fields',
    'css!package/quiqqer/products/bin/utils/Copy.css'
], function (Ajax, Locale, SimpleWindow, Fields) {
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

    return {
        copy: (productId, product = {}) => new Promise(resolve => {
            const ReturnFocus = document.activeElement;
            const id = ++nextId;
            const Content = element('section', 'copy');
            Content.className = 'quiqqer-products-copy';
            const Body = element('div', 'body');
            Body.className = 'quiqqer-products-copy-body';
            const Header = element('header', 'header');
            Header.className = 'quiqqer-products-copy-header';
            const Icon = element('span', 'icon');
            Icon.className = 'fa fa-copy quiqqer-products-copy-icon';
            Icon.setAttribute('aria-hidden', 'true');
            const Heading = element('h2', 'heading', Locale.get(lg, 'products.copy.media.title'));
            Heading.id = 'product-copy-heading-' + id;
            const Product = element('p', 'product', product.title || '');
            Product.className = 'quiqqer-products-copy-product';
            const Metadata = element('div', 'metadata');
            Metadata.className = 'quiqqer-products-copy-metadata';
            Metadata.appendChild(element('span', 'productId', Locale.get(lg, 'products.copy.media.product', {id: productId})));
            const fields = Array.isArray(product.fields) ? product.fields : [];
            const articleNo = fields.find(Field => Number(Field.id) === Fields.FIELD_PRODUCT_NO)?.value;

            if ((typeof articleNo === 'string' && articleNo !== '') || typeof articleNo === 'number') {
                Metadata.appendChild(element('span', 'articleNo', Locale.get(lg, 'productNo') + ': ' + articleNo));
            }

            Header.append(Icon, Heading, Product, Metadata);
            const Options = element('fieldset', 'options');
            Options.className = 'quiqqer-products-copy-options';
            const Legend = element('legend', 'question', Locale.get(lg, 'products.copy.media.question'));
            Options.appendChild(Legend);
            const radios = [];

            for (const mode of ['copy', 'share', 'empty']) {
                const Label = element('label', 'option');
                Label.className = 'quiqqer-products-copy-option';
                const Radio = element('input', 'mediaMode');
                Radio.type = 'radio';
                Radio.name = 'product-copy-media-' + id;
                Radio.value = mode;
                Radio.checked = mode === 'copy';
                const Text = element('span', 'optionText');
                const Title = element('strong', 'optionTitle', Locale.get(lg, 'products.copy.media.' + mode + '.label'));
                const Help = element('span', 'optionHelp', Locale.get(lg, 'products.copy.media.' + mode + '.help'));
                Help.className = 'quiqqer-products-copy-option-help';
                Help.id = 'product-copy-help-' + id + '-' + mode;
                Radio.setAttribute('aria-describedby', Help.id);
                Text.append(Title, Help);
                Label.append(Radio, Text);
                Options.appendChild(Label);
                radios.push(Radio);
            }

            const Status = element('p', 'status');
            Status.className = 'quiqqer-products-copy-status';
            Status.setAttribute('role', 'status');
            Status.setAttribute('aria-live', 'polite');
            const Footer = element('footer', 'actions');
            Footer.className = 'quiqqer-products-copy-actions';
            const Cancel = element('button', 'cancel', Locale.get('quiqqer/core', 'cancel'));
            Cancel.type = 'button';
            Cancel.className = 'btn btn-link-body';
            const Submit = element('button', 'submit', Locale.get(lg, 'products.copy.media.submit'));
            Submit.type = 'button';
            Submit.className = 'btn btn-primary';
            Body.append(Header, Options, Status);
            Footer.append(Cancel, Submit);
            Content.append(Body, Footer);
            let pending = false;
            let copiedProductId = null;

            const Window = new SimpleWindow({
                maxWidth: 540,
                maxHeight: 600,
                resizable: false,
                backgroundClosable: false,
                'class': 'quiqqer-products-copy-window',
                events: {
                    onOpen: () => {
                        Window.getContent().appendChild(Content);
                        Window.getElm().setAttribute('role', 'dialog');
                        Window.getElm().setAttribute('aria-modal', 'true');
                        Window.getElm().setAttribute('aria-labelledby', Heading.id);
                        Submit.focus({preventScroll: true});
                    },
                    onClose: () => {
                        resolve(copiedProductId);

                        if (ReturnFocus?.isConnected) {
                            ReturnFocus.focus({preventScroll: true});
                        }
                    }
                }
            });
            const close = Window.close.bind(Window);
            Window.close = () => pending ? Promise.resolve() : close();
            const setPending = value => {
                pending = value;
                Window.getElm().setAttribute('aria-busy', String(value));
                Submit.disabled = value;
                Cancel.disabled = value;
                radios.forEach(Radio => Radio.disabled = value);
                const Close = Window.getElm().querySelector('[data-name="close"]');

                if (Close) {
                    Close.disabled = value;
                }

                if (value) {
                    Window.getElm().focus({preventScroll: true});
                }

                Submit.textContent = value
                    ? Locale.get('quiqqer/core', 'copy') + ' …'
                    : Locale.get(lg, 'products.copy.media.submit');
            };

            Cancel.addEventListener('click', () => Window.close());
            Submit.addEventListener('click', async () => {
                if (pending || copiedProductId !== null) {
                    return;
                }

                const mediaMode = radios.find(Radio => Radio.checked).value;
                Status.textContent = '';
                setPending(true);

                try {
                    const productIdCopy = await new Promise((resolve, reject) => {
                        Ajax.post('package_quiqqer_products_ajax_products_copy', resolve, {
                            'package': 'quiqqer/products',
                            productId,
                            mediaMode,
                            onError: reject
                        });
                    });
                    copiedProductId = productIdCopy;
                    setPending(false);
                    Window.close();
                } catch (error) {
                    setPending(false);
                    Status.textContent = error?.getMessage?.()
                        || error?.message
                        || Locale.get(lg, 'products.copy.media.error');
                    Status.setAttribute('role', 'alert');
                    Submit.focus({preventScroll: true});
                }
            });

            Window.getElm().addEventListener('keydown', event => {
                if (pending && event.key === 'Tab') {
                    event.preventDefault();
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    Window.close();
                }

                if (event.key === 'Tab') {
                    const Close = Window.getElm().querySelector('[data-name="close"]');
                    const First = Close && Close.getClientRects().length ? Close : Cancel;

                    if (event.shiftKey && document.activeElement === First) {
                        event.preventDefault();
                        Submit.focus();
                    } else if (!event.shiftKey && document.activeElement === Submit) {
                        event.preventDefault();
                        First.focus();
                    }
                }
            });

            Window.open();
        })
    };
});
