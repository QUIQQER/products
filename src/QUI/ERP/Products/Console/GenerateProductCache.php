<?php

/**
 * This file contains \QUI\ERP\Products\Console
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Products;

/**
 * Console tool for HKL used patches
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class GenerateProductCache extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('products:generate-product-cache')
            ->setDescription(
                'Generate the primary product cache'
            );
    }

    /**
     * Execute the console tool
     */
    public function execute()
    {
        $productIds = Products::getProductIds();

        $i = 0;

        foreach ($productIds as $productId) {
            try {
                $Product = Products::getNewProductInstance($productId);

                if ($Product->isActive()) {
                    $Product->setAttribute('viewType', 'frontend');
                    $Product->getView();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addInfo($Exception->getMessage());
            }

            if ($i % 100 === 0) {
                $this->writeLn('- '.$i);
            }

            $i++;
        }
    }
}
