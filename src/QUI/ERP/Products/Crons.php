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
     * Time for one product to update its cache
     */
    const PRODUCT_CACHE_UPDATE_TIME = 3;

    /**
     * Updates cache values for all products
     *
     * @throws QUI\Exception
     */
    public static function updateProductCache()
    {
        $products = Products::getProducts();

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($products as $Product) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product->updateCache();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    'cron :: updateProductCache() :: Could not update cache'
                    . ' for Product #' . $Product->getId() . ' -> '
                    . $Exception->getMessage()
                );
            }
        }
    }
}
