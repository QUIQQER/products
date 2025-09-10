define('package/quiqqer/products/bin/controls/products/productPicker/SheetOptions', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',

    'css!package/quiqqer/products/bin/controls/products/productPicker/SheetOptions.css'

], function (QUI, QUIControl, QUILocale) {

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/productPicker/SheetOptions',

        Binds: [
            '$onInject',
            'addOption'
        ],

        options: {},

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport,
            });
        },

        $onInject: function () {
            console.log('$onInject SheetOptions');
        },

        $onImport: function () {
            this.$Input = this.getElm();
            this.$Elm = new Element('div');
            this.$Elm.wraps(this.$Input);
            this.$Elm.classList.add('quiqqer-products-productPicker-sheetOptions');

            this.$Container = new Element('div');
            this.$Container.inject(this.$Elm);
            this.$Container.classList.add('quiqqer-products-productPicker-sheetOptions-container');

            new Element('button', {
                html: QUILocale.get('quiqqer/products', 'brick.productPicker.sheetOptions.button.addButton'),
                'class': 'qui-button',
                events: {
                    click: (e) => {
                        e.stop();
                        this.addOption();
                        this.fireEvent('onChange', [this]);
                    }
                }
            }).inject(this.$Container, 'after');

            try {
                const values = JSON.parse(this.$Input.value);

                if (Array.isArray(values)) {
                    values.forEach((value) => {
                        this.addOption(value.id, value.label);
                    });
                }
            } catch (e) {
            }
        },

        addOption: function (id, label) {
            const sheet = document.createElement('div');

            sheet.classList.add('quiqqer-products-productPicker-sheetOptions-option');
            sheet.innerHTML = `
                <label>
                    <span>ID</span>
                    <input type="text" name="id" value="" />
                </label>
                <label>
                    <span>Label</span>
                    <input type="text" name="label" value="" />
                </label>
                <button class="qui-button">
                    <span class="fa fa-trash"></span>
                </button>
            `;

            if (typeof id !== 'undefined') {
                sheet.querySelector('input[name="id"]').value = id;
            }

            if (typeof label !== 'undefined') {
                sheet.querySelector('input[name="label"]').value = label;
            }

            sheet.querySelector('button').addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                sheet.parentNode.removeChild(sheet);
                this.onChange();
            });

            Array.from(sheet.querySelectorAll('input')).forEach((input) => {
                input.addEventListener('change', () => {
                    this.onChange();
                });
            });

            this.$Container.appendChild(sheet);
        },

        getSheets: function () {
            const sheets = [];
            const container = this.getElm();

            Array.from(container.querySelectorAll('.quiqqer-products-productPicker-sheetOptions-option')).forEach((sheet) => {
                const id = sheet.querySelector('input[name="id"]').value;
                const label = sheet.querySelector('input[name="label"]').value;

                sheets.push({
                    id: id,
                    label: label
                });
            });

            return sheets;
        },

        onChange: function () {
            this.$Input.value = JSON.stringify(this.getSheets());
            this.fireEvent('onChange', [this]);
        }
    });
});
