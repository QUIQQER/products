/**
 * Edit and manage one product - Product Variant Panel
 *
 * @module package/quiqqer/products/bin/controls/products/ProductVariant
 * @author www.pcsg.de (Henning Leutz)
 */
define('package/quiqqer/products/bin/controls/products/ProductVariant', [

    'qui/QUI',
    'package/quiqqer/products/bin/controls/products/Product',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'qui/controls/buttons/ButtonSwitch',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'Locale',
    'Users',
    'controls/grid/Grid',
    'controls/projects/project/media/FolderViewer',
    'Mustache',
    'Packages',
    'utils/Lock',
    'package/quiqqer/products/bin/Products',
    'package/quiqqer/products/bin/classes/Product',
    'package/quiqqer/products/bin/Categories',
    'package/quiqqer/products/bin/Fields',
    'package/quiqqer/products/bin/utils/Fields',
    'package/quiqqer/products/bin/controls/fields/search/Window',
    'package/quiqqer/products/bin/controls/categories/Select',
    'package/quiqqer/products/bin/controls/fields/FieldTypeSelect',

    'text!package/quiqqer/products/bin/controls/products/ProductInformation.html',
    'text!package/quiqqer/products/bin/controls/products/ProductData.html',
    'text!package/quiqqer/products/bin/controls/products/ProductPrices.html',
    'text!package/quiqqer/products/bin/controls/products/CreateField.html',
    'css!package/quiqqer/products/bin/controls/products/Product.css'

], function (QUI, ProductPanel, QUIButton, QUISwitch, QUIButtonSwitch, QUIConfirm, QUIFormUtils, QUILocale,
             Users, Grid, FolderViewer, Mustache, Packages, Locker,
             Products, Product, Categories, Fields, FieldUtils, FieldWindow,
             CategorySelect, FieldTypeSelect,
             informationTemplate, templateProductData, templateProductPrices, templateField) {
    "use strict";

    var lg   = 'quiqqer/products',
        User = Users.getUserBySession();

    return new Class({

        Extends: ProductPanel,
        Type   : 'package/quiqqer/products/bin/controls/products/ProductVariant',

        Binds: [],

        options: {
            productId: false
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'products.product.panel.title'),
                icon : 'fa fa-shopping-bag',
                '#id': "productId" in options ? options.productId : false
            });

            this.parent(options);
        }
    });
});
