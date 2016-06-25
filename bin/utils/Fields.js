/**
 * Field utils
 * Helper for fields
 *
 * @module package/quiqqer/products/bin/utils/Fields
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/utils/Fields', {

    /**
     * Sort a field array
     *
     * @param {Array} fields
     * @return {Array}
     */
    sortFields: function (fields) {
        "use strict";

        return fields.clean().sort(function (a, b) {
            var ap = parseInt(a.priority);
            var bp = parseInt(b.priority);

            if (ap === 0) {
                return 1;
            }

            if (bp === 0) {
                return -1;
            }

            if (ap < bp) {
                return -1;
            }

            if (ap > bp) {
                return 1;
            }

            return 0;
        });
    }
});
