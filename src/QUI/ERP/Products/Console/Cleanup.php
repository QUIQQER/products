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
class Cleanup extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:cleanup')
            ->setDescription(
                'Cleanup all products. Delete not existing fields.'
            );
    }

    /**
     * Execute the console tool
     */
    public function execute(): void
    {
        QUI\ERP\Products\Handler\Products::cleanup();
    }
}
