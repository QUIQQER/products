<?php

use QUI\ERP\Tax\Handler as Tax;
use QUI\ERP\Tax\Utils as TaxUtils;

/**
 * Get vat entries that can be selected for automatic product price multiplier rounding.
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_products_ajax_settings_getVatEntries',
    function () {
        $TaxHandler = Tax::getInstance();
        $entries    = [];

        return QUI\ERP\Tax\Utils::getAvailableTaxList();

        /** @var \QUI\ERP\Tax\TaxType $TaxType */
        foreach ($TaxHandler->getTaxTypes() as $TaxType) {
            $taxTypeTitle = $TaxType->getTitle();

            /** @var \QUI\ERP\Areas\Area $Area */
            foreach (TaxUtils::getTaxEntriesByTaxType($TaxType->getId()) as $Area) {
                if ($TaxEntry instanceof \QUI\ERP\Tax\TaxEntryEmpty) {
                    continue;
                }

                $entries[] = [
                    'id'    => $TaxEntry->getId(),
                    'title' => $TaxEntry->getArea()->getTitle().' - '.$taxTypeTitle.' - '.$TaxEntry->getValue().'%',
                ];
            }
        }

        return $entries;
    },
    [],
    ['Permission::checkAdminUser']
);
