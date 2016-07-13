<?php

namespace QUITests\ERP\Products\CaseStudies\Classes;

use QUI;

/**
 * Class BruttoUser
 * @package QUITests\ERP\Products\CaseStudies\Classes
 */
class NettoUser extends QUI\Users\User
{
    public function __construct()
    {
        $this->refresh();
    }

    public function refresh()
    {
        $this->name    = 'netto_user';
        $this->id      = 0;
        $this->active  = 1;
        $this->company = false;

        $this->setAttribute(
            'quiqqer.erp.isNettoUser',
            QUI\ERP\Products\Utils\User::IS_NETTO_USER
        );
    }
}
