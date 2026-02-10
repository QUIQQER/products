define('package/quiqqer/products/bin/controls/fields/types/GroupList', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/users/Entry',
    'controls/users/search/Window',
    'package/quiqqer/products/bin/Fields',
    'Users'

], function(QUI, QUIControl, UserDisplay, UserSearch, Fields, Users) {
    'use strict';

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/fields/types/GroupList',

        Binds: [
            '$onImport',
            'openUserSearch'
        ],

        initialize: function(options) {
            this.parent(options);

            this.$Button = null;
            this.$Input = null;
            this.$Display = null;

            this.$uids = [];
            this.$fieldId = false;
            this.$allowedGroups = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function() {
            const Elm = this.getElm();

            Elm.type = 'hidden';

            this.$Button = new Element('span', {
                'class': 'field-container-item',
                html: '<span class="fa fa-user"></span>',
                styles: {
                    cursor: 'pointer',
                    lineHeight: 30,
                    textAlign: 'center',
                    width: 50
                },
                events: {
                    click: this.openUserSearch
                }
            }).inject(Elm, 'after');


            this.$Display = new Element('div', {
                'class': 'field-container-field',
                styles: {
                    padding: 0
                }
            }).wraps(Elm);

            this.$Input = Elm;
            this.$fieldId = this.$Input.get('name').replace('field-', '').toInt();

            // get field settings
            Fields.getChild(this.$fieldId).then((fieldData) => {
                this.$allowedGroups = [];

                if ('groupIds' in fieldData.options && fieldData.options.groupIds) {
                    this.$allowedGroups = fieldData.options.groupIds;
                }

                if (!this.$allowedGroups || !this.$allowedGroups.length) {
                    this.$Button.addClass('disabled');
                }

                try {
                    let data = JSON.decode(Elm.value);

                    Elm.value = '';

                    if (typeOf(data) !== 'array') {
                        return;
                    }

                    if (!data.length) {
                        return;
                    }

                    data.each((uid) => {
                        this.addUser(uid);
                    });
                } catch (e) {
                }
            });
        },

        /**
         * Add a user to the field
         *
         * @param {Number} userId - User-ID
         */
        addUser: function(userId) {
            for (let i = 0, len = this.$uids.length; i < len; i++) {
                if (this.$uids[i] === userId) {
                    return;
                }
            }

            // check if user is allowed
            let Check = Promise.resolve(true);

            if (this.$allowedGroups && this.$allowedGroups.length) {
                Check = this.$isAllowed(userId);
            }

            Check.then((isAllowed) => {
                if (isAllowed === false) {
                    return;
                }

                this.$uids.push(userId);
                this.$updateInput();

                new UserDisplay(userId, {
                    events: {
                        onDestroy: (UD) => {
                            this.removeUser(UD.getUser().getId());
                        }
                    }
                }).inject(this.$Display);
            });
        },

        /**
         * Is the user id allowed to add?
         *
         * @param {Number} userId
         * @return {Promise}
         */
        $isAllowed: function(userId) {
            const allowed = this.$allowedGroups;

            if (!allowed.length) {
                return Promise.resolve(true);
            }

            return new Promise(function(resolve) {
                const User = Users.get(userId);

                if (User.isLoaded()) {
                    return resolve(User);
                }

                User.load().then(resolve);
            }).then(function(User) {
                let i, len, groupId;
                const groups = User.getAttribute('usergroup');

                if (!groups || !groups.length) {
                    return false;
                }

                for (i = 0, len = groups.length; i < len; i++) {
                    groupId = groups[i];

                    if (allowed.indexOf(groupId) !== -1) {
                        return true;
                    }
                }

                return false;
            });
        },

        /**
         * Remove a user from the group list
         *
         * @param {Number} uid
         */
        removeUser: function(uid) {
            this.$uids = this.$uids.filter(function(entry) {
                return entry !== uid;
            });

            this.$updateInput();
        },

        /**
         * Opens the user search
         */
        openUserSearch: function() {
            let searchSettings = false;

            if (this.$allowedGroups) {
                searchSettings = {
                    filter: {
                        filter_group: this.$allowedGroups.join(',')
                    }
                };
            }

            new UserSearch({
                search: true,
                searchSettings: searchSettings,
                events: {
                    onSubmit: (Win, values) => {
                        values.each((Entry) => {
                            this.addUser(Entry.id);
                        });
                    }
                }
            }).open();
        },

        /**
         * Update the input node value
         */
        $updateInput: function() {
            this.$Input.value = JSON.encode(this.$uids);
        },

        /**
         * Return the current value
         *
         * @returns {String}
         */
        getValue: function() {
            return this.$Input.value;
        }
    });
});
