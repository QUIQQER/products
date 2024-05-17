<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\Attributes
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function json_decode;

/**
 * Class Attributes
 * - Attribute Liste
 *
 * @package QUI\ERP\Products\Field
 *
 * @todo eindeutige ID für option
 */
class AttributeGroup extends QUI\ERP\Products\Field\Field
{
    const ENTRIES_TYPE_DEFAULT = 1;
    const ENTRIES_TYPE_SIZE = 2;
    const ENTRIES_TYPE_COLOR = 3;
    const ENTRIES_TYPE_MATERIAL = 4;

    /**
     * @var int|bool
     */
    protected int|bool $searchDataType = Search::SEARCHDATATYPE_TEXT;

    protected mixed $defaultValue = null;

    /**
     * @var array
     */
    protected array $disabled = [];

    /**
     * Attribute group constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct(int $fieldId, array $params)
    {
        $this->setOptions([
            'entries' => [],
            'priority' => 0,
            'generate_tags' => false,
            'entries_type' => self::ENTRIES_TYPE_DEFAULT,
            'is_image_attribute' => false
        ]);

        parent::__construct($fieldId, $params);

        // set default, if one are set
        $options = $this->getOptions();

        foreach ($options['entries'] as $option) {
            if (isset($option['selected']) && $option['selected']) {
                $this->value = $option['valueId'];
                $this->defaultValue = $option['valueId'];
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
                foreach ($value as $val) {
                    if (isset($val['selected']) && $val['selected']) {
                        $this->value = $val['valueId'];
                        $this->defaultValue = $val['valueId'];
                    }
                }
            }
        }
    }

    /**
     * Add an product attribute entry
     *
     * @param array $entry - data entry
     *
     * @example $this->addEntry(array(
     *       'title' => '',    // translation json string {de: "", en: ""}
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

        $data = [];

        $available = [
            'title',
            'valueId',
            'selected', // optional
            'disabled', // optional
            'image'     // optional
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
     * disable all entries
     */
    public function disableEntries(): void
    {
        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['disabled'] = true;
        }
    }

    /**
     * hide all entries
     */
    public function hideEntries(): void
    {
        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['hide'] = true;
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

    /**
     * Enable an option
     *
     * @param integer|string $entry
     */
    public function showEntry(int|string $entry): void
    {
        $this->options['entries'][$entry]['hide'] = false;
    }

    /**
     * @param QUI\Locale|null $Locale
     *
     * @return string
     */
    public function getValueTitle(?QUI\Locale $Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $lang = $Locale->getCurrent();
        $targetValueId = $this->getValue();

        foreach ($this->options['entries'] as $entry) {
            if ($entry['valueId'] == $targetValueId && !empty($entry['title'][$lang])) {
                return $entry['title'][$lang];
            }
        }

        return '';
    }

    public function getValue(): mixed
    {
        if ($this->value !== null) {
            return $this->value;
        }

        return $this->defaultValue;
    }

    /**
     * clears the current value of the field
     */
    public function clearValue(): void
    {
        parent::clearValue();

        foreach ($this->options['entries'] as $key => $option) {
            $this->options['entries'][$key]['selected'] = false;
        }
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->getValue() === null;
    }

    /**
     * Return the FrontendView
     *
     * @return AttributeGroupFrontendView
     */
    public function getFrontendView(): View
    {
        $View = new AttributeGroupFrontendView(
            $this->getFieldDataForView()
        );

        $View->setProduct($this->Product);

        return $View;
    }

    /**
     * @return View
     */
    public function getValueView(): View
    {
        if ($this->getAttribute('viewType') === 'backend') {
            return $this->getBackendView();
        }


        $View = new AttributeGroupFrontendValueView(
            $this->getFieldDataForView()
        );

        $View->setProduct($this->Product);

        return $View;
    }

    /**
     * @return string
     */
    public function getJavaScriptControl(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/AttributeGroup';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/AttributeGroupSettings';
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
        if (empty($value)) {
            return;
        }

        $options = $this->getOptions();
        $entries = $options['entries'];

        foreach ($entries as $entry) {
            if (
                $entry['valueId'] == $value
                || is_numeric($value) && $entry['valueId'] == (int)$value
            ) {
                return;
            }
        }

        throw new Exception([
            'quiqqer/products',
            'exception.field.invalid',
            [
                'fieldId' => $this->getId(),
                'fieldTitle' => $this->getTitle(),
                'fieldType' => $this->getType()
            ]
        ]);
    }

    /**
     * Cleanup the value, so the value is valid
     */
    public function cleanup(mixed $value): mixed
    {
        $check = [];

        if (is_string($value)) {
            $check = json_decode($value, true);

            // if no json, check if value exist
            if ($check === null && !is_numeric($value)) {
                $options = $this->getOptions();
                $entries = $options['entries'];

                foreach ($entries as $entry) {
                    if ($entry['valueId'] == $value) {
                        return $value;
                    }
                }
            }

            if (is_numeric($value)) {
                $options = $this->getOptions();
                $entries = $options['entries'];

                // first check if a value id exists with this value
                foreach ($entries as $entry) {
                    if ($entry['valueId'] == $value) {
                        return $value;
                    }
                }

                // use the key
                if (isset($entries[$value])) {
                    return $entries[$value]['valueId'];
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
     * Get all available search types
     */
    public function getSearchTypes(): array
    {
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_CHECKBOX_LIST
        ];
    }

    /**
     * Get default search type
     */
    public function getDefaultSearchType(): ?string
    {
        return Search::SEARCHTYPE_SELECTMULTI;
    }
}
