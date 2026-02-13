<?php

namespace QUITests\ERP\Products\CaseStudies\Classes;

use QUI;

/**
 * Class BruttoUser
 */
class NettoUser extends QUI\Users\User
{
    public function __construct()
    {
        $this->refresh();
    }
    public function refresh(): void
    {
        $this->name = 'netto_user';
        $this->id = 0;
        $this->active = 1;
        $this->company = false;
        $this->lang = 'de';

        $this->Locale = new QUI\Locale();
        $this->Locale->setCurrent('de');

        $this->setAttribute(
            'quiqqer.erp.isNettoUser',
            QUI\ERP\Utils\User::IS_NETTO_USER
        );
    }
}
