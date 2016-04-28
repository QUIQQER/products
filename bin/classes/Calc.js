/**
 * Fields handler
 * Create and edit fields
 *
 * @module package/quiqqer/products/bin/classes/Fields
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */
define('package/quiqqer/products/bin/classes/Fields', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/products/bin/classes/Fields',

        /**
         * Percentage calculation
         */
        CALCULATION_PERCENTAGE: 1,

        /**
         * Standard calculation
         */
        CALCULATION_COMPLEMENT: 2,

        /**
         * Basis calculation -> netto
         */
        CALCULATION_BASIS_NETTO: 1,

        /**
         *Basis calculation -> from current price
         */
        CALCULATION_BASIS_CURRENTPRICE: 2
    });
});
