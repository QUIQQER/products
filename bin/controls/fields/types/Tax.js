/**
 * @module package/quiqqer/products/bin/controls/fields/types/Vat
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require package/quiqqer/tax/bin/classes/TaxTypes
 */
define('package/quiqqer/products/bin/controls/fields/types/Tax', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/products/bin/controls/fields/types/Vat'

], function (QUI, QUIControl, Vat) {
    "use strict";

    return new Class({
        Extends: Vat,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/Tax',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);
        }
    });
});
