<?php

/**
 * This file contains QUI\ERP\Products\Field\Field
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Search;
use QUI\Exception;
use QUI\Interfaces\Users\User;
use QUI\Locale;

use function array_filter;
use function floor;
use function get_class;
use function is_array;
use function is_bool;
use function is_int;
use function is_null;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function method_exists;
use function reset;
use function round;
use function str_replace;
use function trim;

/**
 * Class Field
 *
 * @package QUI\ERP\Products\Field
 *
 * @example
 * QUI\ERP\Products\Handler\Field::getField( ID );
 */
abstract class Field extends QUI\QDOM implements QUI\ERP\Products\Interfaces\FieldInterface
{
    /**
     * Field-ID
     *
     * @var integer
     */
    protected int $id;

    /**
     * @var bool
     */
    protected bool $system = false;

    /**
     * @var bool
     */
    protected bool $standard = false;

    /**
     * @var bool
     */
    protected bool $require = false;

    /**
     * unassigned = feld ist dem produkt nicht zugewiesen aber die daten soll das produkt trotzdem behalten
     * unassigned ist also ein nicht zugewiesenes feld welches das produkt als daten trotzdem hat
     *
     * @var bool
     */
    protected bool $unassigned = false;

    /**
     * @var bool
     */
    protected bool $ownField = false;

    /**
     * @var bool
     */
    protected bool $public = true;

    /**
     * @var null
     */
    protected mixed $defaultValue = null;

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Is this Field searchable?
     *
     * @var bool
     */
    protected bool $searchable = true;

    /**
     * Should the field be displayed in the details?
     * @var bool
     */
    protected bool $showInDetails = false;

    /**
     * @var ?string
     */
    protected ?string $type = null;

    /**
     * Field-Name
     *
     * @var string
     */
    protected string $name;

    /**
     * Field value
     *
     * @var mixed
     */
    protected mixed $value = null;

    /**
     * Column type for database table (cache column)
     *
     * @var string
     */
    protected string $columnType = 'LONGTEXT';

    /**
     * @var array
     */
    protected array $searchTypes = [];

    /**
     * Search data type for values of this field
     *
     * @var int|bool
     */
    protected int|bool $searchDataType = false;

    /**
     * @var array
     */
    protected array $titles = [];

    /**
     * Current instance of a product
     * optional and only needed at the runtime instance
     * this is the product from which the field are
     *
     * @var QUI\ERP\Products\Interfaces\ProductInterface|null
     */
    protected ?QUI\ERP\Products\Interfaces\ProductInterface $Product = null;

    /**
     * Model constructor.
     *
     * @param integer $fieldId
     * @param array $params - optional, field params (system, require, standard)
     */
    public function __construct(int $fieldId, array $params = [])
    {
        $this->id = $fieldId;

        if (QUI::isBackend()) {
            $this->setAttribute('viewType', 'backend');
        }

        // field types
        if (
            isset($params['public'])
            && (is_bool($params['public']) || is_int($params['public']))
        ) {
            $this->public = (bool)$params['public'];
        }

        // title description are always public
        if ($this->id === 4 || $this->id === 5) {
            $this->public = true;
        }


        if (
            isset($params['system'])
            && (is_bool($params['system']) || is_int($params['system']))
        ) {
            $this->system = (bool)$params['system'];
        }

        if (
            isset($params['required'])
            && (is_bool($params['required']) || is_int($params['required']))
        ) {
            $this->require = (bool)$params['required'];
        }

        if (
            isset($params['standard'])
            && (is_bool($params['standard']) || is_int($params['standard']))
        ) {
            $this->standard = (bool)$params['standard'];
        }

        if (
            isset($params['showInDetails'])
            && (is_bool($params['showInDetails']) || is_int($params['showInDetails']))
        ) {
            $this->showInDetails = (bool)$params['showInDetails'];
        }

        if (isset($params['defaultValue'])) {
            $this->defaultValue = $params['defaultValue'];
        }


        if ($this->isSystem()) {
            $this->standard = true;
        }

        if (isset($params['options'])) {
            $this->setOptions($params['options']);
        }
    }

    /**
     * Return the view for the backend
     *
     * @return View
     */
    public function getBackendView(): View
    {
        $Field = new View($this->getFieldDataForView());
        $Field->setProduct($this->Product);

        return $Field;
    }

    /**
     * Return the view for the frontend
     *
     * @return View
     */
    public function getFrontendView(): View
    {
        $Field = new View($this->getFieldDataForView());
        $Field->setProduct($this->Product);

        return $Field;
    }

    /**
     * Return the field data for a view
     *
     * @return array
     */
    protected function getFieldDataForView(): array
    {
        $attributes = $this->getAttributes();
        $attributes['value'] = $this->getValue();

        return $attributes;
    }

    /**
     * Create a unique field with the current field data
     *
     * @return UniqueField
     */
    public function createUniqueField(): UniqueField
    {
        return new UniqueField($this->getId(), $this->getAttributesForUniqueField());
    }

    /**
     * Return the name of the JavaScript Control for the field
     *
     * This is the JavaScript control used in the product panel for setting the field value!
     *
     * @return string
     */
    abstract public function getJavaScriptControl(): string;

    /**
     * Return the view
     *
     * @return View
     */
    public function getView(): View
    {
        return match ($this->getAttribute('viewType')) {
            'backend' => $this->getBackendView(),
            default => $this->getFrontendView(),
        };
    }

    /**
     * is over writable
     * -> if a control needs another view for eq. details tabs
     * -> every field has the possibility to display only the value (and not the select dropdowns)
     *
     * @return View
     */
    public function getValueView(): View
    {
        if ($this->getAttribute('viewType') === 'backend') {
            return $this->getBackendView();
        }

        return $this->getFrontendView();
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
     * @throws Exception
     * @deprecated ?
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();

        return new QUI\ERP\Money\Price(0, $Currency);
    }

    /**
     * saves / update the field
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     * @todo value check
     *
     */
    public function save(): void
    {
        QUI\Permissions\Permission::checkPermission('field.edit');

        QUI\Permissions\Permission::checkPermission(
            "permission.products.fields.field{$this->getId()}.edit"
        );

        $allowedAttributes = Fields::getChildAttributes();
        $defaultValue = '';

        if (!is_null($this->defaultValue)) {
            $defaultValue = json_encode($this->defaultValue);
        }

        $data = [
            'standardField' => $this->isStandard() ? 1 : 0,
            'systemField' => $this->isSystem() ? 1 : 0,
            'requiredField' => $this->isRequired() ? 1 : 0,
            'publicField' => $this->isPublic() ? 1 : 0,
            'showInDetails' => $this->showInDetails() ? 1 : 0,
            'defaultValue' => $defaultValue
        ];

        foreach ($allowedAttributes as $attribute) {
            if (
                $attribute == 'standardField'
                || $attribute == 'systemField'
                || $attribute == 'requiredField'
                || $attribute == 'publicField'
                || $attribute == 'showInDetails'
            ) {
                continue;
            }

            if ($this->existsAttribute($attribute)) {
                $data[$attribute] = $this->getAttribute($attribute);
                continue;
            }

            $data[$attribute] = '';
        }

        // options json check
        $data['options'] = '';
        $options = $this->getOptions();

        if (!empty($options)) {
            $data['options'] = json_encode($options);
        }


        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.field.save', [
                'id' => $this->getId()
            ]),
            '',
            $data
        );

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            $data,
            ['id' => $this->getId()]
        );

        // clear field cache
        QUI\Cache\LongTermCache::clear('quiqqer/products/fields');

        QUI\Cache\LongTermCache::clear(
            QUI\ERP\Products\Handler\Fields::getFieldCacheName($this->getId())
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldSave', [$this]);
    }

    /**
     * Delete the field
     *
     * @throws QUI\ERP\Products\Field\Exception
     * @throws QUI\Permissions\Exception
     * @throws QUI\Exception
     */
    public function delete(): void
    {
        QUI\Permissions\Permission::checkPermission('field.delete');

        if ($this->isSystem()) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exceptions.system.fields.cant.be.deleted',
                [
                    'id' => $this->getId(),
                    'title' => $this->getTitle()
                ]
            ]);
        }

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldDeleteBefore', [$this]);

        QUI\Watcher::addString(
            QUI::getLocale()->get('quiqqer/products', 'watcher.message.field.delete', [
                'id' => $this->getId(),
                'title' => $this->getTitle()
            ])
        );

        $fieldId = $this->getId();

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            ['id' => $fieldId]
        );

        // delete the locale
        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.$fieldId.title"
        );

        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.$fieldId.workingtitle"
        );

        // permission header locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.permission.products.fields.field$fieldId._header"
        );

        // view permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field$fieldId.view.title"
        );

        // edit permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field$fieldId.edit.title"
        );


        // delete column
        QUI::getDataBase()->table()->deleteColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            Search::getSearchFieldColumnName($this)
        );

        // delete cache
        QUI\Cache\LongTermCache::clear(
            Fields::getFieldCacheName($this->getId())
        );


        // delete permission
        // delete view permission
        QUI::getPermissionManager()->deletePermission(
            "permission.products.fields.field$fieldId.view"
        );

        // delete edit permission
        QUI::getPermissionManager()->deletePermission(
            "permission.products.fields.field$fieldId.edit"
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldDelete', [$this]);
    }

    /**
     * Delete the field
     *
     * @throws QUI\Exception
     */
    public function deleteSystemField(): void
    {
        QUI\Permissions\Permission::checkPermission('field.delete');
        QUI\Permissions\Permission::checkPermission('field.delete.systemfield');

        QUI::getDataBase()->delete(
            QUI\ERP\Products\Utils\Tables::getFieldTableName(),
            ['id' => $this->getId()]
        );

        $fieldId = $this->getId();

        // delete the locale
        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.$fieldId.title"
        );

        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.$fieldId.workingtitle"
        );

        // permission header locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.permission.products.fields.field$fieldId._header"
        );

        // view permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field$fieldId.view.title"
        );

        // edit permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field$fieldId.edit.title"
        );

        // delete column
        QUI::getDataBase()->table()->deleteColumn(
            QUI\ERP\Products\Utils\Tables::getProductCacheTableName(),
            Search::getSearchFieldColumnName($this)
        );


        // delete cache
        QUI\Cache\LongTermCache::clear(
            Fields::getFieldCacheName($this->getId())
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldDeleteSystemfield', [$this]);
    }

    /**
     * Is the field a public field
     * is the field visible at the product view
     *
     * @return boolean
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * Set the public field status
     *
     * @param boolean $status
     */
    public function setPublicStatus(bool $status): void
    {
        $this->public = $status;
    }

    /**
     * Is the field an own field from the product?
     *
     * @return boolean
     */
    public function isOwnField(): bool
    {
        return $this->ownField;
    }

    /**
     * Set the own field status
     *
     * @param boolean $status
     */
    public function setOwnFieldStatus(bool $status): void
    {
        $this->ownField = $status;
    }

    /**
     * Is the field unassigned?
     *
     * @return boolean
     */
    public function isUnassigned(): bool
    {
        return $this->unassigned;
    }

    /**
     * Set the show in details field status
     * Should the field be displayed in the details or not.
     *
     * @param boolean $status
     */
    public function setShowInDetailsStatus(bool $status): void
    {
        $this->showInDetails = $status;
    }

    /**
     * Should the field be displayed in the details?
     *
     * @return bool
     */
    public function showInDetails(): bool
    {
        return $this->showInDetails;
    }

    /**
     * Set the unassigned status
     *
     * @param boolean $status
     */
    public function setUnassignedStatus(bool $status): void
    {
        $this->unassigned = $status;
    }

    /**
     * Return the default value
     *
     * @return mixed|null
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Set the default value
     *
     * @param mixed $value
     *
     * @throws QUI\Exception
     */
    public function setDefaultValue(mixed $value): void
    {
        $this->validate($value);
        $this->defaultValue = $this->cleanup($value);
    }

    /**
     * Clears the default value
     */
    public function clearDefaultValue(): void
    {
        $this->defaultValue = null;
    }

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField(): bool
    {
        return false;
    }

    /**
     * Set the field name
     *
     * @param mixed $value
     * @deprecated maybe? ... getTitle makes more sense
     */
    public function setName(mixed $value): void
    {
        $this->name = $value;
    }

    /**
     * Set the field value
     *
     * @param mixed $value
     *
     * @throws QUI\Exception
     */
    public function setValue(mixed $value): void
    {
        $this->validate($value);
        $this->value = $this->cleanup($value);
    }

    /**
     * clears the current value of the field
     */
    public function clearValue(): void
    {
        $this->value = null;
    }

    /**
     * @param array|string $options - field options
     */
    public function setOptions(array|string $options): void
    {
        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        if (is_array($options)) {
            foreach ($options as $key => $value) {
                $this->setOption($key, $value);
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
        $this->options[$option] = $value;
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param mixed $value - value of the attribute
     */
    public function setAttribute(string $name, mixed $value): void
    {
        switch ($name) {
            case 'name':
            case 'type':
            case 'search_type':
            case 'priority':
            case 'source':
                $value = QUI\Utils\Security\Orthos::clear($value);
                break;

            case 'prefix':
            case 'suffix':
                if (!empty($value)) {
                    $value = json_encode(json_decode($value, true));
                }
                break;

            case 'standardField':
                $this->standard = (bool)$value;

                if ($this->isSystem()) {
                    $this->standard = true;
                }

                return;

            case 'systemField':
                // system field type could not be changed
                return;

            case 'requiredField':
                $this->require = (bool)$value;
                return;

            case 'publicField':
                $this->public = (bool)$value;
                return;

            case 'showInDetails':
                $this->showInDetails = (bool)$value;
                return;
        }

        parent::setAttribute($name, $value);
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
     * Return the current value
     *
     * @return string|array
     */
    public function getValue(): mixed
    {
        if (is_null($this->value)) {
            return $this->getDefaultValue();
        }

        return $this->value;
    }

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param Locale|null $Locale (optional)
     * @return string|array
     */
    public function getValueByLocale(?Locale $Locale = null): string|array
    {
        return $this->getValue();
    }

    /**
     * Return value for use in product search cache
     *
     * @param QUI\Locale|null $Locale
     * @return string|null
     */
    public function getSearchCacheValue(?Locale $Locale = null): ?string
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->getValueByLocale($Locale);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Return the value of the option, if the option exists
     *
     * @param string $option - option name
     * @return mixed
     */
    public function getOption(string $option): mixed
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return false;
    }

    /**
     * Return the suffix
     *
     * @param Locale|null $Locale
     * @return string|bool
     */
    public function getSuffix(QUI\Locale $Locale = null): bool|string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $suffix = $this->getAttribute('suffix');
        $suffix = json_decode($suffix, true);

        if (!is_array($suffix)) {
            return $this->getAttribute('suffix');
        }

        if (isset($suffix[$current])) {
            return $suffix[$current];
        }

        return $this->getAttribute('suffix');
    }

    /**
     * Return the prefix
     *
     * @param ?QUI\Locale $Locale
     * @return string|bool
     */
    public function getPrefix(Locale $Locale = null): bool|string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();
        $prefix = $this->getAttribute('prefix');
        $prefix = json_decode($prefix, true);

        if (!is_array($prefix)) {
            return $this->getAttribute('prefix');
        }

        if (isset($prefix[$current])) {
            return $prefix[$current];
        }

        return $this->getAttribute('prefix');
    }

    /**
     * Return the title of the field
     * The title are from the user and translated
     *
     * @param QUI\Locale|bool $Locale - optional
     * @return string
     */
    public function getTitle($Locale = false): string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $current = $Locale->getCurrent();

        if (isset($this->titles[$current])) {
            return $this->titles[$current];
        }

        $this->titles[$current] = $Locale->get(
            'quiqqer/products',
            'products.field.' . $this->getId() . '.title'
        );

        return $this->titles[$current];
    }

    /**
     * Return the working title
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getWorkingTitle(Locale $Locale = null): string
    {
        $var = 'products.field.' . $this->getId() . '.workingtitle';
        $group = 'quiqqer/products';

        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        if ($Locale->exists($group, $var)) {
            return $Locale->get($group, $var);
        }

        return $this->getTitle();
    }

    /**
     * Return the description
     *
     * @param QUI\Locale|null $Locale - optional
     * @return string
     */
    public function getDescription(?QUI\Locale $Locale): string
    {
        $var = 'products.field.' . $this->getId() . '.description';
        $group = 'quiqqer/products';

        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        if ($Locale->exists($group, $var)) {
            return $Locale->get($group, $var);
        }

        return '';
    }

    /**
     * Return the help description, if the field own a help
     *
     * @param null $Locale
     * @return string
     */
    public function getHelp($Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI::getLocale();
        }

        $type = $this->getType();
        $typeData = Fields::getFieldTypeData($type);

        if (empty($typeData['help'])) {
            return '';
        }

        if (!is_array($typeData['help'])) {
            return '';
        }

        $group = $typeData['help'][0];
        $var = $typeData['help'][1];

        if ($Locale->exists($group, $var)) {
            return $Locale->get($group, $var);
        }

        return '';
    }

    /**
     * Return the field type
     *
     * @return string
     */
    public function getType(): string
    {
        if (!is_null($this->type)) {
            return $this->type;
        }

        $class = parent::getType();

        // quiqqer/product fields
        if (str_contains($class, 'QUI\ERP\Products\Field\Types\\')) {
            $this->type = str_replace('QUI\ERP\Products\Field\Types\\', '', $class);

            return $this->type;
        }

        $fieldTypes = Fields::getFieldTypes();
        $fieldTypes = array_filter($fieldTypes, function ($entry) use ($class) {
            if (!isset($entry['src'])) {
                return false;
            }

            return trim($entry['src'], '\\') == trim($class, '\\');
        });

        if (empty($fieldTypes)) {
            return $class;
        }

        $this->type = reset($fieldTypes)['name'];

        return $this->type;
    }

    /**
     * Return column type for database column (cache table)
     *
     * @return string
     */
    public function getColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * Get search type of this field
     *
     * @return integer|false - search type id oder false if none set
     */
    public function getSearchType(): bool|int
    {
        return $this->getAttribute('search_type');
    }

    /**
     * Get default search type
     *
     * @return string|null
     */
    public function getDefaultSearchType(): ?string
    {
        return null;
    }

    /**
     * Get all available search types for this field
     *
     * @return array
     */
    public function getSearchTypes(): array
    {
        if (!$this->isSearchable()) {
            return [];
        }

        return Search::getSearchTypes();
    }

    /**
     * Return the search type
     *
     * @return bool|int|string
     */
    public function getSearchDataType(): bool|int|string
    {
        return $this->searchDataType;
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['id'] = $this->getId();
        $attributes['title'] = $this->getTitle();
        $attributes['workingtitle'] = $this->getWorkingTitle();
        $attributes['type'] = $this->getType();
        $attributes['options'] = $this->getOptions();
        $attributes['jsControl'] = $this->getJavaScriptControl();
        $attributes['searchvalue'] = $this->getSearchCacheValue();
        $attributes['defaultValue'] = $this->getDefaultValue();
        $attributes['help'] = $this->getHelp();

        $attributes['custom'] = $this->isCustomField();
        $attributes['unassigned'] = $this->isUnassigned();
        $attributes['isRequired'] = $this->isRequired();
        $attributes['isStandard'] = $this->isStandard();
        $attributes['isSystem'] = $this->isSystem();
        $attributes['isPublic'] = $this->isPublic();
        $attributes['searchable'] = $this->isSearchable();
        $attributes['ownField'] = $this->isOwnField();
        $attributes['showInDetails'] = $this->showInDetails();
        $attributes['jsSettings'] = '';

        if (method_exists($this, 'getJavaScriptSettings')) {
            $attributes['jsSettings'] = $this->getJavaScriptSettings();
        }

        return $attributes;
    }

    /**
     * Return the attributes for a unique field
     *
     * @return array
     */
    public function getAttributesForUniqueField(): array
    {
        $attributes = $this->getAttributes();
        $attributes['id'] = $this->getId();
        $attributes['value'] = $this->getValue();
        $attributes['__class__'] = get_class($this);

        if ($this instanceof CustomInputFieldInterface) {
            $attributes['userInput'] = $this->getUserInput();
        }

        return $attributes;
    }

    /**
     * Get all products associated with this field
     *
     * @return QUI\ERP\Products\Product\Product[]
     */
    public function getProducts(): array
    {
        return Products::getProducts([
            'where' => [
                'fieldData' => [
                    'type' => '%LIKE%',
                    'value' => '"id":' . $this->id . ','
                ]
            ]
        ]);
    }

    /**
     * Get IDs of all products associated with this field
     *
     * @return int[]
     */
    public function getProductIds(): array
    {
        return Products::getProductIds([
            'where' => [
                'fieldData' => [
                    'type' => '%LIKE%',
                    'value' => '"id":' . $this->id . ','
                ]
            ]
        ]);
    }

    /**
     * Return the field attributes for a product
     *
     * @return array
     */
    public function toProductArray(): array
    {
        return [
            'id' => $this->getId(),
            'value' => $this->getValue(),
            'unassigned' => $this->isUnassigned(),
            'ownField' => $this->isOwnField(),
            'isPublic' => $this->isPublic(),
            'isRequired' => $this->isRequired(),
            'showInDetails' => $this->showInDetails(),
            'type' => $this->getType(),
            'search_type' => $this->getSearchType()
        ];
    }

    /**
     * Is the value of this value an empty value?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem(): bool
    {
        return $this->system;
    }

    /**
     * Is the field a standard field?
     *
     * @return bool
     */
    public function isStandard(): bool
    {
        if ($this->isSystem()) {
            return true;
        }

        return $this->standard;
    }

    /**
     * @return boolean
     */
    public function isRequired(): bool
    {
        return $this->require;
    }

    /**
     * Is this field allowed as a searchable field?
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Calculates a range with individual steps between a min and a max number
     *
     * @param float|integer $min
     * @param float|integer $max
     * @return array - contains values from min to max with calculated steps in between
     */
    public function calculateValueRange(float|int $min, float|int $max): array
    {
        if ($min < 1) {
            $start = 0.1;
        } else {
            // round down to lowest 10 (e.g.: 144 = 140; 2554 = 2550)
            $floorPrecision = 1;

            if ((string)mb_strlen((int)$min) > 1) {
                $floorPrecision = 10;
            }

            $start = floor($min / $floorPrecision) * $floorPrecision;
            $start = (int)$start;
        }

        $value = $start;
        $range[] = $value;

        while ($value < $max) {
            if (round($value, 1) < 1) {
                $add = 0.1;
            } else {
                $add = 1;
                $i = 10;

                while ($value >= $i) {
                    $i *= 10;
                    $add *= 10;
                }

                $value = floor($value / $add) * $add;
            }

            $value += $add;
            $range[] = $value;
        }

        return $range;
    }

    /**
     * Checks if a user has view permission for this field
     *
     * @param User|null $User
     * @return bool
     */
    public function hasViewPermission(QUI\Interfaces\Users\User $User = null): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        try {
            QUI\Permissions\Permission::checkPermission(
                "permission.products.fields.field{$this->getId()}.view",
                $User
            );

            return true;
        } catch (QUI\Exception) {
        }

        return false;
    }

    /**
     * @param $Product - Product instance
     */
    public function setProduct($Product): void
    {
        $this->Product = $Product;
    }
}
