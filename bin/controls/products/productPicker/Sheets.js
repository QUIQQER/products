define('package/quiqqer/products/bin/controls/products/productPicker/Sheets', [

    'qui/QUI',
    'qui/controls/Control',

    'css!package/quiqqer/products/bin/controls/products/productPicker/Sheets.css'

], function (QUI, QUIControl) {
    "use strict";

    function getOptionsEntryTemplate(option) {
        return `
        <label>
            <span>${option.id}</span>
            <input 
                type="number" 
                name="productId" 
                value="" 
                data-qui="package/quiqqer/products/bin/controls/products/Select"
                data-qui-options-max="1" 
            />
            <input type="hidden" name="label" value="${option.id}" />
        </label>
    `;
    }

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/productPicker/Sheets',

        Binds: [
            '$onInject',
            '$onOptionsChanged'
        ],

        options: {},

        initialize: function (options) {
            this.parent(options);

            this.$Options = null;

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport,
            });
        },

        $onInject: function () {
            console.log('$onInject Sheets');
        },

        $onImport: function () {
            console.log('$onImport Sheets');

            this.$Input = this.getElm();
            this.$Elm = new Element('div');
            this.$Elm.wraps(this.$Input);
            this.$Elm.classList.add('quiqqer-products-productPicker-sheets');

            this.$Container = new Element('div');
            this.$Container.inject(this.$Elm);
            this.$Container.classList.add('quiqqer-products-productPicker-sheets-container');

            new Element('button', {
                html: QUILocale.get('quiqqer/products', 'brick.productPicker.sheetOptions.button.addButton'),
                'class': 'qui-button',
                events: {
                    click: (e) => {
                        e.stop();
                        this.addSheet();
                        this.fireEvent('onChange', [this]);
                    }
                }
            }).inject(this.$Container, 'after');

            const table = this.$Elm.getParent('table');
            const options = table.querySelector(
                '[data-qui="package/quiqqer/products/bin/controls/products/productPicker/SheetOptions"]'
            );

            new Promise((resolve) => {
                if (options.getAttribute('data-quiid')) {
                    this.$Options = QUI.Controls.getById(options.getAttribute('data-quiid'));
                    this.$Options.addEvent('change', this.$onOptionsChanged);
                    resolve();
                } else {
                    options.addEvent('load', () => {
                        this.$Options = QUI.Controls.getById(options.getAttribute('data-quiid'));
                        this.$Options.addEvent('change', this.$onOptionsChanged);
                        resolve();
                    });
                }
            }).then(() => {
                try {
                    const values = JSON.parse(this.$Input.value);

                    console.log('values ==>', values);

                    if (Array.isArray(values)) {
                        values.forEach((value) => {

                        });
                    }
                } catch (e) {
                }
            });


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
            */


        },

        addSheet: function () {
            const sheet = document.createElement('div');

            sheet.classList.add('quiqqer-products-productPicker-sheets-entry');
            sheet.innerHTML = `
                <label>
                    <span>Titel</span>
                    <input type="text" name="id" value="" />
                </label>
                <label>
                    <span>Inhalt</span>
                    <input type="text" name="label" value="" />
                </label>
                <label>
                    <span>Bild</span>
                    <input 
                        type="text" 
                        name="label" 
                        value="" 
                        data-qui=""
                    />
                </label>
                <label>
                    <span>Highlighted</span>
                    <input type="checkbox" name="label" value="" />
                </label>
                <div data-name="quiqqer-products-productPicker-sheets-options">
                
                </div>
            `;

            const optionContainer = sheet.querySelector('[data-name="quiqqer-products-productPicker-sheets-options"]');

            this.$Options.getSheets().forEach((option) => {
                const entry = document.createElement('div');

                entry.setAttribute('data-name', 'options-entry');
                entry.classList.add('quiqqer-products-productPicker-sheets-options-entry');
                entry.innerHTML = getOptionsEntryTemplate(option);
                optionContainer.appendChild(entry);

                QUI.parse(entry);
            });

            this.$Container.appendChild(sheet);
        },

        $onOptionsChanged: function () {
            const sheets = this.$Options.getSheets();
            const optionContainers = this.getElm().querySelectorAll(
                '[data-name="quiqqer-products-productPicker-sheets-options"]'
            );

            optionContainers.forEach((optionContainer) => {
                const currentEntries = Array.from(
                    optionContainer.querySelectorAll('[data-name="options-entry"]')
                );

                // Synchronisiere nach Index
                sheets.forEach((option, idx) => {
                    let entry = currentEntries[idx];
                    if (entry) {
                        // Optional: Werte updaten, falls notwendig
                        entry.querySelector('input[name="label"]').value = option.id;

                        if (option.label === '') {
                            entry.querySelector('span').innerHTML = option.id;
                        } else {
                            entry.querySelector('span').innerHTML = option.label;
                        }
                    } else {
                        // Neuen Eintrag anhängen
                        entry = document.createElement('div');
                        entry.setAttribute('data-name', 'options-entry');
                        entry.classList.add('quiqqer-products-productPicker-sheets-options-entry');
                        entry.innerHTML = getOptionsEntryTemplate(option);
                        optionContainer.appendChild(entry);

                        QUI.parse(entry);
                    }
                });

                // Überschüssige Einträge entfernen
                if (currentEntries.length > sheets.length) {
                    for (let i = sheets.length; i < currentEntries.length; i++) {
                        currentEntries[i].parentNode.removeChild(currentEntries[i]);
                    }
                }
            });

            console.log('$onOptionsChanged');
        }
    });
});
