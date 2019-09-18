/**
 * @module package/quiqqer/products/bin/controls/products/variants/EditableFieldSettings
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldList',
    'Locale',
    'Ajax'

], function (QUI, QUIControl, FieldList, QUILocale, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldSettings',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$List  = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$Elm = new Element('div', {
                'class': 'field-container-field field-container-field-no-padding'
            }).wraps(this.$Input);

            var Container = new Element('div').inject(this.$Elm);

            var ListContainer = new Element('div', {
                styles: {
                    height: 300,
                    width : this.$Elm.getSize().x
                }
            }).inject(Container);

            this.$List = new FieldList().inject(ListContainer);
            this.$List.resize();
        },

        /**
         * resize the control
         */
        resize: function () {
            this.$List.resize();
        },

        /**
         * Save the fields to the settings
         *
         * @return {Promise}
         */
        save: function () {
            var self = this;

            return new Promise(function (resolve) {
                QUIAjax.post('package_quiqqer_products_ajax_products_variant_saveEditableInheritedERPFields', resolve, {
                    'package': 'quiqqer/products',
                    editable : JSON.encode(self.$List.getEditableFields()),
                    inherited: JSON.encode(self.$List.getInheritedFields())
                });
            });
        }
    });
});
