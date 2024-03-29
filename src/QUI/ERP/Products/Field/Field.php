<?php

/**
 * This file contains QUI\ERP\Products\Field\Field
 */

namespace QUI\ERP\Products\Field;

use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Search;

use function array_filter;
use function floor;
use function get_class;
use function is_array;
use function is_bool;
use function is_null;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function method_exists;
use function reset;
use function round;
use function str_replace;
use function strpos;
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
    protected $id;

    /**
     * @var bool
     */
    protected $system = false;

    /**
     * @var bool
     */
    protected $standard = false;

    /**
     * @var bool
     */
    protected $require = false;

    /**
     * unassigned = feld ist dem produkt nicht zugewiesen aber die daten soll das produkt trotzdem behalten
     * unassigned ist also ein nicht zugewiesenes feld welches das produkt als daten trotzdem hat
     *
     * @var bool
     */
    protected $unassigned = false;

    /**
     * @var bool
     */
    protected $ownField = false;

    /**
     * @var bool
     */
    protected $public = true;

    /**
     * @var null
     */
    protected $defaultValue = null;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Is this Field searchable?
     *
     * @var bool
     */
    protected $searchable = true;

    /**
     * Should the field be displayed in the details?
     * @var bool
     */
    protected $showInDetails = false;

    /**
     * @var string
     */
    protected $type = null;

    /**
     * Field-Name
     *
     * @var string
     */
    protected $name;

    /**
     * Field value
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Column type for database table (cache column)
     *
     * @var string
     */
    protected $columnType = 'LONGTEXT';

    /**
     * @var array
     */
    protected $searchTypes = [];

    /**
     * Searchdata type for values of this field
     *
     * @var int
     */
    protected $searchDataType = false;

    /**
     * @var array
     */
    protected $titles = [];

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
    public function __construct($fieldId, $params = [])
    {
        $this->id = (int)$fieldId;

        if (QUI::isBackend()) {
            $this->setAttribute('viewType', 'backend');
        }

        // field types
        if (
            isset($params['public'])
            && (is_bool($params['public']) || \is_int($params['public']))
        ) {
            $this->public = $params['public'] ? true : false;
        }

        // title description are always public
        if ($this->id === 4 || $this->id === 5) {
            $this->public = true;
        }


        if (
            isset($params['system'])
            && (is_bool($params['system']) || \is_int($params['system']))
        ) {
            $this->system = $params['system'] ? true : false;
        }

        if (
            isset($params['required'])
            && (is_bool($params['required']) || \is_int($params['required']))
        ) {
            $this->require = $params['required'] ? true : false;
        }

        if (
            isset($params['standard'])
            && (is_bool($params['standard']) || \is_int($params['standard']))
        ) {
            $this->standard = $params['standard'] ? true : false;
        }

        if (
            isset($params['showInDetails'])
            && (is_bool($params['showInDetails']) || \is_int($params['showInDetails']))
        ) {
            $this->showInDetails = $params['showInDetails'] ? true : false;
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
     * @return \QUI\ERP\Products\Field\View
     */
    public function getBackendView()
    {
        $Field = new View($this->getFieldDataForView());
        $Field->setProduct($this->Product);

        return $Field;
    }

    /**
     * Return the view for the frontend
     *
     * @return \QUI\ERP\Products\Field\View
     */
    public function getFrontendView()
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
    protected function getFieldDataForView()
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
    public function createUniqueField()
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
    abstract public function getJavaScriptControl();

    /**
     * Return the view
     *
     * @return \QUI\ERP\Products\Field\View
     */
    public function getView()
    {
        switch ($this->getAttribute('viewType')) {
            case 'backend':
                return $this->getBackendView();

            default:
                return $this->getFrontendView();
        }
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Change the price of the product
     * Returns the price object
     *
     * @return QUI\ERP\Money\Price
     * @deprecated ?
     */
    public function getPrice()
    {
        $Currency = QUI\ERP\Currency\Handler::getDefaultCurrency();
        $Price = new QUI\ERP\Money\Price(0, $Currency);

        return $Price;
    }

    /**
     * saves / update the field
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     * @todo value check
     *
     */
    public function save()
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
    public function delete()
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
            "products.field.{$fieldId}.title"
        );

        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.{$fieldId}.workingtitle"
        );

        // permission header locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.permission.products.fields.field{$fieldId}._header"
        );

        // view permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field{$fieldId}.view.title"
        );

        // edit permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field{$fieldId}.edit.title"
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
            "permission.products.fields.field{$fieldId}.view"
        );

        // delete edit permission
        QUI::getPermissionManager()->deletePermission(
            "permission.products.fields.field{$fieldId}.edit"
        );

        QUI::getEvents()->fireEvent('onQuiqqerProductsFieldDelete', [$this]);
    }

    /**
     * Delete the field
     *
     * @throws QUI\Exception
     */
    public function deleteSystemField()
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
            "products.field.{$fieldId}.title"
        );

        QUI\Translator::delete(
            'quiqqer/products',
            "products.field.{$fieldId}.workingtitle"
        );

        // permission header locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.permission.products.fields.field{$fieldId}._header"
        );

        // view permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field{$fieldId}.view.title"
        );

        // edit permission locale
        QUI\Translator::delete(
            'quiqqer/products',
            "permission.products.fields.field{$fieldId}.edit.title"
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
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Set the public field status
     *
     * @param boolean $status
     */
    public function setPublicStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->public = $status;
    }

    /**
     * Is the field a own field from the product?
     *
     * @return boolean
     */
    public function isOwnField()
    {
        return $this->ownField;
    }

    /**
     * Set the own field status
     *
     * @param boolean $status
     */
    public function setOwnFieldStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->ownField = $status;
    }

    /**
     * Is the field unassigned?
     *
     * @return boolean
     */
    public function isUnassigned()
    {
        return $this->unassigned;
    }

    /**
     * Set the show in details field status
     * Should the field be displayed in the details or not.
     *
     * @param boolean $status
     */
    public function setShowInDetailsStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->showInDetails = $status;
    }

    /**
     * Should the field be displayed in the details?
     *
     * @return bool
     */
    public function showInDetails()
    {
        return $this->showInDetails;
    }

    /**
     * Set the unassigned status
     *
     * @param boolean $status
     */
    public function setUnassignedStatus($status)
    {
        if (!is_bool($status)) {
            $status = (bool)$status;
        }

        $this->unassigned = $status;
    }

    /**
     * Return the default value
     *
     * @return mixed|null
     */
    public function getDefaultValue()
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
    public function setDefaultValue($value)
    {
        $this->validate($value);
        $this->defaultValue = $this->cleanup($value);
    }

    /**
     * Clears the default value
     */
    public function clearDefaultValue()
    {
        $this->defaultValue = null;
    }

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField()
    {
        return false;
    }

    /**
     * Set the field name
     *
     * @param string $name
     * @deprecated maybe? ... getTitle makes more sense
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the field value
     *
     * @param mixed $value
     *
     * @throws QUI\Exception
     */
    public function setValue($value)
    {
        $this->validate($value);
        $this->value = $this->cleanup($value);
    }

    /**
     * clears the current value of the field
     */
    public function clearValue()
    {
        $this->value = null;
    }

    /**
     * @param array|string $options - field options
     */
    public function setOptions($options)
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
    public function setOption($option, $value)
    {
        if (is_string($option)) {
            $this->options[$option] = $value;
        }
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param string|boolean|array|object $val - value of the attribute
     * @return Field
     */
    public function setAttribute($name, $val)
    {
        switch ($name) {
            case 'name':
            case 'type':
            case 'search_type':
            case 'priority':
            case 'source':
                $val = QUI\Utils\Security\Orthos::clear($val);
                break;

            case 'prefix':
            case 'suffix':
                if (!empty($val)) {
                    $val = json_encode(json_decode($val, true));
                }
                break;

            case 'standardField':
                $this->standard = $val ? true : false;
                if ($this->isSystem()) {
                    $this->standard = true;
                }

                return $this;

            case 'systemField':
                // system field type could not be changed
                return $this;

            case 'requiredField':
                $this->require = $val ? true : false;

                return $this;

            case 'publicField':
                $this->public = $val ? true : false;

                return $this;

            case 'showInDetails':
                $this->showInDetails = $val ? true : false;

                return $this;
        }

        parent::setAttribute($name, $val);

        return $this;
    }

    /**
     * Return the field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the current value
     *
     * @return string|array
     */
    public function getValue()
    {
        if (is_null($this->value)) {
            return $this->getDefaultValue();
        }

        return $this->value;
    }

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param QUI\Locale $Locale (optional)
     * @return array|string
     */
    public function getValueByLocale($Locale = null)
    {
        return $this->getValue();
    }

    /**
     * Return value for use in product search cache
     *
     * @param QUI\Locale|null $Locale
     * @return string
     */
    public function getSearchCacheValue($Locale = null)
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->getValueByLocale($Locale);
    }

    /**
     * @return array|string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return the value of the option, if the option exists
     *
     * @param string $option - option name
     * @return mixed
     */
    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return false;
    }

    /**
     * Return the suffix
     *
     * @param QUI\Locale|bool $Locale
     * @return string|bool
     */
    public function getSuffix($Locale = false)
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
     * @param QUI\Locale|bool $Locale
     * @return string|bool
     */
    public function getPrefix($Locale = false)
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
    public function getTitle($Locale = false)
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
    public function getWorkingTitle($Locale = null)
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
    public function getHelp($Locale = null)
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
     * @return mixed|string
     */
    public function getType()
    {
        if (!is_null($this->type)) {
            return $this->type;
        }

        $class = parent::getType();

        // quiqqer/product fields
        if (strpos($class, 'QUI\ERP\Products\Field\Types\\') !== false) {
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
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * Get search type of this field
     *
     * @return integer|false - search type id oder false if none set
     */
    public function getSearchType()
    {
        return $this->getAttribute('search_type');
    }

    /**
     * Get default search type
     *
     * @return string|null
     */
    public function getDefaultSearchType()
    {
        return null;
    }

    /**
     * Get all available search types for this field
     *
     * @return array
     */
    public function getSearchTypes()
    {
        if (!$this->isSearchable()) {
            return [];
        }

        return Search::getSearchTypes();
    }

    /**
     * Return the search type
     *
     * @return string
     */
    public function getSearchDataType()
    {
        return $this->searchDataType;
    }

    /**
     * Return the attributes
     *
     * @return array
     */
    public function getAttributes()
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
     * Return the attributes for an unique field
     *
     * @return array
     */
    public function getAttributesForUniqueField()
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
    public function getProducts()
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
    public function getProductIds()
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
    public function toProductArray()
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
    public function isEmpty()
    {
        return empty($this->value);
    }

    /**
     * Is the field a system field?
     *
     * @return boolean
     */
    public function isSystem()
    {
        return $this->system;
    }

    /**
     * Is the field a standard field?
     *
     * @return bool
     */
    public function isStandard()
    {
        // systemfields are always standardfields
        if ($this->isSystem()) {
            return true;
        }

        return $this->standard;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->require;
    }

    /**
     * Is this field allowed as a searchable field?
     *
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * Calculates a range with individual steps between a min and a max number
     *
     * @param integer|float $min
     * @param integer|float $max
     * @return array - contains values from min to max with calculated steps inbetween
     */
    public function calculateValueRange($min, $max)
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
     * @param QUI\Users\User $User
     * @return bool
     */
    public function hasViewPermission($User = null)
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
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * @param $Product - Product instance
     */
    public function setProduct($Product)
    {
        $this->Product = $Product;
    }
}
