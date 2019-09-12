<?php

/**
 * This file contains \QUI\ERP\Products\Console
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Cache\ProductCache;

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
        $count      = \count($productIds);

        $i = 0;

        $Pool      = null;
        $poolCount = 0;

        if (\class_exists('Pool')) {
            $Pool = new \Pool(4);
        } else {
            $this->writeLn('No threads installed. The product cache build takes longer to build up');
        }

        foreach ($productIds as $productId) {
            if ($Pool) {
                $Pool->submit(new QUI\ERP\Products\Product\Cache\CacheThread($productId));
                $poolCount++;

                if ($poolCount === 4) {
                    while ($Pool->collect()) {
                    }

                    $poolCount = 0;
                }
            } else {
                ProductCache::create($productId);
            }

            if ($i % 10 === 0) {
                $out  = \str_pad($i, \mb_strlen($count), '0', \STR_PAD_LEFT);
                $time = \date('H:i:s');

                $this->writeLn('- '.$time.' :: '.$out.' of '.$count);
            }

            $i++;
        }

        if ($Pool) {
            $Pool->shutdown();
        }

        $this->writeLn('Cache is successfully build');
        $this->writeLn('');
    }
}
