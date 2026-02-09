/**
 * Category Site Wizard
 *
 * @event onCategorySelect
 */
define('package/quiqqer/products/bin/controls/categories/CategorySiteWizard', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/categories/Sitemap',
    'Locale',
    'Ajax',
    'Projects',

    'text!package/quiqqer/products/bin/controls/categories/CategorySiteWizard.html'

], function (QUI, QUIControl, CategorySitemap, QUILocale, Ajax, Projects, template) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/categories/CategorySiteWizard',

        options: {
            Site: false,
            categoryParent: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Checkbox = null;

            this.addEvents({
                onInject: function () {
                    this.$Input.focus.delay(200, this.$Input);
                }.bind(this)
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                html: template,
                styles: {
                    height: '100%',
                    position: 'relative'
                }
            });

            this.$Input = this.$Elm.getElement('[type="text"]');
            this.$Checkbox = this.$Elm.getElement('[type="checkbox"]');

            this.$Input.addEvent('keyup', function (event) {
                if (event.key === 'enter') {
                    this.fireEvent('submit', [this]);
                }
            }.bind(this));

            this.$Checkbox.addEvent('keyup', function (event) {
                if (event.key === 'enter') {
                    this.fireEvent('submit', [this]);
                }
            }.bind(this));

            return this.$Elm;
        },

        /**
         * Submit and create the site and the category
         *
         * @returns {Promise}
         */
        submit: function () {
            const self = this,
                Site = this.getAttribute('Site'),
                catId = this.getAttribute('categoryParent');

            if (!Site) {
                return Promise.reject();
            }

            if (this.$Checkbox.checked && catId === false) {
                return self.categorySelect().then(function () {
                    self.fireEvent('submitBegin', [self]);

                    return new Promise(function (resolve) {
                        require([
                            'package/quiqqer/translator/bin/classes/Translator'
                        ], function (Translator) {
                            const Tr = new Translator();

                            Tr.publish('quiqqer/products').then(function () {
                                return Tr.refreshLocale();
                            }).then(resolve);
                        });
                    });

                }).then(function () {
                    return self.submit();
                });
            }

            this.fireEvent('submitBegin', [this]);

            return new Promise(function (resolve, reject) {
                Ajax.post('package_quiqqer_products_ajax_categories_createProjectSite', function (newSiteId) {
                    if (!newSiteId) {
                        self.setAttribute('categoryParent', false);
                        self.$Input.focus.delay(200, self.$Input);
                        reject();
                        return;
                    }

                    const Child = Site.getProject().get(newSiteId);

                    Site.fireEvent('createChild', [Site, newSiteId]);
                    resolve(Child);
                }, {
                    'package': 'quiqqer/products',
                    createCategory: this.$Checkbox.checked ? 1 : 0,
                    title: this.$Input.value,
                    parentCategory: this.getAttribute('categoryParent'),
                    project: Site.getProject().encode(),
                    siteId: Site.getId(),
                    onError: function (err) {
                        console.log(err.getMessage());
                    }
                });

            }.bind(this));
        },

        /**
         * Select a parent category
         *
         * @returns {Promise}
         */
        categorySelect: function () {
            return new Promise(function (resolve) {
                const self = this;

                this.fireEvent('categorySelect', [this]);

                const Container = new Element('div', {
                    html: '<p>Bitte wählen Sie die Übergeordnete Kategorie aus:</p>',  // #locale
                    styles: {
                        background: '#fff',
                        height: '100%',
                        left: -1,
                        position: 'absolute',
                        opacity: 0,
                        top: -50,
                        width: '100%'
                    }
                }).inject(this.$Elm);

                moofx(Container).animate({
                    opacity: 1,
                    top: 0
                }, {
                    duration: 200,
                    callback: function () {
                        new CategorySitemap({
                            events: {
                                onClick: function (map, value) {
                                    self.setAttribute('categoryParent', value);

                                    moofx(Container).animate({
                                        opacity: 0,
                                        top: -50
                                    }, {
                                        duration: 200,
                                        callback: function () {
                                            Container.destroy();
                                            resolve();
                                        }
                                    });
                                }
                            }
                        }).inject(Container);
                    }
                });

            }.bind(this));
        }
    });
});
