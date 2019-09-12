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
     */
    public static function create($productId)
    {
        try {
            $Product = Products::getNewProductInstance($productId);

            if (!$Product->isActive()) {
                return;
            }

            $Product->setAttribute('viewType', 'frontend');
            $Product->getView()->getPrice();

            if ($Product instanceof QUI\ERP\Products\Product\Types\VariantParent) {
                $Product->getVariants();
                $Product->getImages();
            }

//            // control cache
//            $Control = new ProductControl([
//                'Product' => $Product
//            ]);
//
//            $Control->create();

            Products::cleanProductInstanceMemCache();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());
        }
    }
}
