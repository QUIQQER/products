<?php

namespace QUI\ERP\Products\Field;

use QUI;

/**
 * Class CustomInputField
 *
 * Represents a product field that allows user input as value.
 */
abstract class CustomInputField extends QUI\ERP\Products\Field\Field implements CustomInputFieldInterface
{
    /**
     * Return the user input text
     *
     * @return string
     */
    abstract public function getUserInput(): string;

    /**
     * Is the field a custom field?
     *
     * @return boolean
     */
    public function isCustomField(): bool
    {
        return true;
    }
}
