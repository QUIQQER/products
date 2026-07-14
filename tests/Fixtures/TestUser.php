<?php

namespace QUITests\ERP\Products\Fixtures;

use QUI;

class TestUser extends QUI\Users\User
{
    public const TYPE_BRUTTO = 'brutto';
    public const TYPE_NETTO = 'netto';
    public const TYPE_COMPANY = 'company';

    public function __construct(private readonly string $userType = self::TYPE_BRUTTO)
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->name = 'phpunit_products_' . $this->userType;
        $this->id = 0;
        $this->uuid = 'phpunit-products-' . $this->userType;
        $this->active = 1;
        $this->company = $this->userType === self::TYPE_COMPANY;
        $this->lang = 'de';
        $this->Locale = new QUI\Locale();
        $this->Locale->setCurrent('de');

        if ($this->userType === self::TYPE_NETTO) {
            $this->setAttribute('quiqqer.erp.isNettoUser', QUI\ERP\Utils\User::IS_NETTO_USER);
            return;
        }

        if ($this->userType === self::TYPE_COMPANY) {
            $this->setAttribute('quiqqer.erp.euVatId', 'DE263620766');
            return;
        }

        $this->setAttribute('quiqqer.erp.isNettoUser', QUI\ERP\Utils\User::IS_BRUTTO_USER);
    }
}
