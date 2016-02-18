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
                'id' => Fields::FIELD_PRICE,
                'type' => 'Price',
                'prefix' => '',
                'suffix' => '',
                'priority' => 5,
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
                'id' => Fields::FIELD_TAX,
                'type' => 'Vat',
                'prefix' => '',
                'suffix' => '',
                'priority' => 6,
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
                'id' => Fields::FIELD_PRODUCT_NO,
                'type' => 'Input',
                'prefix' => '',
                'suffix' => '',
                'priority' => 4,
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
                'id' => Fields::FIELD_TITLE,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
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
                'id' => Fields::FIELD_SHORT_DESC,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 2,
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
                'id' => Fields::FIELD_CONTENT,
                'type' => 'TextareaMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 3,
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
                'id' => Fields::FIELD_SUPPLIER,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => 9,
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
                'id' => Fields::FIELD_MANUFACTURER,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => 10,
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
            ),
            // Produkt Bild
            array(
                'id' => Fields::FIELD_IMAGE,
                'type' => 'Image',
                'prefix' => '',
                'suffix' => '',
                'priority' => 7,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'titles' => array(
                    'de' => 'Produktbild',
                    'en' => 'Product image'
                )
            ),
            // Produkt mediaordner
            array(
                'id' => Fields::FIELD_FOLDER,
                'type' => 'Folder',
                'prefix' => '',
                'suffix' => '',
                'priority' => 8,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'titles' => array(
                    'de' => 'Media-Ordner',
                    'en' => 'Media folder'
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
                if ($field['id'] > 1000) {
                    continue;
                }

                QUI::getDataBase()->update(
                    QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                    array(
                        'type' => $field['type'],
                        'prefix' => $field['prefix'],
                        'suffix' => $field['suffix'],
                        'priority' => $field['priority']
                    ),
                    array('id' => $field['id'])
                );
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
