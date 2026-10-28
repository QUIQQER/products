/**
 * Notification channel selection for the Product Quality Check cron parameter.
 */
define('package/quiqqer/products/bin/controls/cron/QualityNotification', [
    'qui/controls/Control',
    'Locale'
], function (QUIControl, QUILocale) {
    'use strict';

    const lg = 'quiqqer/products';

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/products/bin/controls/cron/QualityNotification',

        initialize: function (options) {
            this.parent(options);
            this.addEvents({
                onImport: () => this.$import(),
                onDestroy: () => this.$Select?.remove()
            });
        },

        $import: function () {
            const Input = this.getElm();
            const Select = document.createElement('select');
            Select.setAttribute('aria-label', QUILocale.get(lg, 'cron.productQualityCheck.notification'));

            ['email', 'backend', 'both'].forEach((value) => {
                const Option = document.createElement('option');
                Option.value = value;
                Option.textContent = QUILocale.get(lg, 'cron.productQualityCheck.notification.' + value);
                Select.appendChild(Option);
            });

            Select.value = Input.value || 'email';
            Input.value = Select.value;
            Input.hidden = true;
            Input.insertAdjacentElement('afterend', Select);
            Select.addEventListener('change', () => {
                Input.value = Select.value;
                Input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            this.$Select = Select;
        }
    });
});
