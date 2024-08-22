<?php

namespace QUI\ERP\Products;

use Exception;
use QUI;
use QUI\ERP\Products\Handler\Cache;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Search;
use QUI\ERP\Products\Utils\Tables;
use QUI\Package\Package;
use QUI\Projects\Site\Edit;
use QUI\System\Console\Tools\MigrationV2;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use QUI\ERP\Products\Field\Types\AttributeGroup;

use function count;
use function explode;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function str_replace;
use function trim;

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
    public static function onPackageSetup(Package $Package): void
    {
        if ($Package->getName() != 'quiqqer/products') {
            return;
        }

        QUI\ERP\Products\Handler\Manufacturers::registerManufacturerUrlPaths();

        self::patchProductTypes();
        self::setDefaultMediaFolder();
        self::setDefaultVariantFields();
        self::setDefaultProductFields();
        self::checkProductCacheTable();
//        Crons::updateProductCache();

        // Clear specific cache paths
        QUI\Cache\LongTermCache::clear(
            QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'price_field_types'
        );
    }

    /**
     * Set the default media folder for all products
     *
     * @return void
     * @throws QUI\Exception
     */
    protected static function setDefaultMediaFolder(): void
    {
        try {
            Products::getParentMediaFolder();
        } catch (QUI\Exception) {
            // no produkt folder, we create one
            $Project = QUI::getProjectManager()->getStandard();
            $Media = $Project->getMedia();

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
    }

    /**
     * Set default editable and inheritable fields for product variants
     *
     * @return void
     */
    protected static function setDefaultVariantFields(): void
    {
        try {
            $Config = QUI::getPackage('quiqqer/products')->getConfig();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return;
        }

        // Check current config for fields that may not exist anymore
        $editableFields = $Config->getSection('editableFields');

        if (!empty($editableFields) && is_array($editableFields)) {
            foreach ($editableFields as $fieldId => $active) {
                try {
                    Fields::getField($fieldId);
                } catch (QUI\ERP\Products\Field\Exception $Exception) {
                    if ($Exception->getCode() === 404) {
                        QUI\System\Log::addInfo(
                            'Removed product field #' . $fieldId . ' from the [editableFields] section in '
                            . $Config->getFilename()
                        );

                        unset($editableFields[$fieldId]);
                    }
                } catch (Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }

            $Config->setSection('editableFields', $editableFields);
        }

        $inheritedFields = $Config->getSection('inheritedFields');

        if (!empty($inheritedFields) && is_array($inheritedFields)) {
            foreach ($inheritedFields as $fieldId => $active) {
                try {
                    Fields::getField($fieldId);
                } catch (QUI\ERP\Products\Field\Exception $Exception) {
                    if ($Exception->getCode() === 404) {
                        QUI\System\Log::addInfo(
                            'Removed product field #' . $fieldId . ' from the [inheritedFields] section in '
                            . $Config->getFilename() . ' because the field no longer exists.'
                        );

                        unset($inheritedFields[$fieldId]);
                    }
                } catch (Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }

            $Config->setSection('inheritedFields', $inheritedFields);
        }

        // Set default fields
        $defaultEditableFields = [
            Fields::FIELD_PRICE,
            Fields::FIELD_PRODUCT_NO,
            Fields::FIELD_TITLE,
            Fields::FIELD_SHORT_DESC,
            Fields::FIELD_CONTENT,
            Fields::FIELD_IMAGE,
            Fields::FIELD_FOLDER,
            Fields::FIELD_KEYWORDS,
            Fields::FIELD_PRICE_OFFER,
            Fields::FIELD_PRICE_RETAIL,
            Fields::FIELD_URL,
            Fields::FIELD_UNIT,
            Fields::FIELD_EAN
        ];

        $defaultInheritedFields = [
            Fields::FIELD_PRICE,
            Fields::FIELD_VAT,
            Fields::FIELD_TITLE,
            Fields::FIELD_SHORT_DESC,
            Fields::FIELD_CONTENT,
            Fields::FIELD_SUPPLIER,
            Fields::FIELD_MANUFACTURER,
            Fields::FIELD_IMAGE,
            Fields::FIELD_FOLDER,
            Fields::FIELD_KEYWORDS,
            Fields::FIELD_EQUIPMENT,
            Fields::FIELD_SIMILAR_PRODUCTS,
            Fields::FIELD_PRICE_OFFER,
            Fields::FIELD_PRICE_RETAIL,
            Fields::FIELD_PRIORITY,
            Fields::FIELD_UNIT,
            Fields::FIELD_EAN
        ];

        try {
            foreach ($defaultEditableFields as $fieldId) {
                $Config->set('editableFields', $fieldId, 1);
            }

            foreach ($defaultInheritedFields as $fieldId) {
                $Config->set('inheritedFields', $fieldId, 1);
            }

            $Config->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Product field setup
     *
     * @return void
     * @throws QUI\Database\Exception
     */
    protected static function setDefaultProductFields(): void
    {
        $standardFields = [
            // Preis
            [
                'id' => Fields::FIELD_PRICE,
                'type' => 'Price',
                'prefix' => '',
                'suffix' => '',
                'priority' => 5,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles' => [
                    'de' => 'Preis',
                    'en' => 'Price'
                ]
            ],
            // Angebotspreis
            [
                'id' => Fields::FIELD_PRICE_OFFER,
                'type' => 'Price',
                'prefix' => '',
                'suffix' => '',
                'priority' => 6,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles' => [
                    'de' => 'Angebotspreis',
                    'en' => 'Price offer'
                ]
            ],
            // UVP - Unverbindliche Preisempfehlung
            [
                'id' => Fields::FIELD_PRICE_RETAIL,
                'type' => 'Price',
                'prefix' => '',
                'suffix' => '',
                'priority' => 6,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'showInDetails' => 1,
                'search_type' => Search::SEARCHTYPE_INPUTSELECTRANGE,
                'titles' => [
                    'de' => 'UVP',
                    'en' => 'RRP'
                ],
                'options' => [
                    'ignoreForPriceCalculation' => 1
                ]
            ],
            // MwSt ID
            [
                'id' => Fields::FIELD_VAT,
                'type' => 'Vat',
                'prefix' => '',
                'suffix' => '',
                'priority' => 6,
                'systemField' => 1,
                'standardField' => 1,
                'publicField' => 0,
                'requiredField' => 0,
                'search_type' => '',
                'titles' => [
                    'de' => 'MwSt.',
                    'en' => 'Vat'
                ]
            ],
            // Artikel Nummer
            [
                'id' => Fields::FIELD_PRODUCT_NO,
                'type' => 'Input',
                'prefix' => '',
                'suffix' => '',
                'priority' => 4,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'showInDetails' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'Art. Nr.',
                    'en' => 'Artikel No.'
                ]
            ],
            // Title
            [
                'id' => Fields::FIELD_TITLE,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 1,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'options' => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles' => [
                    'de' => 'Titel',
                    'en' => 'Title'
                ]
            ],
            // Short Desc
            [
                'id' => Fields::FIELD_SHORT_DESC,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 2,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'options' => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles' => [
                    'de' => 'Kurzbeschreibung',
                    'en' => 'Short description'
                ]
            ],
            // Content
            [
                'id' => Fields::FIELD_CONTENT,
                'type' => 'TextareaMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 3,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'options' => [
                    'html' => 1
                ],
                'titles' => [
                    'de' => 'Beschreibung',
                    'en' => 'Description'
                ]
            ],
            // Lieferant
            [
                'id' => Fields::FIELD_SUPPLIER,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => 9,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options' => [
                    'multipleUsers' => false
                ],
                'titles' => [
                    'de' => 'Lieferant',
                    'en' => 'Supplier'
                ]
            ],
            // Hersteller
            [
                'id' => Fields::FIELD_MANUFACTURER,
                'type' => 'GroupList',
                'prefix' => '',
                'suffix' => '',
                'priority' => 10,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'showInDetails' => 1,
                'search_type' => Search::SEARCHTYPE_INPUTSELECTSINGLE,
                'options' => [
                    'multipleUsers' => false
                ],
                'titles' => [
                    'de' => 'Hersteller',
                    'en' => 'Manufacturer'
                ]
            ],
            // Produkt Bild
            [
                'id' => Fields::FIELD_IMAGE,
                'type' => 'Image',
                'prefix' => '',
                'suffix' => '',
                'priority' => 7,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => '',
                'titles' => [
                    'de' => 'Produktbild',
                    'en' => 'Image'
                ]
            ],
            // Produkt Mediaordner
            [
                'id' => Fields::FIELD_FOLDER,
                'type' => 'Folder',
                'prefix' => '',
                'suffix' => '',
                'priority' => 8,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => '',
                'titles' => [
                    'de' => 'Media-Ordner',
                    'en' => 'Media folder'
                ]
            ],
            // Produkt Priorität
            [
                'id' => Fields::FIELD_PRIORITY,
                'type' => 'IntType',
                'prefix' => '',
                'suffix' => '',
                'priority' => 8,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => '',
                'titles' => [
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
                'id' => Fields::FIELD_KEYWORDS,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 10,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'Suchbegriffe',
                    'en' => 'Search keywords'
                ]
            ],
            // Produkt Zubehör
            [
                'id' => Fields::FIELD_EQUIPMENT,
                'type' => 'Products',
                'prefix' => '',
                'suffix' => '',
                'priority' => 11,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => '',
                'titles' => [
                    'de' => 'Zubehör',
                    'en' => 'Equipment'
                ]
            ],
            // Produkt Ähnliche Produkte
            [
                'id' => Fields::FIELD_SIMILAR_PRODUCTS,
                'type' => 'Products',
                'prefix' => '',
                'suffix' => '',
                'priority' => 12,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => '',
                'titles' => [
                    'de' => 'Ähnliche Produkte',
                    'en' => 'Similar Products'
                ]
            ],
            // Produkt URL
            [
                'id' => Fields::FIELD_URL,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'Produkt URL',
                    'en' => 'Product URL'
                ]
            ],
            // Unit / Einheit (NOT packaging unit / NICHT Verpackungseinheit!)
            [
                'id' => Fields::FIELD_UNIT,
                'type' => 'UnitSelect',
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => '',
                'titles' => [
                    'de' => 'Einheit',
                    'en' => 'Unit'
                ],
                'options' => [
                    'entries' => [
                        'kg' => [
                            'title' => [
                                'de' => 'kg',
                                'en' => 'kg'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'lbs' => [
                            'title' => [
                                'de' => 'Pfd.',
                                'en' => 'lbs.'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'g' => [
                            'title' => [
                                'de' => 'g',
                                'en' => 'g'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'l' => [
                            'title' => [
                                'de' => 'l',
                                'en' => 'l'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'm' => [
                            'title' => [
                                'de' => 'm',
                                'en' => 'm'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'mm' => [
                            'title' => [
                                'de' => 'mm',
                                'en' => 'mm'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'sqm' => [
                            'title' => [
                                'de' => 'm²',
                                'en' => 'm²'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'pair' => [
                            'title' => [
                                'de' => 'Paar',
                                'en' => 'pair'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'piece' => [
                            'title' => [
                                'de' => 'Stück',
                                'en' => 'piece'
                            ],
                            'default' => true,
                            'quantityInput' => false,
                            'defaultQuantity' => 1
                        ],
                        'tons' => [
                            'title' => [
                                'de' => 't',
                                'en' => 't'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ],
                        'hours' => [
                            'title' => [
                                'de' => 'Std.',
                                'en' => 'hrs.'
                            ],
                            'default' => false,
                            'quantityInput' => false,
                            'defaultQuantity' => false
                        ]
                    ]
                ]
            ],
            // Weight / Gewicht
            [
                'id' => Fields::FIELD_WEIGHT,
                'type' => 'UnitSelect',
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => '',
                'titles' => [
                    'de' => 'Gewicht',
                    'en' => 'Weight'
                ],
                'options' => [
                    'entries' => [
                        'kg' => [
                            'title' => [
                                'de' => 'kg',
                                'en' => 'kg'
                            ],
                            'default' => false,
                            'quantityInput' => true,
                            'defaultQuantity' => false
                        ],
                        'g' => [
                            'title' => [
                                'de' => 'g',
                                'en' => 'g'
                            ],
                            'default' => false,
                            'quantityInput' => true,
                            'defaultQuantity' => false
                        ],
                        'tons' => [
                            'title' => [
                                'de' => 't',
                                'en' => 't'
                            ],
                            'default' => false,
                            'quantityInput' => true,
                            'defaultQuantity' => false
                        ],
                        'lbs' => [
                            'title' => [
                                'de' => 'Pfd.',
                                'en' => 'lbs.'
                            ],
                            'default' => false,
                            'quantityInput' => true,
                            'defaultQuantity' => false
                        ],
                        'oz' => [
                            'title' => [
                                'de' => 'oz.',
                                'en' => 'oz.'
                            ],
                            'default' => false,
                            'quantityInput' => true,
                            'defaultQuantity' => false
                        ],
                    ]
                ]
            ],
            // EAN
            [
                'id' => Fields::FIELD_EAN,
                'type' => 'Input',
                'prefix' => '',
                'suffix' => '',
                'priority' => 10,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'showInDetails' => 0,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'GTIN / EAN',
                    'en' => 'GTIN / EAN'
                ]
            ],

            // variant
            [
                'id' => Fields::FIELD_VARIANT_DEFAULT_ATTRIBUTES,
                'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 0,
                'standardField' => 0,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'Produktvarianten',
                    'en' => 'Product variants'
                ]
            ],
            // Title SEO
            [
                'id' => Fields::FIELD_SEO_TITLE,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 11,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'options' => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles' => [
                    'de' => 'SEO Titel',
                    'en' => 'SEO Title'
                ]
            ],
            // Short Desc SEO
            [
                'id' => Fields::FIELD_SEO_DESCRIPTION,
                'type' => 'InputMultiLang',
                'prefix' => '',
                'suffix' => '',
                'priority' => 11,
                'systemField' => 1,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 0,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'options' => [
                    'maxLength' => 255,
                    'minLength' => 3
                ],
                'titles' => [
                    'de' => 'SEO Kurzbeschreibung',
                    'en' => 'SEO Short description'
                ]
            ],
            // Condition
            [
                'id' => Fields::FIELD_CONDITION,
                'type' => Fields::TYPE_ATTRIBUTE_GROUPS,
                'prefix' => '',
                'suffix' => '',
                'priority' => 1,
                'systemField' => 0,
                'standardField' => 1,
                'requiredField' => 0,
                'publicField' => 1,
                'search_type' => Search::SEARCHTYPE_TEXT,
                'titles' => [
                    'de' => 'Zustand',
                    'en' => 'Condition'
                ],
                'options' => [
                    'entries_type' => AttributeGroup::ENTRIES_TYPE_CONDITION,
                    'entries' => [
                        [
                            'title' => [
                                'de' => 'neu',
                                'en' => 'new'
                            ],
                            'valueId' => 'new',
                            'selected' => true
                        ],
                        [
                            'title' => [
                                'de' => 'generalüberholt',
                                'en' => 'refurbished'
                            ],
                            'valueId' => 'refurbished'
                        ],
                        [
                            'title' => [
                                'de' => 'gebraucht',
                                'en' => 'used'
                            ],
                            'valueId' => 'used'
                        ]
                    ]
                ]
            ],
        ];

        foreach ($standardFields as $field) {
            $result = QUI::getDataBase()->fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                'where' => [
                    'id' => $field['id']
                ]
            ]);

            // update system fields
            if (isset($result[0])) {
                // @phpstan-ignore-next-line
                if ((int)$field['id'] > 1000) {
                    continue;
                }

                QUI::getDataBase()->update(
                    QUI\ERP\Products\Utils\Tables::getFieldTableName(),
                    [
                        'type' => $field['type'],
                        'prefix' => $result[0]['prefix'] ?: $field['prefix'],
                        'suffix' => $result[0]['suffix'] ?: $field['suffix'],
                        'priority' => $result[0]['priority'] ?: $field['priority'],
                        'systemField' => $field['systemField'],
                        'standardField' => $result[0]['standardField'] ?: $field['standardField'],
                        'search_type' => $result[0]['search_type'] ?: $field['search_type']
                    ],
                    ['id' => $field['id']]
                );

                Fields::setFieldTranslations($field['id'], $field);

                // create / update view permission
                QUI::getPermissionManager()->addPermission([
                    'name' => "permission.products.fields.field{$field['id']}.view",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.view.title",
                    'desc' => "",
                    'type' => 'bool',
                    'area' => 'groups',
                    'src' => 'user'
                ]);

                // create / update edit permission
                QUI::getPermissionManager()->addPermission([
                    'name' => "permission.products.fields.field{$field['id']}.edit",
                    'title' => "quiqqer/products permission.products.fields.field{$field['id']}.edit.title",
                    'desc' => "",
                    'type' => 'bool',
                    'area' => 'groups',
                    'src' => 'user'
                ]);

                continue;
            }

            // create system fields
            try {
                Fields::createField($field);
            } catch (Exception $Exception) {
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
            } catch (Exception) {
            }
        }
    }

    /**
     * PATCH
     *
     * Updates the `type` column in `products` / `products_cache` so that the class string does not
     * have a leading backslash.
     *
     * @return void
     */
    public static function patchProductTypes(): void
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                'where' => [
                    'type' => [
                        'type' => 'LIKE%',
                        'value' => '\\\\QUI'
                    ]
                ],
                'limit' => 1
            ]);

            if (empty($result)) {
                return;
            }

            $sql = "UPDATE `" . Tables::getProductTableName() . "` SET `type` = REPLACE(`type`, '\\\\QUI', 'QUI');";
            QUI::getDataBase()->execSQL($sql);

            $sql = "UPDATE `" . Tables::getProductCacheTableName() .
                "` SET `type` = REPLACE(`type`, '\\\\QUI', 'QUI');";
            QUI::getDataBase()->execSQL($sql);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Checks if the table products_cache is correct
     *
     * @return void
     */
    public static function checkProductCacheTable(): void
    {
        $DB = QUI::getDataBase();
        $categoryColumn = $DB->table()->getColumn('products_cache', 'category');

        if ($categoryColumn['Type'] !== 'varchar(255)') {
            $Stmnt = QUI::getDataBase()->getPDO()->prepare("ALTER TABLE products_cache MODIFY `category` VARCHAR(255)");
            $Stmnt->execute();
        }

        // check field columns
        $fieldColumns = $DB->table()->getColumns('products_cache');
        $cacheTbl = QUI::getDBTableName('products_cache');

        foreach ($fieldColumns as $column) {
            if (mb_substr($column, 0, 1) !== 'F') {
                continue;
            }

            $fieldId = (int)mb_substr($column, 1);

            try {
                $Field = Fields::getField($fieldId);
                $columnTypeExpected = mb_strtolower($Field->getColumnType());
                $columnTypeExpectedVariant = preg_replace('#[\W\d]#i', '', $columnTypeExpected);

                $columnInfo = $DB->table()->getColumn($cacheTbl, $column);
                $columnTypeActual = preg_replace('#[\W\d]#i', '', $columnInfo['Type']);

                if ($columnTypeActual !== $columnTypeExpected && $columnTypeActual !== $columnTypeExpectedVariant) {
                    QUI\System\Log::addCritical(
                        'Column "' . $column . '" in table "products_cache" has wrong type!'
                        . ' Expected: ' . $columnTypeExpected . ' or ' . $columnTypeExpectedVariant
                        . ' | Actual: ' . $columnTypeActual . '.'
                        . ' Please fix manually!'
                    );
                }
            } catch (QUI\ERP\Products\Field\Exception $Exception) {
                // If field was not found -> remove from cache table
                if ($Exception->getCode() === 404) {
                    $DB->table()->deleteColumn($cacheTbl, $column);

                    QUI\System\Log::addInfo(
                        'quiqqer/products :: Deleted column "' . $column . '" from table "' . $cacheTbl . '" because'
                        . ' product field #' . $fieldId . ' does not exist anymore.'
                    );
                } else {
                    QUI\System\Log::addError(
                        'EventHandling :: checkProductCacheTable -> ERROR on cache table column check for field #'
                        . $fieldId . ': ' . $Exception->getMessage()
                    );
                }
            } catch (Exception $Exception) {
                QUI\System\Log::addError(
                    'EventHandling :: checkProductCacheTable -> ERROR on cache table column check for field #'
                    . $fieldId . ': ' . $Exception->getMessage()
                );
            }
        }
    }

    /**
     * Event on site load
     *
     * @param QUI\Interfaces\Projects\Site $Site
     */
    public static function onSiteLoad(QUI\Interfaces\Projects\Site $Site): void
    {
        $type = $Site->getAttribute('type');

        if ($type == 'quiqqer/products:types/category' || $type == 'quiqqer/products:types/search') {
            $Site->setAttribute('nocache', 1);
        }
    }

    /**
     * Event on product category site save
     *
     * @param QUI\Interfaces\Projects\Site $Site
     * @throws QUI\Exception
     */
    public static function onSiteSave(QUI\Interfaces\Projects\Site $Site): void
    {
        $Project = $Site->getProject();

        // register path
        if ($Site->getAttribute('active') && $Site->getAttribute('type') == 'quiqqer/products:types/category') {
            $url = $Site->getLocation();
            $url = str_replace(QUI\Rewrite::URL_DEFAULT_SUFFIX, '', $url);

            QUI::getRewrite()->registerPath($url . '/*', $Site);

            // Clear category menu cache
            QUI\Cache\LongTermCache::clear(
                QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'categories/menu'
            );
        }

        // cache clearing
        $cname = 'products/search/frontend/fieldvalues/' . $Site->getId() . '/' . $Project->getLang();

        QUI\ERP\Products\Search\Cache::clear($cname);
        QUI\ERP\Products\Search\Cache::clear('products/search/userfieldids/');
        QUI\ERP\Products\Search\Cache::clear('quiqqer/products/category/');
        QUI\ERP\Products\Search\Cache::clear('quiqqer/products/categories/menu');

        // field cache clearing
        $searchFieldCache = 'products/search/frontend/searchfielddata/';
        $searchFieldCache .= $Site->getId() . '/';
        $searchFieldCache .= $Project->getLang() . '/';

        QUI\ERP\Products\Search\Cache::clear($searchFieldCache);

        // category cache clearing
        $categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');

        if ($categoryId) {
            QUI\ERP\Products\Handler\Categories::clearCache($categoryId);
        }

        // the search field ids were initially saved
        $Site->setAttribute('quiqqer.products.settings.searchFieldIds.edited', true);
    }

    /**
     * Event on child create
     *
     * @param integer $newId
     * @param QUI\Interfaces\Projects\Site $Parent
     *
     * @throws QUI\Exception
     */
    public static function onSiteCreateChild(int $newId, QUI\Interfaces\Projects\Site $Parent): void
    {
        $type = $Parent->getAttribute('type');

        if ($type != 'quiqqer/products:types/category') {
            return;
        }

        $Project = $Parent->getProject();
        $Site = new Edit($Project, $newId);

        $Site->setAttribute('type', 'quiqqer/products:types/category');
        $Site->save();


        $Package = QUI::getPackage('quiqqer/products');
        $Site = new Edit($Project, $newId);
        $Config = $Package->getConfig();

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
     * @param QUI\Interfaces\Projects\Site $Site $Site
     * @throws QUI\Exception
     */
    public static function onSiteSaveBefore(QUI\Interfaces\Projects\Site $Site): void
    {
        // default fields ids
        $searchFieldIds = $Site->getAttribute('quiqqer.products.settings.searchFieldIds');
        $fieldsIds = [];

        if (empty($searchFieldIds)) {
            $searchFieldIds = [];
        }

        if (is_string($searchFieldIds)) {
            $searchFieldIds = json_decode($searchFieldIds, true);
        }

        foreach ($searchFieldIds as $key => $entry) {
            if (is_numeric($key)) {
                $fieldsIds[] = $key;
            }
        }

        if (empty($fieldsIds) && $Site->getAttribute('quiqqer.products.settings.searchFieldIds.edited') === false) {
            $Package = QUI::getPackage('quiqqer/products');
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

    /**
     * event: onPackageInstallAfter
     *
     * @param Package $Package
     */
    public static function onPackageInstallAfter(Package $Package): void
    {
        // Clear some cache paths if any new package is installed
        $clearCachePaths = [
            Cache::getBasicCachePath() . 'fields/'
        ];

        foreach ($clearCachePaths as $cachePath) {
            QUI\Cache\Manager::clear($cachePath);
        }
    }

    /**
     * event: onPackageInstall
     *
     * @param Package $Package
     * @param array $params
     * @throws QUI\Exception
     */
    public static function onPackageConfigSave(Package $Package, array $params): void
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
    public static function onTemplateGetHeader(QUI\Template $TemplateManager): void
    {
        $hide = 0;

        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            $hide = 1;
        }

        $frontendAnimation = (int)QUI\ERP\Products\Utils\Package::getConfig()->get(
            'products',
            'frontendAnimationDuration'
        );


        $header = '<script type="text/javascript">';
        $header .= 'var QUIQQER_PRODUCTS_HIDE_PRICE = ' . $hide . ';';
        $header .= 'var QUIQQER_PRODUCTS_FRONTEND_ANIMATION = ' . $frontendAnimation . ';';
        $header .= '</script>';

        $TemplateManager->extendHeader($header);
    }

    /**
     * event: on set permission to object
     *
     * @param QUI\Users\User|QUI\Groups\Group|QUI\Projects\Project|QUI\Projects\Site|QUI\Projects\Site\Edit $Obj
     * @param array $permissions
     *
     * @throws QUI\Exception
     */
    public static function onPermissionsSet(mixed $Obj, array $permissions): void
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
    public static function onRequest(QUI\Rewrite $Rewrite, $url): void
    {
        if (!isset($_GET['_url'])) {
            return;
        }

        $getUrl = $_GET['_url'];
        $getUrl = trim($getUrl, '/');
        $urlParts = explode('/', $getUrl);

        if ($urlParts[0] != '_p') {
            return;
        }

        $params = $Rewrite->getUrlParamsList();

        if (!count($params)) {
            return;
        }

        if (!isset($params[1])) {
            return;
        }

        try {
            $Product = Handler\Products::getProduct($params[1]);
            $Project = $Rewrite->getProject();
            $productUrl = $Product->getUrl();

            if (!str_contains($productUrl, '/_p/')) {
                $Redirect = new RedirectResponse($Product->getUrl());
                $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

                echo $Redirect->getContent();
                $Redirect->send();
                exit;
            }

            QUI\System\Log::addInfo(
                'There is no product category for the products. Please create a product category in your project.'
            );

            $Site = $Project->firstChild();
            $Site->setAttribute('type', 'quiqqer/products:types/category');
            $Site->setAttribute('quiqqer.products.settings.categoryId', 0);
            $Site->setAttribute('quiqqer.products.fake.type', 1);
            $Site->setAttribute('layout', 'layout/noSidebar');
            $Site->setAttribute('quiqqer.bricks.areas', '');

            $_REQUEST['_url'] = '';

            $Rewrite->setSite($Site);
        } catch (QUI\Exception) {
        }
    }

    /**
     * events: frontend cache clearing
     */
    public static function onFrontendCacheClear(): void
    {
        QUI\Cache\LongTermCache::clear('quiqqer/product/frontend');
    }

    /**
     * @param QUI\ERP\Order\AbstractOrder $Order
     */
    public static function onQuiqqerOrderSuccessful(QUI\ERP\Order\AbstractOrder $Order): void
    {
        $Articles = $Order->getArticles();

        foreach ($Articles as $Article) {
            /* @var $Article QUI\ERP\Accounting\Article */
            $productId = $Article->getId();
            $quantity = $Article->getQuantity();

            try {
                $result = QUI::getDataBase()->fetch([
                    'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
                    'where' => [
                        'id' => $productId
                    ],
                    'limit' => 1
                ]);

                if (isset($result[0])) {
                    $orderCount = (int)$result[0]['orderCount'];
                    $orderCount = $orderCount + $quantity;

                    QUI::getDataBase()->update(
                        QUI\ERP\Products\Utils\Tables::getProductTableName(),
                        ['orderCount' => $orderCount],
                        ['id' => $productId]
                    );
                }
            } catch (QUI\Exception) {
            }
        }
    }

    /**
     * @param string $group
     * @param string $var
     * @param string $packageName
     * @param array $data
     */
    public static function onQuiqqerTranslatorEdit(
        string $group,
        string $var,
        string $packageName,
        array $data
    ): void {
        if ($group !== 'quiqqer/products') {
            return;
        }

        if (!str_contains($var, 'products.category.')) {
            return;
        }

        $catId = str_replace('products.category.', '', $var);
        $catId = str_replace('.title', '', $catId);
        $catId = str_replace('.description', '', $catId);

        if (!is_numeric($catId)) {
            return;
        }

        try {
            $catId = (int)$catId;
            $categoryTable = QUI\ERP\Products\Utils\Tables::getCategoryTableName();
            $translationTable = QUI\Translator::table();

            $title = '';
            $desc = '';

            // title
            $titleResult = QUI::getDataBase()->fetch([
                'from' => $translationTable,
                'where' => [
                    'groups' => 'quiqqer/products',
                    'var' => 'products.category.' . $catId . '.title'
                ],
                'limit' => 1
            ]);

            if (isset($titleResult[0])) {
                $title = json_encode($titleResult[0]);
            }

            // desc
            $descResult = QUI::getDataBase()->fetch([
                'from' => $translationTable,
                'where' => [
                    'groups' => 'quiqqer/products',
                    'var' => 'products.category.' . $catId . '.description'
                ],
                'limit' => 1
            ]);

            if (isset($descResult[0])) {
                $desc = json_encode($descResult[0]);
            }

            QUI::getDataBase()->update($categoryTable, [
                'title_cache' => $title,
                'description_cache' => $desc
            ], [
                'id' => $catId
            ]);
        } catch (QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError($Exception->getMessage());
        }
    }

    /**
     * event: on quiqqer translator edit by id
     *
     * @param $id
     * @param $data
     */
    public static function onQuiqqerTranslatorEditById($id, $data): void
    {
        $group = $data['groups'];
        $var = $data['var'];
        $package = $data['package'];

        self::onQuiqqerTranslatorEdit($group, $var, $package, $data);
    }

    /**
     * Update category title & description locale
     *
     * @deprecated replaced by onQuiqqerTranslatorEditById & onQuiqqerTranslatorEdit
     */
    public static function onQuiqqerTranslatorPublish()
    {
    }

    public static function onQuiqqerMigrationV2(MigrationV2 $Console): void
    {
        $Console->writeLn('- Migrate products');
        $table = QUI::getDBTableName('products');

        QUI\Utils\MigrationV1ToV2::migrateUsers($table, [
            'c_user',
            'e_user'
        ]);
    }
}
