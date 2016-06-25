<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */
namespace QUI\ERP\Products;

use QUI;
use QUI\Package\Package;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Search;

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

        try {
            Products::getParentMediaFolder();
        } catch (QUI\Exception $Exception) {
            // no produkt folder, we create one
            $Project = QUI::getProjectManager()->getStandard();
            $Media   = $Project->getMedia();

            $Folder = $Media->firstChild();

            try {
                $Products = $Folder->createFolder('Products');
                $Products->activate();

                $Config = QUI::getPackage('quiqqer/products')->getConfig();
                $Config->set('products', 'folder', $Products->getUrl());
                $Config->save();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning($Exception->getMessage());
            }
        }


        $standardFields = array(
            // Preis
            array(
                'id'            => Fields::FIELD_PRICE,
                'type'          => 'Price',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 5,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles'        => array(
                    'de' => 'Preis',
                    'en' => 'Price'
                )
            ),
            // MwSt ID
            array(
                'id'            => Fields::FIELD_TAX,
                'type'          => 'Vat',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 6,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'MwSt.',
                    'en' => 'Vat'
                )
            ),
            // Artikel Nummer
            array(
                'id'            => Fields::FIELD_PRODUCT_NO,
                'type'          => 'Input',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 4,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'titles'        => array(
                    'de' => 'Art. Nr.',
                    'en' => 'Artikel No.'
                )
            ),
            // Title
            array(
                'id'            => Fields::FIELD_TITLE,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 1,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => array(
                    'maxLength' => 255,
                    'minLength' => 3
                ),
                'titles'        => array(
                    'de' => 'Titel',
                    'en' => 'Title'
                )
            ),
            // Short Desc
            array(
                'id'            => Fields::FIELD_SHORT_DESC,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 2,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => array(
                    'maxLength' => 255,
                    'minLength' => 3
                ),
                'titles'        => array(
                    'de' => 'Kurzbeschreibung',
                    'en' => 'Short description'
                )
            ),
            // Content
            array(
                'id'            => Fields::FIELD_CONTENT,
                'type'          => 'TextareaMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 3,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => array(
                    'html' => 1
                ),
                'titles'        => array(
                    'de' => 'Inhalt',
                    'en' => 'Content'
                )
            ),
            // Lieferant
            array(
                'id'            => Fields::FIELD_SUPPLIER,
                'type'          => 'GroupList',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 9,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options'       => array(
                    'multipleUsers' => false
                ),
                'titles'        => array(
                    'de' => 'Lieferant',
                    'en' => 'Supplier'
                )
            ),
            // Hersteller
            array(
                'id'            => Fields::FIELD_MANUFACTURER,
                'type'          => 'GroupList',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 10,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options'       => array(
                    'multipleUsers' => false
                ),
                'titles'        => array(
                    'de' => 'Hersteller',
                    'en' => 'Manufacturer'
                )
            ),
            // Produkt Bild
            array(
                'id'            => Fields::FIELD_IMAGE,
                'type'          => 'Image',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 7,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Produktbild',
                    'en' => 'Product image'
                )
            ),
            // Produkt Mediaordner
            array(
                'id'            => Fields::FIELD_FOLDER,
                'type'          => 'Folder',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 8,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Media-Ordner',
                    'en' => 'Media folder'
                )
            ),
            // Produkt bestand
            array(
                'id'            => Fields::FIELD_STOCK,
                'type'          => 'IntType',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 9,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Lagerbestand',
                    'en' => 'Total stock'
                )
            ),
            // Produkt suchbegriffe
            array(
                'id'            => Fields::FIELD_KEYWORDS,
                'type'          => 'Textarea',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 10,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Suchbegriffe',
                    'en' => 'Search keywords'
                )
            ),
            // Produkt Zubehör
            array(
                'id'            => Fields::FIELD_EQUIPMENT,
                'type'          => 'Products',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 11,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Zubehör',
                    'en' => 'Equipment'
                )
            ),
            // Produkt Ähnliche Produkte
            array(
                'id'            => Fields::FIELD_SIMILAR_PRODUCTS,
                'type'          => 'Products',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 12,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => array(
                    'de' => 'Ähnliche Produkte',
                    'en' => 'Similar Products'
                )
            )
        );

        foreach ($standardFields as $field) {
            $result = QUI::getDataBase()->fetch(array(
                'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => array(
                    'id' => $field['id']
                )
            ));

            // update system fields
            if (isset($result[0])) {
                if ($field['id'] > 1000) {
                    continue;
                }

                QUI::getDataBase()->update(
                    QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                    array(
                        'type'          => $field['type'],
                        'prefix'        => $field['prefix'],
                        'suffix'        => $field['suffix'],
                        'priority'      => $field['priority'],
                        'systemField'   => $field['systemField'],
                        'standardField' => $field['standardField'],
                        'search_type'   => $field['search_type']
                    ),
                    array('id' => $field['id'])
                );

                Fields::setFieldTranslations($field['id'], $field);

                // create / update view permission
                QUI::getPermissionManager()->addPermission(array(
                    'name'  => "permission.products.fields.field{$field['id']}.view",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.view.title",
                    'desc'  => "",
                    'type'  => 'bool',
                    'area'  => '',
                    'src'   => 'user'
                ));

                // create / update edit permission
                QUI::getPermissionManager()->addPermission(array(
                    'name'  => "permission.products.fields.field{$field['id']}.edit",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.edit.title",
                    'desc'  => "",
                    'type'  => 'bool',
                    'area'  => '',
                    'src'   => 'user'
                ));

                continue;
            }

            // create system fields
            try {
                Fields::createField($field);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addAlert($Exception->getMessage());
            }
        }

        // prüfen welche system felder nicht mehr existieren
        $systemFields = Fields::getFieldIds(array(
            'where' => array(
                'systemField' => 1
            )
        ));

        $fieldInStandardFields = function ($fieldId) use ($standardFields) {
            foreach ($standardFields as $fieldData) {
                if ($fieldId == $fieldData['id']) {
                    return true;
                }
            }
            return false;
        };

        foreach ($systemFields as $systemFieldsId) {
            $fieldId = (int)$systemFieldsId['id'];

            if ($fieldInStandardFields($fieldId)) {
                continue;
            }

            try {
                $Field = Fields::getField($fieldId);
                $Field->deleteSystemField();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_WARNING);
            }
        }


        // field cache
        $fields = Fields::getFieldIds();

        foreach ($fields as $fieldsId) {
            $fieldId = (int)$fieldsId['id'];

            try {
                Fields::createFieldCacheColumn($fieldId);
            } catch (QUI\Exception $Exception) {
            }
        }
    }

    /**
     * Event on product category site save
     *
     * @param \QUI\Projects\Site\Edit $Site
     */
    public static function onSiteSave($Site)
    {
        // register path
        if ($Site->getAttribute('active') &&
            $Site->getAttribute('type') == 'quiqqer/products:types/category'
        ) {
            $url = $Site->getLocation();
            $url = str_replace(QUI\Rewrite::URL_DEFAULT_SUFFIX, '', $url);

            QUI::getRewrite()->registerPath($url . '/*', $Site);
        }

        // cache clearing
        $cname = 'products/search/frontend/fieldvalues/'
                 . $Site->getId() . '/' . $Site->getProject()->getLang();

        QUI\ERP\Products\Search\Cache::clear($cname);

        // category cache clearing
        $categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');

        if ($categoryId) {
            try {
                QUI\ERP\Products\Handler\Categories::clearCache($categoryId);
            } catch (QUI\Cache\Exception $Exception) {
            }
        }
    }

    /**
     * Event on child create
     *
     * @param integer $newId
     * @param \QUI\Projects\Site\Edit $Parent
     */
    public static function onSiteCreateChild($newId, $Parent)
    {
        $type = $Parent->getAttribute('type');

        if ($type != 'quiqqer/products:types/category') {
            return;
        }

        $Project = $Parent->getProject();
        $Site    = new QUI\Projects\Site\Edit($Project, $newId);

        $Site->setAttribute('type', 'quiqqer/products:types/category');
        $Site->save();
    }

    /**
     * Event on product category site save
     *
     * @param \QUI\Projects\Site\Edit $Site
     */
    public static function onSiteSaveBefore($Site)
    {
        // default fields ids
        $searchFieldIds = $Site->getAttribute('quiqqer.products.settings.searchFieldIds');
        $fieldsIds      = array();

        if ($searchFieldIds === false) {
            $searchFieldIds = array();
        }

        if (is_string($searchFieldIds)) {
            $searchFieldIds = json_decode($searchFieldIds, true);
        }


        foreach ($searchFieldIds as $key => $entry) {
            if (is_numeric($key)) {
                $fieldsIds[] = $key;
            }
        }

        if (empty($fieldsIds)) {
            $Package    = QUI::getPackage('quiqqer/products');
            $defaultIds = $Package->getConfig()->get('search', 'frontend');

            if ($defaultIds) {
                $defaultIds = explode(',', $defaultIds);

                foreach ($defaultIds as $defaultId) {
                    $fieldsIds[$defaultId] = 1;
                }

                $Site->setAttribute(
                    'quiqqer.products.settings.searchFieldIds',
                    json_encode($fieldsIds)
                );
            }
        }
    }
}
