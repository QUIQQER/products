<?php

namespace QUI\ERP\Products\Product\Cache;

use Exception;
use QUI;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Handler\Products;

use function is_null;

/**
 * Class ProductCache
 *
 * @package QUI\ERP\Products\Product\Cache
 */
class ProductCache
{
    protected static array $uniqueProductData = [];

    /**
     * @param array $uniqueProductData
     * @param string $cacheName
     * @return void
     */
    public static function writeUniqueProductData(array $uniqueProductData, string $cacheName): void
    {
        if (QUI\Utils\System::memUsageToHigh()) {
            self::clearUniqueProductDataCache();
        }

        self::$uniqueProductData[$cacheName] = $uniqueProductData;
    }

    /**
     * @param string $cacheName
     * @return array|null
     */
    public static function getUniqueProductData(string $cacheName): ?array
    {
        return !empty(self::$uniqueProductData[$cacheName]) ? self::$uniqueProductData[$cacheName] : null;
    }

    /**
     * @param string|null $cacheName (optional) - Only delete data with specific cache name
     * @return void
     */
    public static function clearUniqueProductDataCache(?string $cacheName = null): void
    {
        if (is_null($cacheName)) {
            self::$uniqueProductData = [];
        } else {
            self::$uniqueProductData[$cacheName] = null;
        }
    }

    /**
     * @param int $productId
     * @param bool $createControlCache
     * @throws Exception
     */
    public static function create(int $productId, bool $createControlCache = false): void
    {
        try {
            $Product = Products::getNewProductInstance($productId);
            $variants = [];

            if (!$Product->isActive()) {
                return;
            }

            $Product->setAttribute('viewType', 'frontend');
            $Product->getView()->getPrice();

            if ($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) {
                $variants = $Product->getVariants();

                $Product->getImages();
                $Product->availableActiveFieldHashes();
            }

            // control cache
            if ($createControlCache) {
                $Control = new ProductControl([
                    'Product' => $Product
                ]);

                $Control->create();
            }

            if (!($Product instanceof QUI\ERP\Products\Product\Types\VariantParent)) {
                return;
            }

            foreach ($variants as $Variant) {
                self::create($Variant->getId());
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());
        }
    }
}
