<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class TimePeriod
 *
 * Define an arbitrary time period.
 */
class TimePeriod extends QUI\ERP\Products\Field\Field
{
    const PERIOD_SECOND = 'second';
    const PERIOD_MINUTE = 'minute';
    const PERIOD_HOUR   = 'hour';
    const PERIOD_DAY    = 'day';
    const PERIOD_WEEK   = 'week';
    const PERIOD_MONTH  = 'month';
    const PERIOD_YEAR   = 'year';

    /**
     * @var bool
     */
    protected $searchable = false;

    /**
     * @var null
     */
    protected $defaultValue = null;

    /**
     * @return string
     */
    public function getValue()
    {
        if (!\is_null($this->value)) {
            return $this->value;
        }

        return $this->defaultValue;
    }

    /**
     * Return the FrontendView
     *
     * @return UnitSelectFrontendView
     */
    public function getFrontendView()
    {
        return new UnitSelectFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/TimePeriod';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param array
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $invalidException = [
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId'    => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType'  => $this->getType()
            ]
        ];

        if (!\is_string($value) && !\is_array($value)) {
            throw new QUI\ERP\Products\Field\Exception($invalidException);
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new QUI\ERP\Products\Field\Exception($invalidException);
            }
        }

        $needles = [
            'from',
            'to',
            'unit'
        ];

        foreach ($needles as $needle) {
            if (!\array_key_exists($needle, $value)) {
                throw new QUI\ERP\Products\Field\Exception($invalidException);
            }
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param string|array $value
     * @return array|null
     */
    public function cleanup($value)
    {
        if (empty($value)) {
            return $this->defaultValue;
        }

        if (!\is_string($value) && !\is_array($value)) {
            return $this->defaultValue;
        }

        if (\is_string($value)) {
            $value = \json_decode($value, true);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                return $this->defaultValue;
            }
        }

        switch ($value['unit']) {
            case self::PERIOD_SECOND:
            case self::PERIOD_MINUTE:
            case self::PERIOD_HOUR:
            case self::PERIOD_DAY:
            case self::PERIOD_WEEK:
            case self::PERIOD_MONTH:
            case self::PERIOD_YEAR:
                break;

            default:
                return $this->defaultValue;
        }

        $value['from'] = (int)$value['from'];
        $value['to']   = (int)$value['to'];

        return $value;
    }
}
