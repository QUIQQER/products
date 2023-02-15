/**
 * Piwik client
 *
 * If quiqqer/piwik is installed, products send some stats
 */
define('package/quiqqer/products/bin/classes/frontend/Stats', function () {
    "use strict";

    return new Class({

        /**
         * Return the piwik tracker
         *
         * @returns {Promise}
         */
        getTracker: function () {
            if (typeof window.QUIQQER_MATOMO === 'undefined' && typeof window.QUIQQER_PIWIK === 'undefined') {
                return Promise.reject(404);
            }

            if (typeof window.QUIQQER_PIWIK !== 'undefined') {
                return new Promise(function (resolve, reject) {
                    require(['piwikTracker'], function (piwikTracker) {
                        piwikTracker.then(resolve);
                    }, reject);
                });
            }

            if (typeof window.QUIQQER_MATOMO !== 'undefined') {
                return new Promise(function (resolve, reject) {
                    require(['matomoTracker'], function (matomoTracker) {
                        matomoTracker.then(resolve);
                    }, reject);
                });
            }

            return Promise.reject(404);
        }
    });
});