define('package/quiqqer/products/bin/controls/frontend/products/ProductPicker', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'package/quiqqer/currency/bin/Currency'

], function (QUI, QUIControl, QUIAjax, QUILocale, Currency) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/frontend/products/ProductPicker',

        Binds: [
            '$onInject',
            'refresh'
        ],

        options: {
            forwardToBasket: true,
            sheetOptionsStyle: 'select', // select | radio
            sheetOptions: [
                /*
                {
                    id: 'monthly',
                    label: 'monatlich'
                }
                */
            ],
            sheets: [
                /*
                {
                    title: 'Startup',
                    content: '',
                    image: '',
                    highlighted: true,
                    options: {
                        monthly: 1,
                        yearly: 4
                    }
                }
                */
            ],
            zeroProduct: false
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        $onInject: function () {
            const container = this.getElm();

            QUIAjax.get('ajax_controls_get', (result) => {
                const ghost = document.createElement('div');
                ghost.innerHTML = result;

                container.innerHTML = '';

                Array.from(ghost.querySelectorAll('style')).forEach((style) => {
                    container.appendChild(style);
                });

                const productPicker = ghost.querySelector('[data-qui]');
                container.appendChild(productPicker);
                container.setAttribute('data-qui', productPicker.getAttribute('data-qui'));
                container.setAttribute('data-name', 'product-picker');

                this.$onImport();
                this.fireEvent('load', [this]);
            }, {
                'package': 'quiqqer/core',
                control: '\\QUI\\ERP\\Products\\Controls\\Products\\ProductPicker',
                params: JSON.encode({
                    frontendTitle: this.getAttribute('frontendTitle'),
                    content: this.getAttribute('content'),
                    sheetOptionsStyle: this.getAttribute('sheetOptionsStyle'),
                    sheetOptions: this.getAttribute('sheetOptions'),
                    sheets: this.getAttribute('sheets'),
                    zeroProduct: this.getAttribute('zeroProduct')
                })
            });
        },

        $onImport: function () {
            const container = this.getElm();
            const options = container.querySelector('[data-name="product-picker-options"]');
            const optionNodes = Array.from(options.querySelectorAll('select,input'));

            optionNodes.forEach((optionNode) => {
                optionNode.addEventListener('change', this.refresh);
            });

            const buttons = Array.from(
                container.querySelectorAll('button[data-name="product-sheet-product-select"]')
            );

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const article = button.closest('article');
                    const productId = article.getAttribute('data-product-id');

                    if (this.getAttribute('forwardToBasket')) {
                        // @todo TODO
                        return;
                    }

                    this.fireEvent('onProductSelected', [this, productId]);
                });
            });

            if (this.getAttribute('zeroProduct') && this.getAttribute('zeroProductData')) {
                const productData = this.getAttribute('zeroProductData');

                if (typeof productData.title !== 'undefined' && productData.title !== false) {
                    this.getElm().querySelector(
                        '[data-name="zero-product-title"]'
                    ).innerHTML = productData.title;
                }

                if (typeof productData.image !== 'undefined' && productData.image !== false) {
                    this.getElm().querySelector(
                        '[data-name="zero-product-image"]'
                    ).setAttribute('src', productData.image);
                }

                if (typeof productData.description !== 'undefined' && productData.description !== false) {
                    this.getElm().querySelector(
                        '[data-name="zero-product-description"]'
                    ).innerHTML = productData.description;
                }

                if (typeof productData.content !== 'undefined' && productData.content !== false) {
                    this.getElm().querySelector(
                        '[data-name="zero-product-content"]'
                    ).innerHTML = productData.content;
                }

                if (typeof productData.price !== 'undefined' && productData.price !== false) {
                    const fmt = QUILocale.getNumberFormatter({
                        style: 'currency',
                        currency: window.RUNTIME_CURRENCY,
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    const price = fmt.format(productData.price);
                    this.getElm().querySelector('[data-name="zero-product-price"]').innerHTML = price;
                }

                if (typeof productData.button !== 'undefined' && productData.button !== false) {
                    this.getElm().querySelector(
                        '[data-name="zero-button-text"]'
                    ).innerHTML = productData.button;
                }
            }

            this.refresh();
        },

        refresh: function () {
            const container = this.getElm();
            const form = container.querySelector('[data-name="product-picker-options"]');
            const interval = form.elements.interval.value;

            const articles = Array.from(
                container.querySelectorAll('[data-name="product-sheet-product"]')
            );

            articles.forEach((article) => {
                article.style.display = article.getAttribute('data-interval') === interval ? null : 'none';
            });
        }
    });
});