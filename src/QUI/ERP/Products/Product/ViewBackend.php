<?php

/**
 * This file contains QUI\ERP\Products\Product\View
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\ERP\Products\Product
 */
class ViewBackend extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * @var Product
     */
    protected $Product;

    /**
     * View constructor.
     * @param Model $Product
     */
    public function __construct(Model $Product)
    {
        $this->Product = $Product;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->Product->getId();
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getTitle($Locale = false)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getDescription($Locale = false)
    {
        return $this->Product->getTitle($Locale);
    }

    /**
     * @param bool $Locale
     * @return string
     */
    public function getContent($Locale = false)
    {
        return $this->Product->getContent($Locale);
    }

    /**
     * @return QUI\ERP\Products\Utils\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Products\Utils\Price(
            $this->getAttribute('price'),
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * Get a FieldView
     *
     * @param integer $fieldId
     * @return QUI\ERP\Products\Field\View
     */
    public function getFieldView($fieldId)
    {
        /** @var QUI\ERP\Products\Field\Field $Field */
        $Field = $this->Product->getField($fieldId);
        return $Field->getBackendView();
    }
}
