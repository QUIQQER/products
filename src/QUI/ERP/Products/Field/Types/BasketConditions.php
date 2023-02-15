<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\BasketConditions
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Handler\Search;

/**
 * Class Input
 * @package QUI\ERP\Products\Field
 */
class BasketConditions extends QUI\ERP\Products\Field\Field
{
    const TYPE_1 = 1; // Kann ohne Einschränkung in den Warenkorb
    const TYPE_2 = 2; // Kann nur alleine und nur einmalig in den Warenkorb
    const TYPE_3 = 3; // Kann mit anderen Produkten einmalig in den Warenkorb
    const TYPE_4 = 4; // Kann mit anderen Produkten diesen Typs nicht in den Warenkorb
    const TYPE_5 = 5; // Kann mit anderen Produkten diesen Typs einmalig in den Warenkorb
    const TYPE_6 = 6; // Kann nur alleine und mehrmalig in den Warenkorb

    protected $columnType = 'TINYINT(1)';
    protected $searchDataType = Search::SEARCHDATATYPE_NUMERIC;

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        $value = (int)$value;

        if (empty($value)) {
            return;
        }

        switch ($value) {
            case self::TYPE_1:
            case self::TYPE_2:
            case self::TYPE_3:
            case self::TYPE_4:
            case self::TYPE_5:
            case self::TYPE_6:
                return;
        }

        throw new QUI\ERP\Products\Field\Exception([
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId'    => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType'  => $this->getType()
            ]
        ]);
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return int
     */
    public function cleanup($value)
    {
        return (int)$value;
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/BasketConditions';
    }
}
