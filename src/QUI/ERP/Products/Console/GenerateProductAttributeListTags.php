<?php

/**
 * This file contains \Hklused\Machines\Patch
 */

namespace QUI\ERP\Products\Console;

use QUI;

/**
 * Console tool for HKL used patches
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class GenerateProductAttributeListTags extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
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
     */
    public function execute()
    {
        QUI\Permissions\Permission::isAdmin();
        QUI\ERP\Tags\Crons::generateProductAttributeListTags();
    }
}
