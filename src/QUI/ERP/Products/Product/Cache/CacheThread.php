<?php

namespace QUI\ERP\Products\Product\Cache;

/**
 * Class CacheThread
 *
 * @package QUI\ERP\Products\Product\Cache
 */
class CacheThread extends \Threaded
{
    private $productId;

    /**
     * CacheThread constructor.
     *
     * @param $productId
     */
    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    /**
     * run the thread
     */
    public function run()
    {
        ProductCache::create($this->productId);
    }
}
