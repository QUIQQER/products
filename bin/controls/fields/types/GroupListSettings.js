define('package/quiqqer/products/bin/controls/fields/types/GroupListSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Locale',
    'Mustache',
    'controls/groups/Select',

    'text!package/quiqqer/products/bin/controls/fields/types/GroupListSettings.html',
    'css!package/quiqqer/products/bin/controls/fields/types/GroupListSettings.css'

], function (QUI, QUIControl, QUILocale, Mustache, GroupsInput, template) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/GroupListSettings',

        Binds: [
            'update',
            '$onInject',
            '$onImport'
        ],

        options: {
            groupIds: false,
            multipleUsers: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Groups = null;
            this.$Input = null;
            this.$data = [];

            this.$MultipleUsers = null;

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
                html: Mustache.render(template, {}),
                styles: {
                    'float': 'left',
                    width: '100%'
                }
            });


            this.$MultipleUsers = this.$Elm.getElement('[name="multipleUsers"]');

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            // build group id select
            const GroupIdsInput = this.$Elm.getElement('[name="groupIds"]');

            this.$Groups = new GroupsInput({
                styles: {
                    height: 200
                },
                events: {
                    onChange: this.update
                }
            }).inject(GroupIdsInput.getParent());

            const Parent = GroupIdsInput.getParent('.field-container-field');
            const Options = GroupIdsInput.getParent('.field-options');

            if (Parent) {
                Parent.addClass('field-container-field-no-padding');
            }

            if (Options) {
                Options.addClass('field-container-field-no-padding');
            }

            const groups = this.getAttribute('groupIds');

            if (groups) {
                groups.each(function (groupId) {
                    this.$Groups.addItem(groupId);
                }.bind(this));
            }


            this.$MultipleUsers.checked = this.getAttribute('multipleUsers');
            this.refresh();
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

            try {
                const data = JSON.decode(this.$Input.value);

                if ('multipleUsers' in data) {
                    this.setAttribute('multipleUsers', data.multipleUsers);
                }

                if ('groupIds' in data) {
                    this.setAttribute('groupIds', data.groupIds);
                }

            } catch (e) {
                console.error(e);
            }

            this.$MultipleUsers.addEvent('change', this.update);

            this.$Elm.wraps(this.$Input);
            this.$onInject();
        },

        /**
         * refresh the grid data dispaly
         */
        refresh: function () {

        },

        /**
         * Set the data to the input
         */
        update: function () {
            let groups = [];

            if (this.$Groups.getValue()) {
                groups = this.$Groups.getValue();
                groups = groups.toString();
                groups = groups.split(',');
            }

            this.$Input.value = JSON.encode({
                groupIds: groups,
                multipleUsers: this.$MultipleUsers.checked
            });
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        }
    });
});
