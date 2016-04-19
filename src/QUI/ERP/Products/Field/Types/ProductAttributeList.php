<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeList
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class ProductAttributeList
 *
 * Beschreibung:
 * Dies ist die Auswahlliste
 *
 * Auswahlliste ist ein Feld welches dem Besucher verschiedenen Auswahleigenschaften zur Verfügung stellt.
 * Eine Auswahl kann den Preis des Produktes verändern
 *
 * Beispiel:
 * Oberfläche
 * -> Messing poliert lackiert (MP lackiert)
 * -> Messing poliert ohne Lack (MP ohne Lack)
 * -> Messing matt mit Lack (MM mit Lack)(nach Kundenspezifikation¹) +10%
 *
 * @package QUI\ERP\Products\Field\Types
 */
class ProductAttributeList extends QUI\ERP\Products\Field\CustomField
{
    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * ProductAttributeList constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setAttributes(array(
            'options' => array(),
        ));

        parent::__construct($fieldId, $params);
    }

    /**
     * Return the FrontendView
     *
     * @return ProductAttributeListFrontendView
     */
    public function getFrontendView()
    {
        return new ProductAttributeListFrontendView(array(
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority'),
            'options' => $this->getOptions()
        ));
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeList';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
//        if (!is_numeric($value)) {
//            throw new QUI\Exception(array(
//                'quiqqer/products',
//                'exception.field.invalid',
//                array(
//                    'fieldId' => $this->getId(),
//                    'fieldTitle' => $this->getTitle(),
//                    'fieldType' => $this->getType()
//                )
//            ));
//        }
//
//        $value   = (int)$value;
//        $options = $this->getOptions();
//
//        if (!isset($options[$value])) {
//            throw new QUI\Exception(array(
//                'quiqqer/products',
//                'exception.field.invalid',
//                array(
//                    'fieldId' => $this->getId(),
//                    'fieldTitle' => $this->getTitle(),
//                    'fieldType' => $this->getType()
//                )
//            ));
//        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @throws \QUI\Exception
     * @return array
     */
    public function cleanup($value)
    {

    }
}
