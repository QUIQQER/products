/**
 * Piwik client
 *
 * If quiqqer/piwik is installed, products send some stats
 */
define('package/quiqqer/products/bin/classes/frontend/Piwik', function () {
    "use strict";

    return new Class({

        /**
         * Return the piwik tracker
         *
         * @returns {Promise}
         */
        getTracker: function () {
            if (typeof window.QUIQQER_PIWIK === 'undefined') {
                return Promise.reject(404);
            }

            return new Promise(function (resolve, reject) {
                require(['PiwikTracker'], resolve, reject);
            });
        }
    });
});