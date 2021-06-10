<?php

/**
 * Check if a product is eligible for user input
 *
 * @param int $productId
 * @param int[] - Relevant field data of user input fields
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_fields_userInput_checkProduct',
    function ($productId) {
        try {
            $Product         = \QUI\ERP\Products\Handler\Products::getProduct((int)$productId);
            $userInputFields = $Product->getFieldsByType(\QUI\ERP\Products\Handler\Fields::TYPE_USER_INPUT);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            return [];
        }

        $fieldData = [];

        /** @var \QUI\ERP\Products\Field\Types\UserInput $Field */
        foreach ($userInputFields as $Field) {
            $fieldData[] = [
                'id'           => $Field->getId(),
                'productTitle' => $Product->getTitle(),
                'fieldTitle'   => $Field->getTitle(),
                'options'      => $Field->getOptions()
            ];
        }

        return $fieldData;
    },
    ['productId']
);
