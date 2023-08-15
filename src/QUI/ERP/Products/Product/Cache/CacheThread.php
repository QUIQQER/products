<?php

namespace QUI\ERP\Products\Product\Cache;

/**
 * Class CacheThread
 *
 * @package QUI\ERP\Products\Product\Cache
 */
class CacheThread extends \Threaded
{
    private $controlCache;

    private $productId;

    /**
     * CacheThread constructor.
     *
     * @param $productId
     * @param $controlCache
     */
    public function __construct($productId, $controlCache = false)
    {
        $this->productId = $productId;
        $this->controlCache = $controlCache;
    }

    /**
     * run the thread
     */
    public function run()
    {
        ProductCache::create($this->productId, $this->controlCache);
    }
}
