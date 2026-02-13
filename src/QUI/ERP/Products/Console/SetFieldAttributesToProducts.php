<?php

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Fields;

/**
 * Sets system attributes of all fields to all products
 */
class SetFieldAttributesToProducts extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:set-field-attributes-to-products')
            ->setDescription(
                'Set system field attributes to all products'
            );

        $this->addArgument(
            'fieldId',
            'Only apply attributes of a specific field',
            false,
            true
        );
    }

    /**
     * Execute the console tool
     */
    public function execute(): void
    {
        $fieldId = $this->getArgument('fieldId');

        if (empty($fieldId)) {
            $fieldId = null;
        } else {
            $fieldId = (int)$fieldId;
        }

        $this->writeLn("Start...");
        Fields::setFieldAttributesToProducts($fieldId);
        $this->writeLn("Finished.\n\n");
    }
}
