define('package/quiqqer/products/bin/controls/products/productPicker/Sheets', [

    'qui/QUI',
    'qui/controls/Control',

], function (QUI, QUIControl) {

    return new Class({

        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/products/productPicker/Sheets',

        Binds: [
            '$onInject'
        ],

        options: {},

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport,
            });
        },

        $onInject: function () {
            console.log('$onInject Sheets');
        },

        $onImport: function () {
            console.log('$onImport Sheets');
            console.log(this.getElm());
        }
    });
});
