<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */
namespace QUI\ERP\Products;

use QUI;
use QUI\Package\Package;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Class EventHandling
 *
 * @package QUI\ERP\Products
 */
class EventHandling
{
    /**
     * Runs the setup for products
     *
     * - import the default system fields
     *
     * @param Package $Package
     */
    public static function onPackageSetup(Package $Package)
    {
        if ($Package->getName() != 'quiqqer/products') {
            return;
        }

        $standardFields = array(
            // Preis
            array(
                'id' => 1,
                'type' => 'Price',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'titles' => array(
                    'de' => 'Preis',
                    'en' => 'Price'
                )
            ),
            // MwSt ID
            array(
                'id' => 2,
                'type' => 'Vat',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'titles' => array(
                    'de' => 'MwSt.',
                    'en' => 'Vat'
                )
            ),
            // Artikel Nummer
            array(
                'id' => 3,
                'type' => 'Input',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'titles' => array(
                    'de' => 'Artikel Nummer',
                    'en' => 'Artikel No.'
                )
            ),
            // Title
            array(
                'id' => 4,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'options' => array(
                    'maxLength' => 255,
                    'minLength' => 3
                ),
                'titles' => array(
                    'de' => 'Titel',
                    'en' => 'Title'
                )
            ),
            // Short Desc
            array(
                'id' => 5,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'options' => array(
                    'maxLength' => 255,
                    'minLength' => 3
                ),
                'titles' => array(
                    'de' => 'Kurzbeschreibung',
                    'en' => 'Short description'
                )
            ),
            // Content
            array(
                'id' => 6,
                'type' => 'TextareaMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'options' => array(
                    'html' => 1
                ),
                'titles' => array(
                    'de' => 'Inhalt',
                    'en' => 'Content'
                )
            ),
            // Lieferant
            array(
                'id' => 7,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'options' => array(
                    'html' => 1
                ),
                'titles' => array(
                    'de' => 'Lieferant',
                    'en' => 'Supplier'
                )
            ),
            // Hersteller
            array(
                'id' => 8,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => '',
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'options' => array(
                    'html' => 1
                ),
                'titles' => array(
                    'de' => 'Hersteller',
                    'en' => 'Manufacturer'
                )
            )
        );

        foreach ($standardFields as $field) {
            $result = QUI::getDataBase()->fetch(array(
                'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => array(
                    'id' => $field['id']
                )
            ));

            if (isset($result[0])) {
                continue;
            }

            try {
                Fields::createField($field);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addAlert($Exception->getMessage());
            }


//
//            if (isset($field['options'])) {
//                $field['options'] = json_encode($field['options']);
//            }
//
//            QUI::getDataBase()->insert(
//                QUI\ERP\Products\Utils\Tables::getFieldTableName(),
//                $field
//            );
        }
    }
}
