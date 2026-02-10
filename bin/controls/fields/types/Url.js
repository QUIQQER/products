define('package/quiqqer/products/bin/controls/fields/types/Url', [

    'qui/QUI',
    'qui/controls/Control',
    'URI'

], function (QUI, QUIControl, URI) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/Url',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Status = null;
            this.$currentStatus = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const self = this,
                Elm = this.getElm();

            Elm.addClass('field-container-field');
            Elm.type = 'text';
            Elm.placeholder = 'z.B.: http://www.quiqqer.com';

            this.$Status = new Element('div', {
                'class': 'field-container-item',
                html: '<span class="fa fa-bolt"></span>',
                styles: {
                    lineHeight: 30,
                    textAlign: 'center',
                    width: 50
                }
            }).inject(Elm, 'after');


            Elm.addEvent('change', function () {
                const value = this.value;

                if (value === '') {
                    return;
                }

                const isOk = self.validate();

                if (this.$currentStatus == isOk) {
                    return;
                }

                if (isOk) {
                    this.$Status.set('html', '<span class="fa fa-check"></span>');
                    return;
                }

                this.$Status.set('html', '<span class="fa fa-bolt"></span>');

            }.bind(this));

            Elm.fireEvent('change');
        },

        /**
         * Validate a url
         *
         * @returns {boolean}
         */
        validate: function () {
            const value = this.getElm().value;

            if (value === '') {
                return false;
            }

            try {
                const uri = new URI(this.getElm().value);

                return (!!uri.scheme() && !!uri.host());
            } catch (e) {
                console.warn(e);
                return false;
            }
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.getElm().value;
        }
    });
});
