<?php

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\Exception;
use QUI\ERP\Products\Field\View;

use function is_numeric;
use function is_string;
use function json_decode;
use function json_last_error;
use function mb_substr;
use function preg_replace;
use function strip_tags;

use const JSON_ERROR_NONE;

/**
 * Class UserInput
 *
 * This field type provides an input / textarea for individual user input by a frontend user.
 */
class UserInput extends QUI\ERP\Products\Field\CustomInputField
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
     * Attribute group constructor.
     *
     * @param int $fieldId
     * @param array $params
     */
    public function __construct(int $fieldId, array $params)
    {
        $this->setOptions([
            'inputType' => 'input', // "input", "input_inline", "textarea"
            'maxCharacters' => 100
        ]);

        parent::__construct($fieldId, $params);
    }

    /**
     * Return the FrontendView
     *
     * @return UserInputFrontendView
     */
    public function getFrontendView(): UserInputFrontendView
    {
        $View = new UserInputFrontendView(
            $this->getFieldDataForView()
        );

        $View->setProduct($this->Product);

        return $View;
    }

    /**
     * Return the view for the backend
     *
     * @return UserInputBackendView|View
     */
    public function getBackendView(): UserInputBackendView|View
    {
        $View = new UserInputBackendView(
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
        return 'package/quiqqer/products/bin/controls/fields/types/UserInput';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings(): string
    {
        return 'package/quiqqer/products/bin/controls/fields/types/UserInputSettings';
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

        if (is_string($value) || is_numeric($value)) {
            return;
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
     *
     * @param mixed $value
     * @return string|null
     */
    public function cleanup(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        if (is_string($value)) {
            $valueJsonDecoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $valueJsonDecoded;
            }
        }

        // Remove everything that could be dangerous.
        $value = preg_replace("/[^\p{L}\p{N}\p{M}\n ]/ui", '', $value);
        $value = strip_tags($value);

        return mb_substr($value, 0, (int)$this->getOption('maxCharacters'));
    }

    /**
     * Return the user input text
     *
     * @return string
     */
    public function getUserInput(): string
    {
        $userInput = $this->getValue();

        if (empty($userInput)) {
            return '';
        }

        return $userInput;
    }
}
