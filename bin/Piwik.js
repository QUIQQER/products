/**
 * Piwik Tracker
 *
 * @module package/quiqqer/products/bin/Stats
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require package/quiqqer/products/bin/classes/frontend/Piwik
 */
define('package/quiqqer/products/bin/Piwik', [
    'package/quiqqer/products/bin/classes/frontend/Piwik'
], function (Piwik) {
    "use strict";
    return new Piwik();
});
