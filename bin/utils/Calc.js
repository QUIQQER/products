/**
 * Calc utils
 * Helper for calculations
 */
define('package/quiqqer/products/bin/utils/Calc', {
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
    CALCULATION_BASIS_CURRENTPRICE: 2,

    /**
     * Basis brutto
     * include all price factors (from netto calculated price)
     * warning: its not brutto VAT
     *
     * geht vnn der netto basis aus, welche alle price faktoren schon beinhaltet
     * alle felder sind in diesem price schon enthalten
     */
    CALCULATION_BASIS_BRUTTO: 3
});
