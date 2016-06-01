/**
 * @module package/quiqqer/products/bin/controls/products/permissions/Permission
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require controls/Control
 * @require controls/usersAndGroups/Input
 * @require Mustache
 */
define('package/quiqqer/products/bin/controls/products/permissions/Permission', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/usersAndGroups/Input',
    'Mustache',

    'text!package/quiqqer/products/bin/controls/products/permissions/Permission.html'

], function (QUI, QUIControl, PermissionInput, Mustache, template) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : '',

        options: {
            permission: '',
            title     : '',
            value     : false
        },

        initialize: function (options) {
            this.$Input = null;
            this.parent(options);
        },

        /**
         * Return the domnode element
         *
         * @returns {Promise}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-products-permissions',
                html   : Mustache.render(template, {
                    title: this.getAttribute('title')
                })
            });

            this.$Input = new PermissionInput({
                value: this.getAttribute('value')
            }).inject(this.$Elm.getElement('tbody td'));

            return this.$Elm;
        },

        /**
         * Return the value, return the UG-String
         *
         * @return {String}
         */
        getValue: function () {
            return this.$Input.getValue();
        }
    });
});
