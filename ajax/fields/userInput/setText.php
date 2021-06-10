<?php

use QUI\ERP\Order\Basket\Product as BasketProduct;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Order\Handler as OrderHandler;

/**
 * Set user text to basket product
 *
 * @param int $productId
 * @param array $text
 * @return void
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_userInput_setText',
    function ($basketId, $productId, $text) {
        $text = \json_decode($text, true);

        if (!\is_array($text) || \json_last_error() !== \JSON_ERROR_NONE) {
            return;
        }

        try {
            $Basket = OrderHandler::getInstance()->getBasketFromUser(QUI::getUserBySession());

            $basketProducts = $Basket->getProducts()->getProducts();
            $productId      = (int)$productId;

            $Basket->clear();

            /** @var BasketProduct $BasketProduct */
            foreach ($basketProducts as $BasketProduct) {
                if ($BasketProduct->getId() !== $productId) {
                    $Basket->addProduct($BasketProduct);
                    continue;
                }

                $productAttributes = $BasketProduct->getAttributes();

                /** @var UniqueField $UniqueField */
                foreach ($productAttributes['fields'] as $k => $UniqueField) {
                    foreach ($text as $fieldId => $userInput) {
                        if ($UniqueField->getId() !== $fieldId) {
                            continue;
                        }

                        $fieldAttributes          = $UniqueField->getAttributes();
                        $fieldAttributes['value'] = $userInput;

                        $productAttributes['fields'][$k] = new UniqueField($fieldId, $fieldAttributes);
                    }
                }

                $NewBasketProduct = new BasketProduct($productId, $productAttributes);
                $Basket->addProduct($NewBasketProduct);
            }

            $Basket->save();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    },
    ['basketId', 'productId', 'text']
);
