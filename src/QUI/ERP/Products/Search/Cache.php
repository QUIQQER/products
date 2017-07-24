<?php

/**
 * This file contains QUI\ERP\Products\Search\Cache
 */

namespace QUI\ERP\Products\Search;

use QUI;
use Stash;
use QUI\ERP\Products\Utils\Package as PackageUtils;

/**
 * Class Search
 *
 * Cache class for product search
 *
 * @package QUI\ERP\Products\Search
 */
class Cache extends QUI\QDOM
{
    /**
     * Cache stash for search cache
     *
     * @var Stash\Pool
     */
    protected static $Stash = null;

    /**
     * Set data to product search cache
     *
     * @param string $name
     * @param mixed $data
     * @param int|\DateTime|null $time -> sekunden oder datetime
     *
     * @return Stash\Interfaces\ItemInterface
     */
    public static function set($name, $data, $time = null)
    {
        return self::getStashItem($name)->set($data, $time);
    }

    /**
     * Get data from product search cache
     *
     * @param string $name
     * @return string|array|object|boolean
     * @throws QUI\Cache\Exception
     */
    public static function get($name)
    {
        try {
            $Item   = self::getStashItem($name);
            $data   = $Item->get();
            $isMiss = $Item->isMiss();
        } catch (\Exception $Exception) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        if ($isMiss) {
            throw new QUI\Cache\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.cache.manager.not.exist'
                ),
                404
            );
        }

        return $data;
    }

    /**
     * Empty cache
     *
     * @param string|boolean $key (optional) - if no key given, cash is cleared completely
     */
    public static function clear($key = null)
    {
        self::getStashItem($key)->clear();
    }

    /**
     * Return a specific cache item
     *
     * @param string $key (optional) - cache name / cache key
     * @return Stash\Interfaces\ItemInterface
     */
    protected static function getStashItem($key = null)
    {
        if (is_null($key)) {
            $key = md5(__FILE__) . '/products/';
        } else {
            $key = md5(__FILE__) . '/products/' . $key;
        }

        return self::getStash()->getItem($key);
    }

    /**
     * Get product cache stash
     *
     * @return Stash\Pool
     * @throws QUI\Exception
     */
    protected static function getStash()
    {
        if (!is_null(self::$Stash)) {
            return self::$Stash;
        }

        $cacheDir = self::getCacheDir();

        try {
            $handlers[] = new Stash\Driver\FileSystem(array(
                'path' => $cacheDir
            ));

            $Handler = new Stash\Driver\Composite(array(
                'drivers' => $handlers
            ));

            $Stash = new Stash\Pool($Handler);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new Exception(array(
                'quiqqer/products',
                'exception.searchcache.initialize.error',
                array(
                    'error' => $Exception->getMessage()
                )
            ));
        }

        self::$Stash = $Stash;

        return self::$Stash;
    }

    /**
     * Get base cache dir
     *
     * @return string
     */
    protected static function getCacheDir()
    {
        $cacheDir = PackageUtils::getVarDir() . 'cache/products/search/';

        if (!file_exists($cacheDir)
            || !is_dir($cacheDir)
        ) {
            QUI\Utils\System\File::mkdir($cacheDir);
        }

        return $cacheDir;
    }
}
