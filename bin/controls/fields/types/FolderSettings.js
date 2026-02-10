define('package/quiqqer/products/bin/controls/fields/types/FolderSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',

    'Locale',

    'css!package/quiqqer/products/bin/controls/fields/types/FolderSettings.css'

], function (QUI, QUIControl, QUILoader, QUILocale) {
    "use strict";

    const lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/FolderSettings',

        Binds: [
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            fieldId: false,
            groups: [],

            autoActivateItems: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$CheckboxAutoActivate = null;
            this.$CheckboxShowFrontendTabIfEmpty = null;
            this.$MediaFolderSelect = null;
            this.Loader = new QUILoader();

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                styles: {
                    'float': 'left',
                    width: '100%'
                }
            });

            return this.$Elm;
        },

        /**
         * event: on import
         */
        $onInject: function () {
            const Parent = this.$Elm.getParent('.field-options');

            if (Parent) {
                Parent.setStyle('padding', 0);
            }

            const Content = new Element('div', {
                'class': 'quiqqer-products-folder-settings',
                html: '<label>' +
                    '        <input type="checkbox" name="autoActivateItems"/>' +
                    '           <span>' + QUILocale.get(lg, 'controls.FolderSettings.autoActivateItems') + '</span>' +
                    '    </label>' +
                    '<label>' +
                    '        <input type="checkbox" name="showFrontendTabIfEmpty"/>' +
                    '           <span>' + QUILocale.get(lg, 'controls.FolderSettings.showFrontendTabIfEmpty') + '</span>' +
                    '    </label>' +
                    '<label>' +
                    '           <span>' + QUILocale.get(lg, 'controls.FolderSettings.mediaFolder') + '</span>' +
                    '        <input type="hidden"' +
                    ' name="mediaFolder"' +
                    ' data-qui="controls/projects/project/media/Input"' +
                    '/>' +
                    '    </label>' +
                    '<div class="field-container-item-desc">' +
                    QUILocale.get(lg, 'controls.FolderSettings.mediaFolder.desc') +
                    '</div>'
            }).inject(this.$Elm);

            this.Loader.inject(Content);

            const MediaFolderSelectInput = Content.getElement('input[name="mediaFolder"]');
            MediaFolderSelectInput.value = this.getAttribute('mediaFolder');

            this.$CheckboxAutoActivate = this.$Elm.getElement('[name="autoActivateItems"]');
            this.$CheckboxAutoActivate.addEvent('change', this.update);

            this.$CheckboxAutoActivate.checked = !!this.getAttribute('autoActivateItems');

            this.$CheckboxShowFrontendTabIfEmpty = this.$Elm.getElement('[name="showFrontendTabIfEmpty"]');
            this.$CheckboxShowFrontendTabIfEmpty.addEvent('change', this.update);

            this.$CheckboxShowFrontendTabIfEmpty.checked = !!this.getAttribute('showFrontendTabIfEmpty');

            Content.getParent('.field-container').setStyles({
                height: 250
            });

            this.Loader.show();

            QUI.parse(Content).then(() => {
                this.$MediaFolderSelect = QUI.Controls.getById(MediaFolderSelectInput.get('data-quiid'));

                this.$MediaFolderSelect.addEvent('onChange', this.update);
                this.$MediaFolderSelect.setAttribute('selectable_types', ['folder']);

                this.Loader.hide();
            });
        },

        /**
         * event : on import
         *
         * @param self
         * @param {HTMLInputElement} Node
         */
        $onImport: function (self, Node) {
            this.$Input = Node;
            this.$Elm = this.create();

            let data = {};

            try {
                data = JSON.decode(this.$Input.value);

                // parse data
                if ("autoActivateItems" in data) {
                    this.setAttribute('autoActivateItems', data.autoActivateItems);
                }

                if ("mediaFolder" in data) {
                    this.setAttribute('mediaFolder', data.mediaFolder);
                }

                if ("showFrontendTabIfEmpty" in data) {
                    this.setAttribute('showFrontendTabIfEmpty', data.showFrontendTabIfEmpty);
                }
            } catch (e) {
                console.error(this.$Input.value);
                console.error(e);
            }

            if (!this.$data) {
                this.$data = [];
            }

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * Set the data to the input
         */
        update: function () {
            this.$Input.value = JSON.encode({
                autoActivateItems: this.$CheckboxAutoActivate.checked ? 1 : 0,
                mediaFolder: this.$MediaFolderSelect.getValue(),
                showFrontendTabIfEmpty: this.$CheckboxShowFrontendTabIfEmpty.checked ? 1 : 0
            });
        }
    });
});
