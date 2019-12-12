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

use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

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
     *
     * @throws QUI\Exception
     */
    public static function onPackageSetup(Package $Package)
    {
        if ($Package->getName() != 'quiqqer/products') {
            return;
        }

        self::setDefaultSearchSettings();

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

        //default variant fields
        $editableFields  = [1, 3, 4, 5, 6, 9, 10, 12, 13, 16, 17, 19];
        $inheritedFields = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13, 14, 15, 16, 17, 18];

        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();

            foreach ($editableFields as $editableField) {
                $Config->set('editableFields', $editableField, 1);
            }

            foreach ($inheritedFields as $inheritedField) {
                $Config->set('inheritedFields', $inheritedField, 1);
            }

            $Config->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        // default fields
        $standardFields = [
            // Preis
            [
                'id'            => Fields::FIELD_PRICE,
                'type'          => 'Price',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 5,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles'        => [
                    'de' => 'Preis',
                    'en' => 'Price'
                ]
            ],
            // Angebotspreis
            [
                'id'            => Fields::FIELD_PRICE_OFFER,
                'type'          => 'Price',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 6,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles'        => [
                    'de' => 'Angebotspreis',
                    'en' => 'Price offer'
                ]
            ],
            // UVP - Unverbindliche Preisempfehlung
            [
                'id'            => Fields::FIELD_PRICE_RETAIL,
                'type'          => 'Price',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 6,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'showInDetails' => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles'        => [
                    'de' => 'UVP',
                    'en' => 'RRP'
                ]
            ],
            // MwSt ID
            [
                'id'            => Fields::FIELD_VAT,
                'type'          => 'Vat',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 6,
                'systemField'   => 1,
                'standardField' => 1,
                'publicField'   => 0,
                'requiredField' => 0,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'MwSt.',
                    'en' => 'Vat'
                ]
            ],
            // Artikel Nummer
            [
                'id'            => Fields::FIELD_PRODUCT_NO,
                'type'          => 'Input',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 4,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'showInDetails' => 1,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'titles'        => [
                    'de' => 'Art. Nr.',
                    'en' => 'Artikel No.'
                ]
            ],
            // Title
            [
                'id'            => Fields::FIELD_TITLE,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 1,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles'        => [
                    'de' => 'Titel',
                    'en' => 'Title'
                ]
            ],
            // Short Desc
            [
                'id'            => Fields::FIELD_SHORT_DESC,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 2,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles'        => [
                    'de' => 'Kurzbeschreibung',
                    'en' => 'Short description'
                ]
            ],
            // Content
            [
                'id'            => Fields::FIELD_CONTENT,
                'type'          => 'TextareaMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 3,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'options'       => [
                    'html' => 1
                ],
                'titles'        => [
                    'de' => 'Beschreibung',
                    'en' => 'Description'
                ]
            ],
            // Lieferant
            [
                'id'            => Fields::FIELD_SUPPLIER,
                'type'          => 'GroupList',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 9,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options'       => [
                    'multipleUsers' => false
                ],
                'titles'        => [
                    'de' => 'Lieferant',
                    'en' => 'Supplier'
                ]
            ],
            // Hersteller
            [
                'id'            => Fields::FIELD_MANUFACTURER,
                'type'          => 'GroupList',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 10,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'showInDetails' => 1,
                'search_type'   => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options'       => [
                    'multipleUsers' => false
                ],
                'titles'        => [
                    'de' => 'Hersteller',
                    'en' => 'Manufacturer'
                ]
            ],
            // Produkt Bild
            [
                'id'            => Fields::FIELD_IMAGE,
                'type'          => 'Image',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 7,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Produktbild',
                    'en' => 'Image'
                ]
            ],
            // Produkt Mediaordner
            [
                'id'            => Fields::FIELD_FOLDER,
                'type'          => 'Folder',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 8,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Media-Ordner',
                    'en' => 'Media folder'
                ]
            ],
            // Produkt Priorität
            [
                'id'            => Fields::FIELD_PRIORITY,
                'type'          => 'IntType',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 8,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 0,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Sortierung',
                    'en' => 'Sorting'
                ]
            ],
            // Stock / Lagerbestand (now supplied by quiqqer/stock-management
//            [
//                'id'            => Fields::FIELD_STOCK,
//                'type'          => 'IntType',
//                'prefix'        => '',
//                'suffix'        => '',
//                'priority'      => 9,
//                'systemField'   => 0,
//                'standardField' => 1,
//                'requiredField' => 0,
//                'publicField'   => 0,
//                'search_type'   => '',
//                'titles'        => [
//                    'de' => 'Lagerbestand',
//                    'en' => 'Total stock'
//                ]
//            ],
            // Produkt suchbegriffe
            [
                'id'            => Fields::FIELD_KEYWORDS,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 10,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'titles'        => [
                    'de' => 'Suchbegriffe',
                    'en' => 'Search keywords'
                ]
            ],
            // Produkt Zubehör
            [
                'id'            => Fields::FIELD_EQUIPMENT,
                'type'          => 'Products',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 11,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Zubehör',
                    'en' => 'Equipment'
                ]
            ],
            // Produkt Ähnliche Produkte
            [
                'id'            => Fields::FIELD_SIMILAR_PRODUCTS,
                'type'          => 'Products',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 12,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Ähnliche Produkte',
                    'en' => 'Similar Products'
                ]
            ],
            // Produkt URL
            [
                'id'            => Fields::FIELD_URL,
                'type'          => 'InputMultiLang',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 1,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'titles'        => [
                    'de' => 'Produkt URL',
                    'en' => 'Product URL'
                ]
            ],
            // Unit / Einheit
            [
                'id'            => Fields::FIELD_UNIT,
                'type'          => 'UnitSelect',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 1,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 0,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Einheit',
                    'en' => 'Unit'
                ],
                'options'       => [
                    'entries' => [
                        'kg'    => [
                            'title'         => [
                                'de' => 'kg',
                                'en' => 'kg'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'lbs'   => [
                            'title'         => [
                                'de' => 'Pfd.',
                                'en' => 'lbs.'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'g'     => [
                            'title'         => [
                                'de' => 'g',
                                'en' => 'g'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'l'     => [
                            'title'         => [
                                'de' => 'l',
                                'en' => 'l'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'm'     => [
                            'title'         => [
                                'de' => 'm',
                                'en' => 'm'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'mm'    => [
                            'title'         => [
                                'de' => 'mm',
                                'en' => 'mm'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'sqm'   => [
                            'title'         => [
                                'de' => 'm²',
                                'en' => 'm²'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'pair'  => [
                            'title'         => [
                                'de' => 'Paar',
                                'en' => 'pair'
                            ],
                            'default'       => false,
                            'quantityInput' => false
                        ],
                        'piece' => [
                            'title'         => [
                                'de' => 'Stück',
                                'en' => 'piece'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'tons'  => [
                            'title'         => [
                                'de' => 't',
                                'en' => 't'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'hours' => [
                            'title'         => [
                                'de' => 'Std.',
                                'en' => 'hrs.'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ]
                    ]
                ]
            ],
            // Weight / Gewicht
            [
                'id'            => Fields::FIELD_WEIGHT,
                'type'          => 'UnitSelect',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 1,
                'systemField'   => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 0,
                'search_type'   => '',
                'titles'        => [
                    'de' => 'Gewicht',
                    'en' => 'Weight'
                ],
                'options'       => [
                    'entries' => [
                        'kg'   => [
                            'title'         => [
                                'de' => 'kg',
                                'en' => 'kg'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'g'    => [
                            'title'         => [
                                'de' => 'g',
                                'en' => 'g'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'tons' => [
                            'title'         => [
                                'de' => 't',
                                'en' => 't'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'lbs'  => [
                            'title'         => [
                                'de' => 'Pfd.',
                                'en' => 'lbs.'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                        'oz'   => [
                            'title'         => [
                                'de' => 'oz.',
                                'en' => 'oz.'
                            ],
                            'default'       => false,
                            'quantityInput' => true
                        ],
                    ]
                ]
            ],
            // EAN
            [
                'id'            => Fields::FIELD_EAN,
                'type'          => 'Input',
                'prefix'        => '',
                'suffix'        => '',
                'priority'      => 10,
                'systemField'   => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField'   => 1,
                'showInDetails' => 0,
                'search_type'   => Search::SEARCHTYPE_TEXT,
                'titles'        => [
                    'de' => 'GTIN / EAN',
                    'en' => 'GTIN / EAN'
                ]
            ]
        ];

        foreach ($standardFields as $field) {
            $result = QUI::getDataBase()->fetch([
                'from'  => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => [
                    'id' => $field['id']
                ]
            ]);

            // update system fields
            if (isset($result[0])) {
                if ($field['id'] > 1000) {
                    continue;
                }

                QUI::getDataBase()->update(
                    QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                    [
                        'type'          => $field['type'],
                        'prefix'        => $field['prefix'],
                        'suffix'        => $field['suffix'],
                        'priority'      => $field['priority'],
                        'systemField'   => $field['systemField'],
                        'standardField' => $field['standardField'],
                        'search_type'   => $field['search_type']
                    ],
                    ['id' => $field['id']]
                );

                Fields::setFieldTranslations($field['id'], $field);

                // create / update view permission
                QUI::getPermissionManager()->addPermission([
                    'name'  => "permission.products.fields.field{$field['id']}.view",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.view.title",
                    'desc'  => "",
                    'type'  => 'bool',
                    'area'  => 'groups',
                    'src'   => 'user'
                ]);

                // create / update edit permission
                QUI::getPermissionManager()->addPermission([
                    'name'  => "permission.products.fields.field{$field['id']}.edit",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.edit.title",
                    'desc'  => "",
                    'type'  => 'bool',
                    'area'  => 'groups',
                    'src'   => 'user'
                ]);

                continue;
            }

            // create system fields
            try {
                Fields::createField($field);
            } catch (\Exception $Exception) {
                QUI\System\Log::addAlert($Exception->getMessage());
            }
        }

        // prüfen welche system felder nicht mehr existieren
        $systemFields = Fields::getFieldIds([
            'where' => [
                'systemField' => 1
            ]
        ]);

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

            if ($fieldId >= 100 || $fieldInStandardFields($fieldId)) {
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
            } catch (\Exception $Exception) {
            }
        }

        self::checkProductCacheTable();
//        Crons::updateProductCache();
    }

    /**
     * Checks if the table products_cache is correct
     *
     * @return void
     */
    public static function checkProductCacheTable()
    {
        $DB             = QUI::getDataBase();
        $categoryColumn = $DB->table()->getColumn('products_cache', 'category');

        if ($categoryColumn['Type'] !== 'varchar(255)') {
            $Stmnt = QUI::getDataBase()->getPDO()->prepare("ALTER TABLE products_cache MODIFY `category` VARCHAR(255)");
            $Stmnt->execute();
        }

        // check field columns
        $fieldColumns = $DB->table()->getColumns('products_cache');

        foreach ($fieldColumns as $column) {
            if (\mb_substr($column, 0, 1) !== 'F') {
                continue;
            }

            $fieldId = (int)\mb_substr($column, 1);

            try {
                $Field                     = Fields::getField($fieldId);
                $columnTypeExpected        = \mb_strtolower($Field->getColumnType());
                $columnTypeExpectedVariant = \preg_replace('#[\W\d]#i', '', $columnTypeExpected);

                $columnInfo       = $DB->table()->getColumn('products_cache', $column);
                $columnTypeActual = \preg_replace('#[\W\d]#i', '', $columnInfo['Type']);

                if ($columnTypeActual !== $columnTypeExpected
                    && $columnTypeActual !== $columnTypeExpectedVariant) {
                    QUI\System\Log::addCritical(
                        'Column "'.$column.'" in table "products_cache" has wrong type!'
                        .' Expected: '.$columnTypeExpected.' or '.$columnTypeExpectedVariant
                        .' | Actual: '.$columnTypeActual.'.'
                        .' Please fix manually!'
                    );
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::addError(
                    'EventHandling :: checkProductCacheTable -> ERROR on cache table column check for field #'
                    .$fieldId.': '.$Exception->getMessage()
                );
            }
        }
    }

    /**
     * Event on site load
     *
     * @param \QUI\Projects\Site $Site
     */
    public static function onSiteLoad($Site)
    {
        if ($Site->getAttribute('type') == 'quiqqer/products:types/category' ||
            $Site->getAttribute('type') == 'quiqqer/products:types/search'
        ) {
            $Site->setAttribute('nocache', 1);
        }
    }

    /**
     * Event on product category site save
     *
     * @param \QUI\Projects\Site\Edit $Site
     * @throws QUI\Exception
     */
    public static function onSiteSave($Site)
    {
        $Project = $Site->getProject();

        // register path
        if ($Site->getAttribute('active') &&
            $Site->getAttribute('type') == 'quiqqer/products:types/category'
        ) {
            $url = $Site->getLocation();
            $url = \str_replace(QUI\Rewrite::URL_DEFAULT_SUFFIX, '', $url);

            QUI::getRewrite()->registerPath($url.'/*', $Site);
        }

        // cache clearing
        $cname = 'products/search/frontend/fieldvalues/'.$Site->getId().'/'.$Project->getLang();

        QUI\ERP\Products\Search\Cache::clear($cname);
        QUI\ERP\Products\Search\Cache::clear('products/search/userfieldids/');
        QUI\ERP\Products\Search\Cache::clear('quiqqer/products/category/');
        QUI\ERP\Products\Search\Cache::clear('quiqqer/products/categories/menu');

        // field cache clearing
        $searchFieldCache = 'products/search/frontend/searchfielddata/';
        $searchFieldCache .= $Site->getId().'/';
        $searchFieldCache .= $Project->getLang().'/';

        QUI\ERP\Products\Search\Cache::clear($searchFieldCache);

        // category cache clearing
        $categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');

        if ($categoryId) {
            try {
                QUI\ERP\Products\Handler\Categories::clearCache($categoryId);
            } catch (QUI\Cache\Exception $Exception) {
            }
        }

        // the searchfield ids were initially saved
        $Site->setAttribute('quiqqer.products.settings.searchFieldIds.edited', true);
    }

    /**
     * Event on child create
     *
     * @param integer $newId
     * @param \QUI\Projects\Site\Edit $Parent
     *
     * @throws QUI\Exception
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


        $Package = QUI::getPackage('quiqqer/products');
        $Site    = new QUI\Projects\Site\Edit($Project, $newId);
        $Config  = $Package->getConfig();

        if ($Config->getValue('products', 'categoryShowFilterLeft')) {
            $Site->setAttribute('quiqqer.products.settings.showFilterLeft', 1);
        }

        if ($Config->getValue('products', 'categoryAsFilter')) {
            $Site->setAttribute('quiqqer.products.settings.categoryAsFilter', 1);
        }

        $Site->save();
    }

    /**
     * Event on product category site save
     *
     * @param \QUI\Projects\Site\Edit $Site
     *
     * @throws QUI\Exception
     */
    public static function onSiteSaveBefore($Site)
    {
        // default fields ids
        $searchFieldIds = $Site->getAttribute('quiqqer.products.settings.searchFieldIds');
        $fieldsIds      = [];

        if (empty($searchFieldIds)) {
            $searchFieldIds = [];
        }

        if (\is_string($searchFieldIds)) {
            $searchFieldIds = \json_decode($searchFieldIds, true);
        }

        foreach ($searchFieldIds as $key => $entry) {
            if (\is_numeric($key)) {
                $fieldsIds[] = $key;
            }
        }

        if (empty($fieldsIds)
            && $Site->getAttribute('quiqqer.products.settings.searchFieldIds.edited') === false
        ) {
            $Package    = QUI::getPackage('quiqqer/products');
            $defaultIds = $Package->getConfig()->get('search', 'frontend');

            if ($defaultIds) {
                $defaultIds = \explode(',', $defaultIds);

                foreach ($defaultIds as $defaultId) {
                    $fieldsIds[$defaultId] = 1;
                }

                $Site->setAttribute(
                    'quiqqer.products.settings.searchFieldIds',
                    \json_encode($fieldsIds)
                );
            }
        }
    }

    /**
     * event: onPackageInstall
     *
     * @param Package $Package
     *
     * @throws QUI\Exception
     */
    public static function onPackageInstall($Package)
    {
        if ($Package->getName() != 'quiqqer/products') {
            return;
        }

        $CronManager = new QUI\Cron\Manager();

        // which crons to set up
        $crons = [
            QUI::getLocale()->get($Package->getName(), 'cron.updateProductCache.title'),
            QUI::getLocale()->get($Package->getName(), 'cron.generateProductAttributeListTags.title')
        ];

        foreach ($crons as $cron) {
            if ($CronManager->isCronSetUp($cron)) {
                continue;
            }

            // add cron: run once every day at 0 am
            $CronManager->add($cron, '0', '0', '*', '*', '*');
        }
    }

    /**
     * event: onPackageInstall
     *
     * @param Package $Package
     * @param array $params
     */
    public static function onPackageConfigSave($Package, $params)
    {
        if ($Package->getName() != 'quiqqer/products') {
            return;
        }

        QUI\ERP\Products\Search\Cache::clear();
    }

    /**
     * event: on template get header
     *
     * @param QUI\Template $TemplateManager
     */
    public static function onTemplateGetHeader(QUI\Template $TemplateManager)
    {
        $hide = 0;

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $hide = 1;
        }

        $header = '<script type="text/javascript">';
        $header .= 'var QUIQQER_PRODUCTS_HIDE_PRICE = '.$hide.';';
        $header .= '</script>';

        $TemplateManager->extendHeader($header);
    }

    /**
     * event: on set permission to object
     *
     * @param QUI\Users\User|QUI\Groups\Group|
     *                           QUI\Projects\Project|QUI\Projects\Site|QUI\Projects\Site\Edit $Obj
     * @param array $permissions
     *
     */
    public static function onPermissionsSet($Obj, $permissions)
    {
        if ($Obj instanceof QUI\Groups\Group) {
            QUI\ERP\Products\Search\Cache::clear('products/search/userfieldids/');
        }
    }

    /**
     * event : on request
     *
     * @param QUI\Rewrite $Rewrite
     * @param $url
     */
    public static function onRequest(QUI\Rewrite $Rewrite, $url)
    {
        if (QUI::getLocale()->getCurrent() != '_p') {
            return;
        }

        $params = $Rewrite->getUrlParamsList();

        if (!\count($params)) {
            return;
        }

        try {
            $Product = Handler\Products::getProduct($params[0]);
            $Project = $Rewrite->getProject();

            if ('/_p/'.$url !== \urldecode($Product->getUrl())) {
                $Redirect = new RedirectResponse($Product->getUrl());
                $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

                echo $Redirect->getContent();
                $Redirect->send();
                exit;
            }

            $Site = $Project->firstChild();
            $Site->setAttribute('type', 'quiqqer/products:types/category');
            $Site->setAttribute('quiqqer.products.settings.categoryId', 0);
            $Site->setAttribute('quiqqer.products.fake.type', 1);
            $Site->setAttribute('layout', 'layout/noSidebar');
            $Site->setAttribute('quiqqer.bricks.areas', '');

            $_REQUEST['_url'] = '';

            $Rewrite->setSite($Site);
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Set default search settings if none are set
     *
     * @return void
     * @throws QUI\Exception
     */
    protected static function setDefaultSearchSettings()
    {
        try {
            $Conf = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return;
        }

        $search = $Conf->getSection('search');

        // Backend search fields
        if (empty($search['backend'])) {
            $search['backend'] = implode(',', [
                Fields::FIELD_PRODUCT_NO,
                Fields::FIELD_TITLE
            ]);
        }

        if (empty($search['freetext'])) {
            $search['freetext'] = implode(',', [
                Fields::FIELD_PRODUCT_NO,
                Fields::FIELD_TITLE,
                Fields::FIELD_SHORT_DESC,
                Fields::FIELD_KEYWORDS
            ]);
        }

        $Conf->setSection('search', $search);
        $Conf->save();
    }
}
