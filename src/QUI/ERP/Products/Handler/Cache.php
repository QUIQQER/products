<?php

namespace QUI\ERP\Products\Handler;

use QUI;

/**
 * Class Cache
 * - Helper for caching ids / names
 *
 * @package QUI\ERP\Products\Handler
 */
class Cache
{
    public static function getBasicCachePath()
    {
        return 'products/';
    }

    /**
     * Cache name for a product
     *
     * @param $productId
     * @return string
     */
    public static function getProductCachePath($productId)
    {
        return self::getBasicCachePath().'product/'.$productId;
    }

    /**
     * @param int $productId
     * @param array $params
     * @return string
     */
    public static function frontendProductCacheName($productId, $params = [])
    {
        $general = 'quiqqer/product/frontend/'.$productId.'/';

        if (!empty($params)) {
            $general .= \md5(\serialize($params));
        }

        return $general;
    }

    /**
     * @param integer $productId
     */
    public static function clearProductFrontendCache($productId)
    {
        QUI\Cache\Manager::clear(
            self::getProductCachePath($productId)
        );
        
        QUI\Cache\Manager::clear(
            self::frontendProductCacheName($productId)
        );
    }
}
