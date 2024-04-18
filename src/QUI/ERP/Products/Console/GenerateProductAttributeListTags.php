<?php

/**
 * This file contains QUI\ERP\Products\Console\GenerateProductAttributeListTags
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\Exception;

/**
 * Console tool for HKL used patches
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class GenerateProductAttributeListTags extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:generateProductAttributeListTags')
            ->setDescription(
                'Generate tags for projects/products from every product attribute list option'
            );
    }

    /**
     * Execute the console tool
     * @throws Exception
     */
    public function execute(): void
    {
        QUI\Permissions\Permission::isAdmin();
        QUI\ERP\Tags\Crons::generateProductAttributeListTags();
    }
}
