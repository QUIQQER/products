<?php

/**
 * This file contains QUI\ERP\Products\Utils\ProductTypes
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Utils\Singleton;

/**
 * Class ProductTypes
 *
 * @package QUI\ERP\Products\Product
 */
class ProductTypes extends Singleton
{
    /**
     * Return the product type provider classes
     *
     * @return array
     */
    public function getProductTypes()
    {
        $cache = QUI\ERP\Products\Handler\Cache::getBasicCachePath().'types';

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $packages = QUI::getPackageManager()->getInstalled();
        $provider = [];

        foreach ($packages as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $packageProvider = $Package->getProvider();

                if (isset($packageProvider['productType'])) {
                    $provider = \array_merge($provider, $packageProvider['productType']);
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI\Cache\LongTermCache::set($cache, $provider);

        return $provider;
    }

    /**
     * Exists the wanted product type?
     *
     * @param string $productType
     * @return bool
     */
    public function exists($productType)
    {
        $productTypes = $this->getProductTypes();
        $productType  = trim($productType, '\\');

        foreach ($productTypes as $type) {
            $type = trim($type, '\\');

            if ($productType === $type) {
                return true;
            }
        }

        return false;
    }
}
