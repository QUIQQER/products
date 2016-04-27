/**
 * @modue package/quiqqer/products/bin/controls/search/FieldSelect
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/search/FieldSelect', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/fields/Select',
    'Ajax'

], function (QUI, QUIControl, FieldSelect, Ajax) {
    "use strict";

    return new Class({
        Type   : 'package/quiqqer/products/bin/controls/search/FieldSelect',
        Extends: QUIControl,

        Binds: [
            '$onImport',
            '$search'
        ],

        options: {
            siteId : false,
            project: false,
            Site   : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$FieldSelect = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * events : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            Elm.type = 'hidden';

            this.$FieldSelect = new FieldSelect({
                disabled: true,
                search  : this.$search,
                events  : {
                    onChange: function (Select, values) {

                    }
                }
            }).inject(Elm.getParent());

            if (!this.getAttribute('Site')) {
                var Panel    = false,
                    DomPanel = Elm.getParent('.qui-panel');

                if (DomPanel) {
                    Panel = QUI.Controls.getById(DomPanel.get('data-quiid'));

                    if (Panel.getType() == 'controls/projects/project/site/Panel') {
                        this.setAttribute('Site', Panel.getSite());
                    }
                }
            }

            if (this.getAttribute('Site')) {
                this.$FieldSelect.enable();
            }
        },

        /**
         * Search fields
         *
         * @param {String} value
         * @param {String} params
         * @returns {Promise}
         */
        $search: function (value, params) {
            return new Promise(function (resolve, reject) {
                var Site = this.getAttribute('Site');

                if (!Site) {
                    reject(false);
                    return;
                }

                var Project = Site.getProject();

                Ajax.get('package_quiqqer_products_ajax_search_frontend_getSearchFields', function (result) {
                    console.log(result);

                    resolve(result);
                }, {
                    'package': 'quiqqer/products',
                    siteId   : Site.getId(),
                    project  : Project.encode()
                });
            }.bind(this));
        }
    });
});
