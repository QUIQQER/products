<?php

/**
 * This file contains QUI\ERP\Products\Utils\ProductTypes
 */

namespace QUI\ERP\Products\Utils;

use QUI;
use QUI\Utils\Singleton;

use function array_filter;
use function array_merge;
use function is_a;

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
    public function getProductTypes(): array
    {
        $cache = QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'types';

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
                    $provider = array_merge($provider, $packageProvider['productType']);
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI\Cache\LongTermCache::set($cache, $provider);

        return $provider;
    }


    /**
     * Get all product types (class names) that are variant parents.
     *
     * @return string[]
     */
    public function getVariantParentProductTypes(): array
    {
        $cache = QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'types/variant_parents';

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $variantParentTypes = array_filter($this->getProductTypes(), function ($productType) {
            return is_a($productType, QUI\ERP\Products\Product\Types\VariantParent::class, true);
        });

        QUI\Cache\LongTermCache::set($cache, $variantParentTypes);

        return $variantParentTypes;
    }

    /**
     * Get all product types (class names) that are variant children.
     *
     * @return string[]
     */
    public function getVariantChildProductTypes(): array
    {
        $cache = QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'types/variant_child';

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $variantChildTypes = array_filter($this->getProductTypes(), function ($productType) {
            return is_a($productType, QUI\ERP\Products\Product\Types\VariantChild::class, true);
        });

        QUI\Cache\LongTermCache::set($cache, $variantChildTypes);

        return $variantChildTypes;
    }

    /**
     * Exists the wanted product type?
     *
     * @param string $productType
     * @return bool
     */
    public function exists(string $productType): bool
    {
        $productTypes = $this->getProductTypes();
        $productType = trim($productType, '\\');

        foreach ($productTypes as $type) {
            $type = trim($type, '\\');

            if ($productType === $type) {
                return true;
            }
        }

        return false;
    }
}
