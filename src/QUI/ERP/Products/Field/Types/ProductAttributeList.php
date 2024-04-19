<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\ProductAttributeList
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Accounting\Calc as ErpCalc;

use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;

use function get_class;
use function htmlspecialchars;
use function is_array;
use function is_int;
use function is_null;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function mb_strtoupper;

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
class ProductAttributeList extends QUI\ERP\Products\Field\CustomCalcField
{
    /**
     * @var bool
     */
    protected bool $searchable = false;

    /**
     * @var null
     */
    protected mixed $defaultValue = null;

    /**
     * @var array
     */
    protected array $disabled = [];

    /**
     * ProductAttributeList constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct(int $fieldId, array $params)
    {
        $this->setOptions([
            'entries' => [],
            'priority' => 0,
            'calculation_basis' => '',
            'display_discounts' => true,
            'generate_tags' => false
        ]);

        parent::__construct($fieldId, $params);

        // set default, if one are set
        $options = $this->getOptions();

        foreach ($options['entries'] as $key => $option) {
            if (isset($option['selected']) && $option['selected']) {
                $this->value = $key;
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
    public function setOption(string $option, mixed $value): void
    {
        parent::setOption($option, $value);

        if ($option == 'entries') {
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    if (isset($val['selected']) && $val['selected']) {
                        $this->value = $key;
                        $this->defaultValue = $key;
                    }
                }
            }
        }
    }

    /**
     * Add a product attribute entry
     *
     * @param array $entry - data entry
     *
     * @example $this->addEntry(array(
     *       'title' => '',    // translation json string {de: "", en: ""}
     *       'sum'   => '',      // -> 10, 100 -> numbers
     *       'type'  => '',     // optional -> QUI\ERP\Products\Utils\Calc::CALCULATION_PERCENTAGE |
     *                                        QUI\ERP\Products\Utils\Calc::CALCULATION_COMPLEMENT
     *       'selected' => '', // optional
     *       'userinput => ''' // optional
     * ));
     */
    public function addEntry(array $entry = []): void
    {
        if (empty($entry)) {
            return;
        }

        if (!isset($entry['title'])) {
            return;
        }

        if (!isset($entry['sum'])) {
            return;
        }

        $data = [];
        $available = [
            'title',
            'sum',
            'type',     // optional
            'selected', // optional
            'userinput' // optional
        ];

        foreach ($available as $k) {
            if (isset($entry[$k])) {
                $data[$k] = $entry[$k];
            }
        }

        $entries = $this->options['entries'];
        $entries[] = $data;

        $this->options['entries'] = $entries;
    }

    /**
     * Return the custom value entry from the user
     *
     * @return string|false
     */
    public function getUserInput(): bool|string
    {
        if (!is_null($this->value)) {
            $value = json_decode($this->value, true);

            if (isset($value[1])) {
                return $value[1];
            }
        }

        return false;
    }

    /**
     * Return the FrontendView
     *
     * @return ProductAttributeListFrontendView
     */
    public function getFrontendView(): View
    {
        return new ProductAttributeListFrontendView(
            $this->getFieldDataForView()
        );
    }

    /**
     * Return the view for the backend
     *
     * @return View
     */
    public function getBackendView(): View
    {
        return new ProductAttributeListBackendView($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeList';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/ProductAttributeListSettings';
    }

    /**
     * Return the array for the calculation
     *
     * @param null|QUI\Locale $Locale
     * @return array
     */
    public function getCalculationData($Locale = null): array
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $options = $this->getOptions();

        if (!isset($options['priority'])) {
            $options['priority'] = 0;
        }

        if (!isset($options['calculation_basis'])) {
            $options['calculation_basis'] = '';
        }

        if (!isset($options['entries'])) {
            $options['entries'] = [];
        }

        $entries = $options['entries'];
        $value = $this->getValue();
        $valueText = '';
        $sum = 0;
        $userInput = '';
        $calcType = ErpCalc::CALCULATION_COMPLEMENT;

        if ($value && str_contains($value, '[') && str_contains($value, ']')) {
            $data = json_decode($value, true);

            if (is_array($data)) {
                if (isset($data[1])) {
                    $userInput = htmlspecialchars($data[1]);
                }

                $value = $data[0];
            }
        }

        // @todo show amount
        if (isset($entries[$value])) {
            $sum = $entries[$value]['sum'];
            $type = $entries[$value]['type'];
            $valueText = $entries[$value]['title'];

            if (get_class($Locale) == QUI\Locale::class) {
                $current = $Locale->getCurrent();
                $currentCode = mb_strtolower($current) . '_' . mb_strtoupper($current);

                if (isset($valueText[$current])) {
                    $valueText = $valueText[$current];
                } elseif (isset($valueText[$currentCode])) {
                    $valueText = $valueText[$currentCode];
                }
            }

            if ($type == ErpCalc::CALCULATION_PERCENTAGE) {
                $calcType = ErpCalc::CALCULATION_PERCENTAGE;
            }
        }

        if ($value === '') {
            $valueText = '';
        }

        if ($userInput) {
            // locale values
            if (is_array($valueText)) {
                $current = QUI::getLocale()->getCurrent();

                if (isset($valueText[$current])) { // check if lang values
                    foreach ($valueText as $lang => $val) {
                        $valueText[$lang] .= ' - ' . $userInput;
                    }
                }
            } else {
                $valueText .= ' - ' . $userInput;
            }
        }

        return [
            'priority' => (int)$options['priority'],
            'basis' => $options['calculation_basis'],
            'value' => $sum,
            'calculation' => $calcType,
            'valueText' => $valueText,
            'displayDiscounts' => $options['display_discounts']
        ];
    }

    /**
     * @return string
     */
    public function getValue(): mixed
    {
        if (!is_null($this->value)) {
            return $this->value;
        }

        return $this->defaultValue;
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param integer|string $value - User value = "[key, user value]"
     * @throws Exception
     */
    public function validate($value): void
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }

        if (empty($value) && $value != '0') {
            if (QUI::isFrontend() && $this->isRequired()) {
                throw new QUI\ERP\Products\Field\ExceptionRequired([
                    'quiqqer/products',
                    'exception.field.is.invalid',
                    [
                        'fieldId' => $this->getId(),
                        'fieldtitle' => $this->getTitle(),
                        'fieldType' => $this->getType()
                    ]
                ]);
            }

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

        if (!is_numeric($value)) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $value = json_decode($value, true);

            if (!isset($value[0]) || !isset($value[1])) {
                throw new Exception($invalidException);
            }

            //$customValue = $value[1];
            $value = $value[0];
        }

        if (!is_numeric($value)) {
            throw new Exception($invalidException);
        }

        $value = (int)$value;
        $options = $this->getOptions();

        if (!isset($options['entries'])) {
            throw new Exception($invalidException);
        }

        $entries = $options['entries'];

        if (!isset($entries[$value])) {
            throw new Exception($invalidException);
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return string|array|int|null
     */
    public function cleanup(mixed $value): string|array|int|null
    {
        if ($value === '') {
            return null;
        }

        $check = [];

        if (is_string($value) && !is_numeric($value)) {
            $check = json_decode($value, true);

            // if no json, check if value exist
            if ($check === null) {
                $options = $this->getOptions();
                $entries = $options['entries'];
                $wanted = (int)$value;

                if (isset($entries[$wanted])) {
                    return $wanted;
                }
            }

            if (!isset($check[0]) || !isset($check[1])) {
                return null;
            }

            if (!is_numeric($check[0])) {
                return null;
            }

            return $value;
        }

        if (is_array($value)) {
            if (!isset($check[0]) || !isset($check[1])) {
                return null;
            }

            if (!is_numeric($check[0])) {
                return null;
            }

            return $value;
        }


        if (empty($value) && !is_int($value) && $value != 0) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }

    /**
     * disable all entries
     */
    public function disableEntries(): void
    {
        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['disabled'] = true;
        }
    }

    /**
     * Disable an option
     *
     * @param integer|string $entry
     */
    public function disableEntry(int|string $entry): void
    {
        $this->options['entries'][$entry]['disabled'] = true;
    }

    /**
     * Enable an option
     *
     * @param integer|string $entry
     */
    public function enableEntry(int|string $entry): void
    {
        $this->options['entries'][$entry]['disabled'] = false;
    }
}
