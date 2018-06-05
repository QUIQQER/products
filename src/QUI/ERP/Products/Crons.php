<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */

namespace QUI\ERP\Products;

use QUI;
use QUI\ERP\Products\Handler\Products;

/**
 * Class EventHandling
 *
 * @package QUI\ERP\Products
 */
class Crons
{
    /**
     * Time for one product to update its cache (seconds)
     */
    const PRODUCT_CACHE_UPDATE_TIME = 3;

    /**
     * Updates cache values for all products
     *
     * @throws QUI\Exception
     */
    public static function updateProductCache()
    {
        // clear search cache
        QUI\ERP\Products\Search\Cache::clear();

        $ids = Products::getProductIds();

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($ids as $id) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getProduct($id);
                $Product->updateCache();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    'cron :: updateProductCache() :: Could not update cache'
                    . ' for Product #' . $Product->getId() . ' -> '
                    . $Exception->getMessage()
                );
            }
        }

        // reset time limit
        set_time_limit(ini_get('max_execution_time'));
    }

    /**
     * Go through all images and build the image cache
     * So the first call is faster
     */
    public static function generateCacheImagesOfProducts()
    {
        $ids = Products::getProductIds();

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($ids as $id) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getProduct($id);
                $Image   = $Product->getImage();

                $Image->createCache();

                $Image->createSizeCache(400); // product gallery
                $Image->createSizeCache(500); // product slider
                $Image->createSizeCache(100, 200); // product gallery. preview
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addNotice($Exception->getMessage(), array(
                    'stack'     => $Exception->getTraceAsString(),
                    'productId' => $id,
                    'cron'      => 'generateCacheImagesOfProducts'
                ));
            }
        }

        // reset time limit
        set_time_limit(ini_get('max_execution_time'));
    }
}
