<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Handler\Search;

/**
 * Class UserInput
 *
 * This field type provides an input / textarea for individual user input by a frontend user.
 */
class UserInput extends QUI\ERP\Products\Field\Field
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
     * @var array
     */
    protected $disabled = [];

    /**
     * Attribute group constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setOptions([
            'inputType'     => 'input',
            'maxCharacters' => 100
        ]);

        parent::__construct($fieldId, $params);
    }

    /**
     * Return the FrontendView
     *
     * @return UserInputFrontendView
     */
    public function getFrontendView()
    {
        $View = new UserInputFrontendView(
            $this->getFieldDataForView()
        );

        $View->setProduct($this->Product);

        return $View;
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UserInput';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UserInputSettings';
    }


    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param integer|string $value - User value = "[key, user value]"
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        if (\is_string($value) || \is_numeric($value)) {
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
     * @param integer|string $value
     * @return int|null
     */
    public function cleanup($value)
    {
        if (!\is_string($value) && !\is_numeric($value)) {
            return null;
        }

        // Remove everything that could be dangerous.
        $value = \preg_replace("/[^\p{L}\p{N}\p{M} ]/ui", '', $value);
        $value = \strip_tags($value);

        return $value;
    }

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField()
    {
        return true;
    }
}