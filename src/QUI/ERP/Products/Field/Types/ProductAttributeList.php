<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeList
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Utils\Calc;

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
     * @var null
     */
    protected $defaultValue = null;

    /**
     * ProductAttributeList constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setOptions(array(
            'entries' => array(),
            'priority' => 0,
            'calculation_basis' => '',
            'display_discounts' => true,
            'generate_tags' => false
        ));

        parent::__construct($fieldId, $params);

        // set default, if one are set
        $options = $this->getOptions();

        foreach ($options['entries'] as $key => $option) {
            if (isset($option['selected']) && $option['selected']) {
                $this->value        = $key;
                $this->defaultValue = $key;
            }
        }
    }

    /**
     * Set a field option
     *
     * @param string $option - option name
     * @param mixed $value - option value
     */
    public function setOption($option, $value)
    {
        parent::setOption($option, $value);

        if ($option == 'entries') {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    if (isset($val['selected']) && $val['selected']) {
                        $this->value        = $key;
                        $this->defaultValue = $key;
                    }
                }
            }
        }
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        if (!is_null($this->value)) {
            return $this->value;
        }

        return $this->defaultValue;
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
     * Return the array for the calculation
     *
     * @return array
     */
    public function getCalculationData()
    {
        $options = $this->getOptions();

        if (!isset($options['priority'])) {
            $options['priority'] = 0;
        }

        if (!isset($options['calculation_basis'])) {
            $options['calculation_basis'] = '';
        }

        if (!isset($options['entries'])) {
            $options['entries'] = array();
        }

        $entries  = $options['entries'];
        $value    = $this->getValue();
        $sum      = 0;
        $calcType = Calc::CALCULATION_COMPLEMENT;

        if (isset($entries[$value])) {
            $sum  = $entries[$value]['sum'];
            $type = $entries[$value]['type'];

            switch ($type) {
                case Calc::CALCULATION_PERCENTAGE:
                    $calcType = Calc::CALCULATION_PERCENTAGE;
                    break;
            }
        }

        return array(
            'priority' => (int)$options['priority'],
            'basis' => $options['calculation_basis'],
            'value' => $sum,
            'calculation' => $calcType
        );
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param integer $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (!is_numeric($value)) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
        }

        $value   = (int)$value;
        $options = $this->getOptions();

        if (!isset($options['entries'])) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
        }

        $entries = $options['entries'];

        if (!isset($entries[$value])) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'fieldType' => $this->getType()
                )
            ));
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param integer $value
     * @throws \QUI\Exception
     * @return int|null
     */
    public function cleanup($value)
    {
        if (empty($value) && !is_int($value) && $value != 0) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }
}
