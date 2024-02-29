/**
 * @module package/quiqqer/products/bin/controls/fields/windows/Field
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSave [self]
 */
define('package/quiqqer/products/bin/controls/fields/windows/Field', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale'

], function(QUI, QUIConfirm, QUIAjax, QUILocale) {
    'use strict';

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIConfirm,
        Type: 'package/quiqqer/products/bin/controls/fields/windows/Field',

        Binds: [
            '$onOpen',
            '$onSubmit'
        ],

        options: {
            fieldId: false,
            maxWidth: 800,
            maxHeight: 800,
            autoclose: false
        },

        initialize: function(options) {
            this.parent(options);

            this.$Field = null;

            this.setAttribute('icon', 'fa fa-file-text-o');
            this.setAttribute('title', QUILocale.get(lg, 'fields.update.title', {
                fieldId: this.getAttribute('fieldId')
            }));

            this.setAttribute('ok_button', {
                text: QUILocale.get('quiqqer/quiqqer', 'save'),
                textimage: 'fa fa-check'
            });

            this.addEvents({
                onSubmit: this.$onSubmit,
                onOpen: this.$onOpen
            });
        },

        /**
         * event: on open
         */
        $onOpen: function() {
            var self = this;

            this.getContent().set('html', '');
            this.Loader.show();

            require(['package/quiqqer/products/bin/controls/fields/Update'], function(UpdateField) {
                self.$Field = new UpdateField({
                    fieldId: self.getAttribute('fieldId'),
                    events: {
                        onLoaded: function() {
                            self.Loader.hide();
                        }
                    }
                }).inject(self.getContent());
            });
        },

        /**
         * event: on submit
         */
        $onSubmit: function() {
            var self = this;

            this.Loader.show();
            this.$Field.submit().then(function() {
                self.fireEvent('save', [self]);
                self.close();
            }, function() {
                self.Loader.hide();
            });
        }
    });
});