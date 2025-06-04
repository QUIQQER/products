define('package/quiqqer/products/bin/controls/frontend/products/ProductPicker', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUIAjax, QUILocale) {
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
            ]
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        $onInject: function () {
            console.log('on inject');

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
            }, {
                'package': 'quiqqer/core',
                control: '\\QUI\\ERP\\Products\\Controls\\Products\\ProductPicker',
                params: JSON.encode({
                    sheetOptionsStyle: this.getAttribute('sheetOptionsStyle'),
                    sheetOptions: this.getAttribute('sheetOptions'),
                    sheets: this.getAttribute('sheets')
                })
            });
        },

        $onImport: function () {
            console.log('on import');

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
                article.style.display = article.getAttribute('data-interval') === interval ? 'flex' : 'none';
            });
        }
    });
});