<?php

namespace QUI\ERP\Products\Console;

use Exception;
use QUI;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

/**
 * Console tool for updating product prices with multipliers
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class UpdatePrices extends QUI\System\Console\Tool
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName('products:update-prices')
            ->setDescription(
                'Updates product prices (with multipliers).'
            );

        $this->addArgument(
            'activeOnly',
            'Only update prices of active products.',
            false,
            true
        );

        $this->addArgument(
            'categoryId',
            'Only update prices of products in the given category.',
            false,
            true
        );
    }

    /**
     * Execute the console tool
     */
    public function execute(): void
    {
        $this->updateProductPrices(!empty($this->getArgument('activeOnly')), $this->getArgument('categoryId'));
    }

    /**
     * Update all product prices of products that have relevant price fields.
     *
     * A price field is relevant if a multiplier is configured for it.
     *
     * @param bool $activeOnly (optional) - Only update active products
     * @param int|null $categoryId (optional) - Only update prices for products in given category
     * @return int - Number of updated products
     */
    public function updateProductPrices(bool $activeOnly = false, ?int $categoryId = null): int
    {
        $where = [];

        if (!empty($activeOnly)) {
            $where['active'] = 1;
        }

        if ($categoryId) {
            $where['categories'] = [
                'type' => '%LIKE%',
                'value' => '%,' . (int)$categoryId . ',%'
            ];
        }

        $productIds = Products::getProductIds([
            'where' => $where
        ]);

        $updateCount = 0;
        $priceFactors = Fields::getPriceFactorSettings();
        $SystemUser = QUI::getUsers()->getSystemUser();

        $priceFactorsCategories = [];
        $categories = QUI\ERP\Products\Handler\Categories::getCategories();

        foreach ($categories as $Category) {
            if (!method_exists($Category, 'getCustomDataEntry')) {
                continue;
            }

            $priceFieldFactors = $Category->getCustomDataEntry('priceFieldFactors');

            if (empty($priceFieldFactors)) {
                continue;
            }

            $priceFactorsCategories[$Category->getId()] = $priceFieldFactors;
        }

        foreach ($productIds as $productId) {
            try {
                $this->writeLn("Updating product #" . $productId . "...");
                $Product = Products::getProduct($productId);
                $updateProduct = false;

                foreach ($priceFactors as $priceFieldId => $settings) {
                    if (!$Product->hasField($priceFieldId) || !$Product->hasField($settings['sourceFieldId'])) {
                        continue;
                    }

                    $updateProduct = true;
                    break;
                }

                if (!$updateProduct) {
                    $productCategories = $Product->getCategories();

                    foreach ($priceFactorsCategories as $categoryId => $pf) {
                        if (isset($productCategories[$categoryId])) {
                            $updateProduct = true;
                        }
                    }
                }

                if ($updateProduct) {
                    $Product->setForcePriceFieldFactorUse(true);
                    $Product->update($SystemUser);

                    $this->writeLn(" -> SUCCESS!");
                    $updateCount++;
                } else {
                    $this->writeLn(" -> Product does not contain relevant price fields. Skipping product.");
                }
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->writeLn(" -> ERROR: " . $Exception->getMessage());
            }
        }

        return $updateCount;
    }
}
