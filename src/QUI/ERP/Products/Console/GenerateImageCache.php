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
class GenerateImageCache extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('products:generate-image-cache')
            ->setDescription(
                'Generate the primary image cache'
            );
    }

    /**
     * Execute the console tool
     */
    public function execute()
    {
        QUI\ERP\Products\Crons::generateCacheImagesOfProducts();
    }
}
