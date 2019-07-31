/**
 * @module package/quiqqer/products/bin/controls/fields/types/GroupList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/fields/types/GroupList', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/users/Entry',
    'controls/users/search/Window',
    'package/quiqqer/products/bin/Fields',
    'Users'

], function (QUI, QUIControl, UserDisplay, UserSearch, Fields, Users) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/GroupList',

        Binds: [
            '$onImport',
            'openUserSearch'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Button  = null;
            this.$Input   = null;
            this.$Display = null;

            this.$uids          = [];
            this.$fieldId       = false;
            this.$allowedGroups = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            Elm.type = 'hidden';

            this.$Button = new Element('span', {
                'class': 'field-container-item',
                html   : '<span class="fa fa-user"></span>',
                styles : {
                    cursor    : 'pointer',
                    lineHeight: 30,
                    textAlign : 'center',
                    width     : 50
                },
                events : {
                    click: this.openUserSearch
                }
            }).inject(Elm, 'after');


            this.$Display = new Element('div', {
                'class': 'field-container-field',
                styles : {
                    padding: 0
                }
            }).wraps(Elm);

            this.$Input   = Elm;
            this.$fieldId = this.$Input.get('name').replace('field-', '').toInt();

            // get field settings
            Fields.getChild(this.$fieldId).then(function (fieldData) {
                this.$allowedGroups = [];

                if ("groupIds" in fieldData.options) {
                    this.$allowedGroups = fieldData.options.groupIds;
                }

                if (!this.$allowedGroups || !this.$allowedGroups.length) {
                    this.$Button.addClass('disabled');
                }

                try {
                    var data = JSON.decode(Elm.value);

                    Elm.value = '';

                    if (typeOf(data) !== 'array') {
                        return;
                    }

                    if (!data.length) {
                        return;
                    }

                    data.each(function (uid) {
                        this.addUser(uid);
                    }.bind(this));

                } catch (e) {
                }

            }.bind(this));
        },

        /**
         * Add a user to the field
         *
         * @param {Number} userId - User-ID
         */
        addUser: function (userId) {
            userId = parseInt(userId);

            for (var i = 0, len = this.$uids.length; i < len; i++) {
                if (this.$uids[i] === userId) {
                    return;
                }
            }

            // check if user is allowed
            var self  = this,
                Check = Promise.resolve(true);

            if (this.$allowedGroups && this.$allowedGroups.length) {
                Check = this.$isAllowed(userId);
            }

            Check.then(function (isAllowed) {
                if (isAllowed === false) {
                    return;
                }

                self.$uids.push(userId);
                self.$updateInput();

                new UserDisplay(userId, {
                    events: {
                        onDestroy: function (UD) {
                            self.removeUser(UD.getUser().getId());
                        }
                    }
                }).inject(self.$Display);
            });
        },

        /**
         * Is the user id allowed to add?
         *
         * @param {Number} userId
         * @return {Promise}
         */
        $isAllowed: function (userId) {
            var allowed = this.$allowedGroups;

            if (!allowed.length) {
                return Promise.resolve(true);
            }

            return new Promise(function (resolve) {
                var User = Users.get(userId);

                if (User.isLoaded()) {
                    return resolve(User);
                }

                return User.load();
            }).then(function (User) {
                var i, len, groupId;
                var groups = User.getAttribute('usergroup');

                if (!groups || !groups.length) {
                    return false;
                }

                for (i = 0, len = groups.length; i < len; i++) {
                    groupId = parseInt(groups[i]);

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
        removeUser: function (uid) {

            this.$uids = this.$uids.filter(function (entry) {
                return entry !== uid;
            });

            this.$updateInput();
        },

        /**
         * Opens the user search
         */
        openUserSearch: function () {
            var self           = this,
                searchSettings = false;

            if (this.$allowedGroups) {
                this.$allowedGroups = this.$allowedGroups.map(function (g) {
                    return parseInt(g);
                });

                searchSettings = {
                    filter: {
                        filter_group: this.$allowedGroups.join(',')
                    }
                };
            }

            new UserSearch({
                search        : true,
                searchSettings: searchSettings,
                events        : {
                    onSubmit: function (Win, values) {
                        values.each(function (Entry) {
                            self.addUser(Entry.id);
                        });
                    }
                }
            }).open();
        },

        /**
         * Update the input node value
         */
        $updateInput: function () {
            this.$Input.value = JSON.encode(this.$uids);
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
