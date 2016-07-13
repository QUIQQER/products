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
        $this->name    = 'company_user';
        $this->id      = 0;
        $this->active  = 1;
        $this->company = true;

        $this->setAttribute('quiqqer.erp.euVatId', 'DE263620766');
    }
}
