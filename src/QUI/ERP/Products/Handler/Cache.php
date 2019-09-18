<?php

namespace QUI\ERP\Products\Handler;

/**
 * Class Cache
 * - Helper for caching ids / names
 *
 * @package QUI\ERP\Products\Handler
 */
class Cache
{
    /**
     * Cache name for a product
     *
     * @param $productId
     * @return string
     */
    public static function productCacheName($productId)
    {
        return 'quiqqer/product/product/'.$productId;
    }
}
