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
    },

    /**
     * Can the field used as a detail field?
     * JavaScript equivalent package/quiqqer/products/bin/utils/Fields
     *
     * @param {string|number} field - Field Type or Field-Id
     * @returns {Promise} (bool)
     */
    canUsedAsDetailField: function (field) {
        "use strict";
        return new Promise(function (resolve) {

            require(['package/quiqqer/products/bin/Fields'], function (FieldHandler) {
                if (field == FieldHandler.FIELD_TITLE ||
                    field == FieldHandler.FIELD_CONTENT ||
                    field == FieldHandler.FIELD_SHORT_DESC ||
                    field == FieldHandler.FIELD_PRICE ||
                    field == FieldHandler.FIELD_IMAGE
                ) {
                    return resolve(false);
                }

                if (field == FieldHandler.TYPE_ATTRIBUTE_LIST ||
                    field == FieldHandler.TYPE_FOLDER ||
                    field == FieldHandler.TYPE_PRODCUCTS ||
                    field == FieldHandler.TYPE_IMAGE ||
                    field == FieldHandler.TYPE_TEXTAREA_MULTI_LANG
                ) {
                    return resolve(false);
                }

                return resolve(true);
            });
        });
    }
});
