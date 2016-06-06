/**
 * Category Site Wizard
 *
 * @module package/quiqqer/products/bin/CategorySiteWizard
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require package/quiqqer/products/bin/classes/Fields
 */
define('package/quiqqer/products/bin/CategorySiteWizard', [
    'package/quiqqer/products/bin/controls/categories/CategorySiteWizard'
], function (Wizard) {
    "use strict";

    return function (Site) {
        Site.addEvent('onOpenCreateChild', function (Win, Site) {

            // on submit event
            var onSubmit = function (Wiz) {

                Wiz.submit().then(function (Site) {
                    require(['utils/Panels'], function (PanelUtils) {

                        Win.Loader.hide();
                        Win.close();

                        PanelUtils.openSitePanel(
                            Site.getProject().getName(),
                            Site.getProject().getLang(),
                            Site.getId()
                        );
                    });

                }, function () {
                    Win.Loader.hide();
                });
            };

            // create the dom
            var Content = Win.getContent(),
                Wiz     = new Wizard({
                    Site  : Site,
                    events: {
                        onSubmitBegin: function () {
                            Win.Loader.show();
                        },
                        onSubmitError: function () {
                            Win.Loader.hide();
                        },
                        submit       : onSubmit
                    }
                });

            Win.Loader.show();

            Content.set('html', '');
            Wiz.inject(Content);

            // harakiri method
            Win.submit = function () {
                onSubmit(Wiz);
            };

            Win.setAttribute('maxHeight', Win.getElm().getSize().y + 50);
            Win.resize();

            (function () {
                Win.Loader.hide();
            }).delay(300);
        });
    };
});
