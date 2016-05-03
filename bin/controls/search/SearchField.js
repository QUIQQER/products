/**
 * @module package/quiqqer/products/bin/controls/search/SearchField
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require css!package/quiqqer/products/bin/controls/search/SearchField.css
 *
 *
 * self::SEARCHTYPE_TEXT,
 - Input

 self::SEARCHTYPE_SELECTRANGE,
 - 2 Select (von bis)

 self::SEARCHTYPE_SELECTSINGLE,
 - 1 Select

 self::SEARCHTYPE_SELECTMULTI,
 - Select multi

 self::SEARCHTYPE_BOOL,
 - Input checkbox (oder Select)

 self::SEARCHTYPE_HASVALUE,
 - Select (ja / nein)

 self::SEARCHTYPE_DATE,
 - Input date

 self::SEARCHTYPE_DATERANGE,
 - 2 Input date (von bis)

 self::SEARCHTYPE_INPUTSELECTRANGE,
 - 2 Select (von bis - mit zusätzlicher eingabe -> wie zb mobile.de)

 self::SEARCHTYPE_INPUTSELECTSINGLE
 - 1 Select (von bis - mit zusätzlicher eingabe -> wie zb mobile.de)

 */
define('package/quiqqer/products/bin/controls/search/SearchField', [

    'qui/QUI',
    'qui/controls/Control',

    'css!package/quiqqer/products/bin/controls/search/SearchField.css'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/products/bin/controls/search/SearchField',

        Binds: [
            '$onInject'
        ],

        options: {
            searchtype: 'text',
            fieldid   : false
        },

        initialize: function (options) {

            this.$Elm   = null;
            this.$Input = null;
            this.$Type  = null;

            this.$searchData = null;

            this.parent(options);

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div');

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    type: 'hidden'
                }).inject(this.$Elm);
            }

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            console.log(this.getAttribute('searchtype'));

            require([
                'package/quiqqer/products/bin/controls/search/searchtypes/Bool',
                'package/quiqqer/products/bin/controls/search/searchtypes/Date',
                'package/quiqqer/products/bin/controls/search/searchtypes/DateRange',
                'package/quiqqer/products/bin/controls/search/searchtypes/HasValue',
                'package/quiqqer/products/bin/controls/search/searchtypes/InputSelectRange',
                'package/quiqqer/products/bin/controls/search/searchtypes/InputSelectSingle',
                'package/quiqqer/products/bin/controls/search/searchtypes/SelectMulti',
                'package/quiqqer/products/bin/controls/search/searchtypes/SelectRange',
                'package/quiqqer/products/bin/controls/search/searchtypes/SelectSingle',
                'package/quiqqer/products/bin/controls/search/searchtypes/Text'
            ], function (Bool, Date, DateRange, HasValue, InputSelectRange, InputSelectSingle,
                         SelectMulti, SelectRange, SelectSingle, Text) {

                switch (this.getAttribute('searchtype')) {
                    case 'text':
                        this.$Type = new Text().inject(this.getElm());
                        break;

                    case 'selectRange':
                        this.$Type = new SelectRange().inject(this.getElm());
                        break;

                    case 'inputSelectRange':
                        this.$Type = new InputSelectRange().inject(this.getElm());
                        break;

                    case 'selectSingle':
                        this.$Type = new SelectSingle().inject(this.getElm());
                        break;

                    case 'inputSelectSingle':
                        this.$Type = new InputSelectSingle().inject(this.getElm());
                        break;

                    case 'selectMulti':
                        this.$Type = new SelectMulti().inject(this.getElm());
                        break;

                    case 'bool':
                        this.$Type = new Bool().inject(this.getElm());
                        break;

                    case 'hasValue':
                        this.$Type = new HasValue().inject(this.getElm());
                        break;

                    case 'date':
                        this.$Type = new Date().inject(this.getElm());
                        break;

                    case 'dateRange':
                        this.$Type = new DateRange().inject(this.getElm());
                        break;
                }

                if (this.$searchData) {
                    this.$Type.setSearchData(this.$searchData);
                }

            }.bind(this));
        },

        /**
         * Set the search data for the fields
         *
         * @param {object|array} data
         */
        setSearchData: function (data) {
            if (this.$Type) {
                this.$Type.setSearchData(data);
            } else {
                this.$searchData = data;
            }
        }
    });
});
