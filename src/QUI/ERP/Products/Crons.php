<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */

namespace QUI\ERP\Products;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\Exception;
use QUI\System\Log;

use function count;
use function ini_get;
use function set_time_limit;

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
    const PRODUCT_CACHE_UPDATE_TIME = 10;

    /**
     * Updates cache values for all products
     * @throws Exception
     */
    public static function updateProductCache(): void
    {
        // global watcher disable
        QUI\Watcher::$globalWatcherDisable = true;

        // clear search cache
        QUI\ERP\Products\Search\Cache::clear();

        $ids = Products::getProductIds();

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($ids as $id) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getNewProductInstance($id);

                $t = microtime(true);
                $Product->updateCache();
                $Product->buildCache();
                Log::addDebug("update cache for product #" . $id . " | time: " . (microtime(true) - $t));
            } catch (Exception $Exception) {
                Log::writeException($Exception);

                Log::addWarning(
                    'cron :: updateProductCache() :: Could not update cache'
                    . ' for Product #' . $id . ' -> '
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
     *
     * @throws Exception
     */
    public static function generateCacheImagesOfProducts(): void
    {
        $ids = Products::getProductIds();
        $count = count($ids);
        $current = 0;

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($ids as $id) {
            QUI::getEvents()->fireEvent('generateCacheImagesOfProductsBegin', [$id, $current, $count]);

            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getNewProductInstance($id);

                if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
                    $current++;
                    continue;
                }

                $Image = $Product->getImage();

                $Image->createCache();

                $Image->createSizeCache(400); // product gallery
                $Image->createSizeCache(500); // product slider
                $Image->createSizeCache(100, 200); // product gallery. preview
            } catch (\Exception $Exception) {
                Log::addNotice($Exception->getMessage(), [
                    'stack' => $Exception->getTraceAsString(),
                    'productId' => $id,
                    'cron' => 'generateCacheImagesOfProducts'
                ]);
            }

            QUI::getEvents()->fireEvent('generateCacheImagesOfProductsEnd', [$id, $current, $count]);

            $current++;
        }

        // reset time limit
        set_time_limit(ini_get('max_execution_time'));
    }
}
