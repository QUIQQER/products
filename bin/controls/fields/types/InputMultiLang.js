/**
 * @module package/quiqqer/products/bin/controls/fields/types/InputMultiLang
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 * @require css!package/quiqqer/products/bin/controls/fields/types/InputMultiLang.css
 */
define('package/quiqqer/products/bin/controls/fields/types/InputMultiLang', [

    'controls/lang/InputMultiLang'

], function (InputMultiLang) {
    "use strict";

    return new Class({
        Extends: InputMultiLang,
        Type   : 'package/quiqqer/products/bin/controls/fields/types/InputMultiLang'
    });
});
