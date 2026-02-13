<?php

namespace QUITests\ERP\Products\CaseStudies\Classes;

use QUI;

/**
 * Class BruttoUser
 */
class CompanyUser extends QUI\Users\User
{
    public function __construct()
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->name = 'company_user';
        $this->id = 0;
        $this->active = 1;
        $this->company = true;
        $this->lang = 'en';

        $this->Locale = new QUI\Locale();
        $this->Locale->setCurrent('en');

        $this->setAttribute('quiqqer.erp.euVatId', 'DE263620766');
    }
}
