<?php

/**
 * This file contains QUI\ERP\Products\Field\UniqueField
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\Locale;

use function class_exists;
use function class_implements;
use function defined;
use function get_class;
use function in_array;
use function is_numeric;
use function is_string;
use function property_exists;
use function reset;

/**
 * Class UniqueField
 * This field is a field for the view, unique fields are not editable
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID )->createUniqueField();
 */
class UniqueField implements QUI\ERP\Products\Interfaces\UniqueFieldInterface
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected int $id;

    /**
     * Field name
     *
     * @var string
     */
    protected string $name;

    /**
     * Field title
     * @var string|array
     */
    protected string|array $title;

    /**
     * @var string
     */
    protected string $prefix = '';

    /**
     * @var string
     */
    protected string $suffix = '';

    /**
     * @var integer
     */
    protected int $priority = 0;

    /**
     * Field value
     *
     * @var mixed
     */
    protected mixed $value = '';

    /**
     * @var string
     */
    protected string $type = '';

    /**
     * is custom field?
     * Custom field is a field, which can be filled by the visitors
     *
     * @var boolean
     */
    protected bool $custom = false;

    /**
     * custom field calculation data
     * @var array
     */
    protected array $custom_calc = [];

    /**
     * search cache value
     *
     * @var string|array|null
     */
    protected string|array|null $searchvalue;

    /**
     * is field public
     * field which is visible by the visitors, too
     *
     * @var boolean
     */
    protected bool $isPublic = false;

    /**
     * Field from the system, like price
     * @var bool
     */
    protected bool $isSystem = false;

    /**
     * @var boolean
     */
    protected bool $isStandard = false;

    /**
     * @var boolean
     */
    protected bool $isRequired = false;

    /**
     * @var bool
     */
    protected bool $showInDetails = false;

    /**
     * a field in the product, but not in any category from the product
     *
     * @var boolean
     */
    protected bool $unassigned = false;

    /**
     * Is the field a product own field
     * @var boolean
     */
    protected bool $ownField = false;

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Is the field in the frontend currently changeable
     *
     * @var bool
     */
    protected bool $changeable = true;

    /**
     * The parent field class
     *
     * @var string
     */
    protected string $parentFieldClass = '';

    /**
     * Current instance of a product
     * optional and only needed at the runtime instance
     * this is the product from which the field are
     *
     * @var null|QUI\ERP\Products\Interfaces\ProductInterface
     */
    protected ?QUI\ERP\Products\Interfaces\ProductInterface $Product = null;

    /**
     * User input from custom input fields
     *
     * @var string
     */
    protected string $userInput = '';

    /**
     * Model constructor.
     *
     * @param integer $fieldId
     * @param array $params - optional, field params (system, require, standard)
     */
    public function __construct(int $fieldId, array $params = [])
    {
        $this->id = $fieldId;

        $attributes = [
            'title',
            'type',
            'prefix',
            'suffix',
            'priority',
            'options',
            'isRequired',
            'isStandard',
            'isSystem',
            'isPublic',
            'custom',
            'custom_calc',
            'unassigned',
            'value',
            'ownField',
            'showInDetails',
            'searchvalue',
            'changeable',
            'userInput'
        ];

        if (!isset($params['isPublic'])) {
            $this->isPublic = true;
        }

        if (!isset($params['showInDetails'])) {
            $this->showInDetails = true;
        }

        foreach ($attributes as $attribute) {
            if (!isset($params[$attribute])) {
                continue;
            }

            if (property_exists($this, $attribute)) {
                $this->$attribute = $params[$attribute];
            }
        }

        if (isset($params['__class__']) && class_exists($params['__class__'])) {
            $this->parentFieldClass = $params['__class__'];
        }

        if (empty($this->parentFieldClass) && isset($params['type'])) {
            $class = 'QUI\ERP\Products\Field\Types\\' . $params['type'];

            if (class_exists($class)) {
                $this->parentFieldClass = $class;
            }
        }
    }

    /**
     * Return the view
     *
     * @return View
     */
    public function getView(): View
    {
        if (defined('QUIQQER_BACKEND')) {
            $View = $this->getBackendView();
        } else {
            $View = $this->getFrontendView();
        }

        if ($this->Product) {
            $View->setProduct($this->Product);
        }

        return $View;
    }

    /**
     * Return the Frontend View
     *
     * @return View
     */
    public function getFrontendView(): View
    {
        $type = $this->getType();
        $viewClass = 'QUI\ERP\Products\Field\Types\\' . $type . 'FrontendView';

        if (class_exists($viewClass)) {
            return new $viewClass($this->getAttributes());
        }

        if ($this->parentFieldClass && class_exists($this->parentFieldClass)) {
            $Field = new $this->parentFieldClass($this->getId(), $this->getAttributes());

            if ($Field instanceof Field) {
                return $Field->getFrontendView();
            }
        }

        return new View($this->getAttributes());
    }

    /**
     * Return the Backend View
     *
     * @return View
     */
    public function getBackendView(): View
    {
        $type = $this->getType();
        $viewClass = 'QUI\ERP\Products\Field\Types\\' . $type . 'BackendView';

        if (class_exists($viewClass)) {
            return new $viewClass($this->getAttributes());
        }

        if ($this->parentFieldClass && class_exists($this->parentFieldClass)) {
            $Field = new $this->parentFieldClass($this->getId(), $this->getAttributes());

            if ($Field instanceof Field) {
                return $Field->getBackendView();
            }
        }

        return new View($this->getAttributes());
    }

    /**
     * Return Field-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Change the price of the product
     * Returns the price object
     *
     * @return QUI\ERP\Money\Price
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();

        if (is_numeric($this->value)) {
            $Price = new QUI\ERP\Money\Price($this->value, $Currency);
        } else {
            $Price = new QUI\ERP\Money\Price(0, $Currency);
        }

        return $Price;
    }

    /**
     * Return the type of the parent field
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Is the field unassigned?
     *
     * @return bool
     */
    public function isUnassigned(): bool
    {
        return $this->unassigned;
    }

    /**
     * @return bool
     */
    public function isCustomField(): bool
    {
        return $this->custom;
    }

    /**
     * Is the field currently changeable
     * This flag is needed for the frontend view
     *
     * @return bool
     */
    public function isChangeable(): bool
    {
        return $this->changeable;
    }

    /**
     * Return the field name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|array
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Exists an empty entry in this list?
     *
     * @return bool
     */
    public function hasDefaultEntry(): bool
    {
        $options = $this->getOptions();
        $entries = $options['entries'];

        foreach ($entries as $entry) {
            if ($entry['selected']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the parent class of the unique field
     * - this is the class name from the original field
     *
     * @return string
     */
    public function getParentClass(): string
    {
        return $this->parentFieldClass;
    }

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param Locale|null $Locale $Locale
     * @return array|string
     */
    public function getValueByLocale(?Locale $Locale = null): mixed
    {
        return $this->getValue();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param Locale|null $Locale
     * @return string|array|null
     */
    public function getSearchCacheValue(QUI\Locale $Locale = null): null|string|array
    {
        return $this->searchvalue;
    }

    /**
     * Return the title of the field
     * The title are from the user and translated
     *
     * @param Locale|null $Locale - optional
     * @return string
     */
    public function getTitle(QUI\Locale $Locale = null): string
    {
        if (!$Locale) {
            return QUI::getLocale()->get(
                'quiqqer/products',
                'products.field.' . $this->getId() . '.title'
            );
        }

        return $Locale->get(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $options = $this->getOptions();
        $value = $this->getValue();

        /*
         * Auskommentiert, weil das ein sehr umständlicher Weg war, um dan die Benutzereingabe
         * Von ProductAttributeList-Feldern zu kommen; dafür gibt es jetzt eine einfachere API.
         */
//        $json      = null;
//        if (\is_string($value)) {
//            $json = \json_decode($value, true);
//
//            if (\is_array($json) && isset($json[0])) {
//                $value = $json[0];
//
//                if (isset($json[1])) {
//                    $userinput = $json[1];
//                }
//            }
//        }

        $parentClass = $this->getParentClass();
        $interfaces = [];

        try {
            if (empty($parentClass)) {
                $Field = QUI\ERP\Products\Handler\Fields::getField($this->getId());
                $interfaces = class_implements(get_class($Field));
            } else {
                $interfaces = class_implements($this->getParentClass());
            }
        } catch (\QUI\Exception) {
        }

        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'type' => $this->getType(),
            'options' => $options,
            'isRequired' => $this->isRequired(),
            'isStandard' => $this->isStandard(),
            'isSystem' => $this->isSystem(),
            'isPublic' => $this->isPublic(),

            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'priority' => $this->priority,
            'custom' => $this->isCustomField(),
            'isUserInputField' => in_array(CustomInputFieldInterface::class, $interfaces),
            'custom_calc' => $this->custom_calc,
            'unassigned' => $this->isUnassigned(),
            'value' => $value,
            'valueText' => $this->getValueText(),
            'userInput' => $this->userInput,
            'showInDetails' => $this->showInDetails()
        ];
    }

    /**
     * Get field value text
     *
     * @return string
     */
    protected function getValueText(): string
    {
        if (isset($this->custom_calc['valueText'])) {
            return $this->custom_calc['valueText'];
        }

        $valueText = '-';

        switch ($this->type) {
            case QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_LIST:
                $valueText = $this->getValueTextProductAttributeList();
                break;

            case QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_GROUPS:
                $valueText = $this->getValueTextAttributeGroup();
                break;

            default:
                if ($this->isCustomField()) {
                    if (!empty($this->userInput)) {
                        $valueText = $this->userInput;
                    } else {
                        $value = $this->getValue();

                        if (!empty($value) && is_string($value)) {
                            $valueText = $value;
                        }
                    }
                }
        }

        return $valueText;
    }

    /**
     * Parse value text of field type ProductAttributeList
     *
     * @return string
     */
    protected function getValueTextProductAttributeList(): string
    {
        $options = $this->getOptions();
        $value = $this->getValue();
        $valueText = '-';

        if (!empty($options) && isset($options['entries'])) {
            $current = QUI::getLocale()->getCurrent();

            foreach ($options['entries'] as $option) {
                if (!isset($option['selected']) || $option['selected'] === false) {
                    continue;
                }

                if (isset($option['title'][$current])) {
                    $valueText = $option['title'][$current];
                    break;
                }

                $valueText = reset($option['title']);
            }

            if ($valueText === '-') {
                foreach ($options['entries'] as $option) {
                    if (!isset($option['default']) || $option['default'] === false) {
                        continue;
                    }

                    if (isset($option['title'][$current])) {
                        $valueText = $option['title'][$current];
                        break;
                    }

                    $valueText = reset($option['title']);
                }
            }

            if ($valueText === '-') {
                foreach ($options['entries'] as $option) {
                    if (!isset($option['valueId'])) {
                        continue;
                    }

                    $numCheck = is_numeric($value)
                        && is_numeric($option['valueId'])
                        && (int)$option['valueId'] === (int)$value;

                    if ($option['valueId'] !== $value && !$numCheck) {
                        continue;
                    }

                    if (isset($option['title'][$current])) {
                        $valueText = $option['title'][$current];
                        break;
                    }

                    $valueText = reset($option['title']);
                }
            }
        }

        return $valueText;
    }

    /**
     * Parse value text of field type AttributeGroup
     *
     * @return string
     */
    protected function getValueTextAttributeGroup(): string
    {
        $options = $this->getOptions();
        $value = $this->getValue();
        $valueText = '-';

        if (empty($options['entries'])) {
            return $valueText;
        }

        $current = QUI::getLocale()->getCurrent();

        foreach ($options['entries'] as $option) {
            if ($value != $option['valueId']) {
                continue;
            }

            if (!empty($option['title'][$current])) {
                return $option['title'][$current];
            }

            // fallback to default title (first language)
            return reset($option['title']);
        }

        // Get default value
        foreach ($options['entries'] as $option) {
            if (empty($option['selected'])) {
                continue;
            }

            if (!empty($option['title'][$current])) {
                return $option['title'][$current];
            }

            // fallback to default title (first language)
            return reset($option['title']);
        }

        return $valueText;
    }

    /**
     * Is the field currently changeable
     * This flag is needed for the frontend view
     *
     * @param bool $status
     */
    public function setChangeableStatus(bool $status): void
    {
        $this->changeable = $status;
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    /**
     * @return boolean
     */
    public function isStandard(): bool
    {
        return $this->isStandard;
    }

    /**
     * @return boolean
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * @return boolean
     */
    public function isOwnField(): bool
    {
        return $this->ownField;
    }

    /**
     * @return boolean
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * Show the fields in the details?
     *
     * @return boolean
     */
    public function showInDetails(): bool
    {
        return $this->showInDetails;
    }

    /**
     * @param $Product - Product instance
     */
    public function setProduct($Product): void
    {
        $this->Product = $Product;
    }
}
