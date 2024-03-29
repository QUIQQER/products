<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Products
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Products as ProductHandler;

/**
 * Class Products
 * @package QUI\ERP\Products\Field
 */
class Products extends QUI\ERP\Products\Field\Field
{
    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * @var bool
     */
    protected $showInDetails = true;

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Products';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param array $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (!\is_array($value)) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.invalid',
                [
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                ]
            ]);
        }
    }

    /**
     * Clean up the product ids
     *
     * @param array $value - [productId, productId, productId]
     * @return array
     */
    public function cleanup($value)
    {
        if (!\is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $productId) {
            if (ProductHandler::existsProduct($productId)) {
                $result[] = $productId;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return true;
    }
}
