/**
 * Field select item
 */
define('package/quiqqer/products/bin/controls/fields/SelectItem', [

    'qui/controls/Control',
    'package/quiqqer/products/bin/Fields',

    'css!package/quiqqer/products/bin/controls/fields/SelectItem.css'

], function (QUIControl, Fields) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/SelectItem',

        Binds: [
            '$onInject'
        ],

        options: {
            id: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Icon = null;
            this.$Text = null;
            this.$Destroy = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLElement}
         */
        create: function () {
            const self = this,
                Elm = this.parent();

            Elm.set({
                'class': 'quiqqer-fields-selectItem smooth',
                html: '<span class="quiqqer-fields-selectItem-icon fa fa-file-text-o"></span>' +
                    '<span class="quiqqer-fields-selectItem-text">&nbsp;</span>' +
                    '<span class="quiqqer-fields-selectItem-destroy fa fa-remove"></span>'
            });

            this.$Icon = Elm.getElement('.quiqqer-fields-selectItem-icon');
            this.$Text = Elm.getElement('.quiqqer-fields-selectItem-text');
            this.$Destroy = Elm.getElement('.quiqqer-fields-selectItem-destroy');

            this.$Destroy.addEvent('click', function (e) {
                e.stop();
                self.destroy();
            });

            return Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            const self = this;

            this.$Text.set({
                html: '<span class="fa fa-spinner fa-spin"></span>'
            });

            Fields.getChild(
                this.getAttribute('id')
            ).then(function (data) {
                self.$Text.set('html', data.title);
            }).catch(function () {
                self.$Icon.removeClass('fa-file-text-o');
                self.$Icon.addClass('fa-bolt');
                self.$Text.set('html', '...');
            });
        }
    });
});
