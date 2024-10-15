<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Products
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Products as ProductHandler;

use function is_array;

/**
 * Class Products
 * @package QUI\ERP\Products\Field
 */
class Products extends QUI\ERP\Product\Field\Field
{
    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @var bool
     */
    protected bool $showInDetails = true;

    /**
     * @return View
     */
    public function getBackendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView(): View
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/Products';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param array $value
     * @throws Exception
     */
    public function validate($value): void
    {
        if (empty($value)) {
            return;
        }

        if (!is_array($value)) {
            throw new Exception([
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
     * @param mixed $value - [productId, productId, productId]
     * @return array
     */
    public function cleanup(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $productId) {
            if (ProductHandler::existsProduct((int)$productId)) {
                $result[] = $productId;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return true;
    }
}
