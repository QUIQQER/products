<?php

/**
 * This file contains \QUI\ERP\Products\Console
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;

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

                if (!$Product->isActive()) {
                    continue;
                }

                $Product->setAttribute('viewType', 'frontend');
                $Product->getView()->getPrice();

                if ($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) {
                    $Product->getVariants();
                }

                // control cache
                $Control = new ProductControl([
                    'Product' => $Product
                ]);

                $Control->create();
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
