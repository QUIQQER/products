/**
 * @module package/quiqqer/products/bin/controls/frontend/category/FilterWindow
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/frontend/category/FilterWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/products/bin/controls/frontend/category/ProductListField',
    'package/quiqqer/productsearch/bin/controls/search/SearchField',
    'qui/controls/buttons/Select',
    'Ajax',
    'Locale',

    'css!package/quiqqer/products/bin/controls/frontend/category/FilterWindow.css'

], function (QUI, QUIConfirm, ProductListField, SearchField, QUISelect, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/products/bin/controls/frontend/category/FilterWindow',

        Binds: [
            'submit'
        ],

        options: {
            categories: [],
            fields    : {},
            tags      : []
        },

        initialize: function (options) {
            var height  = 600,
                width   = 400,
                winSize = QUI.getWindowSize();

            if (winSize.y < height) {
                height = winSize.y - 20;
            }

            if (winSize.x < width) {
                width = winSize.x - 20;
            }

            this.setAttributes({
                class        : 'qui-window-filterWindow',
                title        : QUILocale.get('quiqqer/products', 'quiqqer.products.control.search.title'),
                icon         : 'fa fa-filter',
                maxHeight    : height,
                maxWidth     : width,
                ok_button    : {
                    text     : QUILocale.get('quiqqer/system', 'accept'),
                    textimage: 'fa fa-check'
                },
                cancel_button: {
                    text     : QUILocale.get('quiqqer/system', 'cancel'),
                    textimage: 'fa fa-remove'
                }
            });

            this.parent(options);

            this.$Container = null;
            this.$Menu      = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            Content.set({
                html  : '',
                styles: {
                    opacity: 0
                }
            });

            this.Loader.show();

            QUIAjax.get('package_quiqqer_products_ajax_products_frontend_getFilters', function (result) {

                Content.set('html', result);
                Content.addClass('quiqqer-products-productList-filterMobile-content');

                self.$Container = Content.getElement('.quiqqer-products-productList-filter-container');
                self.$Menu      = Content.getElement('.quiqqer-products-category-menu');

                if (!self.$Container) {
                    self.$Container = new Element('div');
                }

                self.$renderCategoryMenu();
                self.$renderFilter();

                QUI.parse(Content).then(function () {
                    self.$Container.setStyles({
                        height : self.$Container.getScrollSize().y,
                        opacity: 1
                    });

                    moofx(Content).animate({
                        opacity: 1
                    }, {
                        duration: 250,
                        callback: function () {
                            self.Loader.hide();
                        }
                    });
                });
            }, {
                'package': 'quiqqer/products',
                project  : JSON.encode(QUIQQER_PROJECT),
                siteId   : QUIQQER_SITE.id,
                onError  : function (err) {
                    console.error(err);
                    self.close();
                }
            });
        },

        /**
         * event : on close
         */
        submit: function () {
            this.fireEvent('submit', [this, this.getSelected()]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        },

        /**
         * Return the selected filters
         *
         * @return {Object}
         */
        getSelected: function () {
            var result = {};

            if (!this.$Menu) {
                result.categories = [];
            } else {
                // category
                result.categories = this.$Menu.getElements(
                    'input[type="checkbox"]'
                ).filter(function (Checkbox) {
                    return Checkbox.checked;
                }).map(function (Checkbox) {
                    return Checkbox.value;
                });
            }

            // freetext
            var FreeText = this.getContent().getElement('[name="search"]');

            if (FreeText.value !== '') {
                result.freetext = FreeText.value;
            }

            // Filter
            result.fields = this.getContent().getElements(
                '.quiqqer-products-productList-filter-entry'
            ).filter(function (Node) {
                return Node.getElement('[data-quiid]');
            }).map(function (Node) {
                var Control = QUI.Controls.getById(
                    Node.getElement('[data-quiid]').get('data-quiid')
                );

                if (Control.getType() === 'package/quiqqer/productsearch/bin/controls/search/SearchField') {
                    return {
                        value  : Control.getSearchValue(),
                        fieldId: parseInt(Control.getFieldId())
                    };
                }

                return {
                    value  : Control.getValue(),
                    fieldId: parseInt(Control.getAttribute('fieldid'))
                };
            }).filter(function (entry) {
                if (typeOf(entry.value) === 'array' && !entry.value.length) {
                    return false;
                }
                return !(entry.value === '' || entry.value === false);
            });

            // tags
            result.tags = this.getContent().getElements(
                '.quiqqer-products-productList-filter-entry'
            ).filter(function (Node) {
                return !Node.get('data-fieldid');
            }).map(function (Node) {
                var Control = QUI.Controls.getById(
                    Node.getElement('[data-quiid]').get('data-quiid')
                );

                return Control.getValue();
            }).flatten();

            return result;
        },

        /**
         * render the checkbox menu
         */
        $renderCategoryMenu: function () {
            if (!this.$Menu) {
                return;
            }

            // menu events
            this.$Menu.set('data-qui', '');

            this.$Menu.getElements('a').addEvent('click', function (event) {
                var Target = event.target,
                    Label  = Target.getParent('label');

                if (!Label) {
                    return;
                }

                var Input = Label.getElement('input');

                if (!Input) {
                    return;
                }

                event.stop();

                Input.checked = !Input.checked;
                Input.fireEvent('change');
            });

            // menu selected
            var categories = this.getAttribute('categories');

            if (categories.length) {
                this.$Menu.getElements('[type="checkbox"]').each(function (Input) {
                    if (categories.contains(Input.value)) {
                        Input.checked = true;
                    }
                });
            }
        },

        /**
         * render the filter and field select boxes
         */
        $renderFilter: function () {
            var c, i, len, clen, options, fieldId, searchdata,
                Field, Control, Filter, Title, Select;

            // freetext
            var FreeText = this.getContent().getElement('[name="search"]');
            var freetext = this.getAttribute('freetext');

            if (freetext && freetext !== '') {
                FreeText.value = freetext;
            }

            // standard
            var filter = this.getContent().getElements(
                '.quiqqer-products-productList-filter-entry'
            );

            var fields = this.getAttribute('fields');
            var tags   = this.getAttribute('tags');

            for (i = 0, len = filter.length; i < len; i++) {
                Filter = filter[i];
                Select = Filter.getElement('select');
                Title  = Filter.getElement(
                    '.quiqqer-products-productList-filter-entry-title'
                );

                // field
                if (!Select) {
                    // search fields
                    Select     = Filter.getElement('input');
                    searchdata = null;
                    fieldId    = Select.get('data-fieldid');

                    try {
                        searchdata = JSON.decode(Select.get('data-searchdata'));
                    } catch (e) {
                    }

                    Field = new SearchField({
                        fieldid   : fieldId,
                        searchtype: Select.get('data-searchtype'),
                        searchdata: searchdata,
                        title     : Title.get('text').trim()
                    }).inject(Filter);

                    if (fieldId in fields) {
                        Field.setSearchValue(fields[fieldId]);
                    }

                    Select.destroy();
                    continue;
                }

                options = Select.getElements('option');
                fieldId = Select.get('data-fieldid');

                Control = new QUISelect({
                    fieldid              : fieldId,
                    placeholderText      : Title.get('html').trim(),
                    placeholderSelectable: false,
                    multiple             : true,
                    checkable            : true,
                    styles               : {
                        width: '100%'
                    }
                });

                Control.inject(Filter);

                for (c = 0, clen = options.length; c < clen; c++) {
                    Control.appendChild(
                        options[c].get('html').trim(),
                        options[c].get('value').trim()
                    );
                }

                Select.destroy();
                Control.setValues(tags);
            }
        }
    });
});
