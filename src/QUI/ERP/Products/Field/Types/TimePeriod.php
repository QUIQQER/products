<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

/**
 * Class TimePeriod
 *
 * Define an arbitrary time period.
 */
class TimePeriod extends QUI\ERP\Products\Field\Field
{
    const PERIOD_SECOND = 'second';
    const PERIOD_MINUTE = 'minute';
    const PERIOD_HOUR = 'hour';
    const PERIOD_DAY = 'day';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';

    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @var null
     */
    protected mixed $defaultValue = null;

    /**
     * Return the FrontendView
     *
     * @return UnitSelectFrontendView
     */
    public function getFrontendView(): QUI\ERP\Products\Field\View
    {
        return new UnitSelectFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/TimePeriod';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed< $value
     * @throws Exception
     */
    public function validate(mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        $invalidException = [
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId' => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType' => $this->getType()
            ]
        ];

        if (!is_string($value) && !is_array($value)) {
            throw new Exception($invalidException);
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception($invalidException);
            }
        }

        $needles = [
            'from',
            'to',
            'unit'
        ];

        foreach ($needles as $needle) {
            if (!array_key_exists($needle, $value)) {
                throw new Exception($invalidException);
            }
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return array|null
     */
    public function cleanup(mixed $value): mixed
    {
        if (empty($value)) {
            return $this->defaultValue;
        }

        if (!is_string($value) && !is_array($value)) {
            return $this->defaultValue;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
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
        $value['to'] = (int)$value['to'];

        return $value;
    }
}
