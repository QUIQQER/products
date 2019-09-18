<?php

namespace QUI\ERP\Products\Product\Cache;

use QUI;
use QUI\ERP\Products\Controls\Products\Product as ProductControl;
use QUI\ERP\Products\Handler\Products;

/**
 * Class ProductCache
 *
 * @package QUI\ERP\Products\Product\Cache
 */
class ProductCache
{
    /**
     * @param $productId
     * @param $createControlCache
     */
    public static function create($productId, $createControlCache = false)
    {
        try {
            $Product  = Products::getNewProductInstance($productId);
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
