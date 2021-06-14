<?php

namespace QUI\ERP\Products\Field;

/**
 * Interface CustomInputFieldInterface
 *
 * Represents a product field that allows user input as value.
 */
interface CustomInputFieldInterface
{
    /**
     * Return the user input text
     *
     * @return string|false
     */
    public function getUserInput();
}
