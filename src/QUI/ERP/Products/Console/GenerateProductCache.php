<?php

/**
 * This file contains \QUI\ERP\Products\Console
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Product\Cache\ProductCache;

use QUI\Lock\Exception;

use function count;
use function date;
use function mb_strlen;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * Console tool for HKL used patches
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class GenerateProductCache extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:generate-product-cache')
            ->setDescription('Generate the primary product cache')
            ->addArgument('unlock', 'Ignore the LOCK flag or unlock the LOCK flag', false, true)
            ->addArgument('rebuild', 'Ignores the current cache and rebuild the entire cache', false, true)
            ->addArgument('withControlCache', 'Create control cache, too', false, true);
    }

    /**
     * Execute the console tool
     * @throws Exception
     */
    public function execute(): void
    {
        Products::$createFrontendCache = true;

        // LOCK
        try {
            $Package = QUI::getPackage('quiqqer/products');
            $lockKey = 'products-generating';

            if ($this->getArgument('unlock')) {
                QUI\Lock\Locker::unlock($Package, $lockKey);
            }

            if (QUI\Lock\Locker::isLocked($Package, $lockKey)) {
                $this->writeLn('Generating is currently running', 'red');
                exit;
            }

            QUI\Lock\Locker::lock($Package, $lockKey);
        } catch (QUI\Exception $Exception) {
            $this->writeLn($Exception->getMessage(), 'red');
            exit;
        }


        // check cache
        if ($this->getArgument('rebuild')) {
            QUI\Cache\LongTermCache::clear('quiqqer/products');
        }


        // execute
        $controlCache = $this->getArgument('withControlCache');
        $productIds = Products::getProductIds([
            'where' => [
                'parent' => null
            ]
        ]);

        $count = count($productIds);
        $i = 0;

        foreach ($productIds as $productId) {
            // check cache, if no rebuild is set
            if (!$this->getArgument('rebuild')) {
                try {
                    QUI\Cache\LongTermCache::get(QUI\ERP\Products\Handler\Cache::getProductCachePath($productId));
                    continue;
                } catch (QUI\Exception) {
                }
            }

            ProductCache::create($productId, $controlCache);
            Products::cleanProductInstanceMemCache();


            $out = str_pad($i, mb_strlen($count), '0', STR_PAD_LEFT);
            $time = date('H:i:s');
            $this->writeLn('- ' . $time . ' :: ' . $out . ' of ' . $count);

            $i++;
        }

        $this->writeLn('Cache is successfully build');
        $this->writeLn();

        QUI\Lock\Locker::unlock($Package, $lockKey);
        Products::$createFrontendCache = false;
    }
}
