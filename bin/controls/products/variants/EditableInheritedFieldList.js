/**
 * @module package/quiqqer/products/bin/controls/products/EditableFieldList
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldList', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'controls/grid/Grid',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUISwitch, Grid, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/products';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldList',

        Binds: [
            '$onInject',
            '$onStatusChange',
            'refresh'
        ],

        options: {
            productId: false // if false, global field set will be used
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid = null;

            this.$editable  = [];
            this.$inherited = [];
            this.$disabled  = false;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {Element}
         */
        create: function () {
            this.parent();

            this.$Elm = new Element('div', {
                'class'   : 'quiqqer-products-variant-editable-fields',
                id        : this.getId(),
                'data-qui': 'package/quiqqer/products/bin/controls/products/variants/EditableInheritedFieldList',
                styles    : {
                    height: '100%'
                }
            });


            var Container = new Element('div').inject(this.$Elm);

            this.$Grid = new Grid(Container, {
                pagination : true,
                width      : Container.getSize().x,
                height     : Container.getSize().y,
                perPage    : 20,
                page       : 1,
                serverSort : true,
                columnModel: [{
                    header   : QUILocale.get(lg, 'inherited'),
                    dataIndex: 'inherited',
                    dataType : 'QUI',
                    width    : 80,
                    sortable : false
                }, {
                    header   : QUILocale.get(lg, 'editable'),
                    dataIndex: 'editable',
                    dataType : 'QUI',
                    width    : 80,
                    sortable : false
                }, {
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'number',
                    width    : 60,
                    sortable : true
                }, {
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }, {
                    header   : QUILocale.get(lg, 'workingTitle'),
                    dataIndex: 'workingtitle',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }, {
                    header   : QUILocale.get(lg, 'fieldtype'),
                    dataIndex: 'fieldtype',
                    dataType : 'text',
                    width    : 200,
                    sortable : true
                }]
            });

            this.$Grid.addEvents({
                onRefresh: this.refresh
            });

            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            if (!this.$Grid) {
                return;
            }

            var size = this.$Elm.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * Saves the editable fields to the product
         *
         * @return {Promise}
         */
        save: function () {
            var self = this;

            if (this.$disabled) {
                return Promise.resolve();
            }

            if (this.getAttribute('productId') === false) {
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                QUIAjax.post('package_quiqqer_products_ajax_products_variant_saveEditableInheritedFields', resolve, {
                    'package': 'quiqqer/products',
                    productId: self.getAttribute('productId'),
                    editable : JSON.encode(self.getEditableFields()),
                    inherited: JSON.encode(self.getInheritedFields())
                });
            });
        },

        /**
         * Return the active editable fields
         *
         * @return {array}
         */
        getEditableFields: function () {
            return this.$editable;
        },

        /**
         * Return the active editable fields
         *
         * @return {array}
         */
        getInheritedFields: function () {
            return this.$inherited;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var self = this;

            self.$loadFields().then(function () {
                return self.refresh();
            }).then(function () {
                self.fireEvent('load', [self]);
            });
        },

        /**
         * refresh the grid
         *
         * @return {Promise}
         */
        refresh: function () {
            var self    = this,
                options = this.$Grid.options;

            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList', function (result) {
                    var i, len, entry, Editable, Inherited;
                    var data = [];

                    var editable  = self.$editable;
                    var inherited = self.$inherited;

                    for (i = 0, len = result.fields.length; i < len; i++) {
                        entry = result.fields[i];

                        if (!editable.length || editable.indexOf(entry.id) === -1) {
                            Editable = new QUISwitch({
                                editType: 'editable',
                                status  : false,
                                fieldId : parseInt(entry.id),
                                events  : {
                                    onChange: self.$onStatusChange
                                }
                            });
                        } else {
                            Editable = new QUISwitch({
                                editType: 'editable',
                                status  : true,
                                fieldId : parseInt(entry.id),
                                events  : {
                                    onChange: self.$onStatusChange
                                }
                            });
                        }

                        if (!inherited.length || inherited.indexOf(entry.id) === -1) {
                            Inherited = new QUISwitch({
                                editType: 'inherited',
                                status  : false,
                                fieldId : parseInt(entry.id),
                                events  : {
                                    onChange: self.$onStatusChange
                                }
                            });
                        } else {
                            Inherited = new QUISwitch({
                                editType: 'inherited',
                                status  : true,
                                fieldId : parseInt(entry.id),
                                events  : {
                                    onChange: self.$onStatusChange
                                }
                            });
                        }

                        data.push({
                            editable    : Editable,
                            inherited   : Inherited,
                            id          : parseInt(entry.id),
                            title       : entry.title,
                            workingtitle: entry.workingtitle,
                            fieldtype   : entry.type
                        });
                    }

                    self.$Grid.setData({
                        data : data,
                        total: result.total,
                        page : result.page
                    });

                    resolve();
                }, {
                    'package': 'quiqqer/products',
                    productId: self.getAttribute('productId'),
                    options  : JSON.encode({
                        perPage: options.perPage,
                        page   : options.page,
                        sortOn : options.sortOn,
                        sortBy : options.sortBy
                    })
                });
            });
        },

        /**
         * Enable this list
         */
        enable: function () {
            this.$disabled = false;

            if (!this.$Grid) {
                return;
            }

            if (typeof this.$Grid.enable === 'function') {
                this.$Grid.enable();
            }
        },

        /**
         * Disable this list
         */
        disable: function () {
            this.$disabled = true;

            if (typeof this.$Grid.disable === 'function') {
                this.$Grid.disable();
            }
        },

        /**
         * init editable / inherited fields from the product
         *
         * @return {Promise}
         */
        $loadFields: function () {
            var self = this;

            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_products_ajax_products_variant_getEditableInheritedFieldList', function (result) {
                    self.$editable  = result.editable;
                    self.$inherited = result.inherited;
                    resolve();
                }, {
                    'package': 'quiqqer/products',
                    productId: self.getAttribute('productId')
                });
            });
        },

        /**
         * event: on field status change
         * @param Switch
         */
        $onStatusChange: function (Switch) {
            if (this.$disabled) {
                return;
            }

            var index;
            var fieldId = Switch.getAttribute('fieldId'),
                status  = Switch.getStatus();

            if (Switch.getAttribute('editType') === 'editable') {
                if (status) {
                    this.$editable.push(fieldId);
                    this.$editable = this.$editable.filter(function (value, index, self) {
                        return self.indexOf(value) === index;
                    });

                    return;
                }

                index = this.$editable.indexOf(fieldId);
                this.$editable.splice(index, 1);
                return;
            }


            if (Switch.getAttribute('editType') === 'inherited') {
                if (status) {
                    this.$inherited.push(fieldId);
                    this.$inherited = this.$inherited.filter(function (value, index, self) {
                        return self.indexOf(value) === index;
                    });

                    return;
                }

                index = this.$inherited.indexOf(fieldId);
                this.$inherited.splice(index, 1);
            }
        }
    });
});
