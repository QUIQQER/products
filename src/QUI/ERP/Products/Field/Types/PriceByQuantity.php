<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Price
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

/**
 * Class PriceByQuantity
 * @package QUI\ERP\Products\Field
 */
class PriceByQuantity extends Price
{
    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getAttributes());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        $Price = new QUI\ERP\Products\Utils\Price(
            $this->cleanup($this->getValue()),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );

        return new View(array(
            'id'       => $this->getId(),
            'value'    => $Price->getDisplayPrice(),
            'title'    => $this->getTitle(),
            'prefix'   => $this->getAttribute('prefix'),
            'suffix'   => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Price';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/PriceByQuantitySettings';
    }
}
