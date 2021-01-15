<?php

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;

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
            'Only update prices of active products',
            false,
            true
        );
    }

    /**
     * Execute the console tool
     */
    public function execute()
    {
        $this->updateProductPrices(!empty($this->getArgument('activeOnly')));
    }

    /**
     * Update all product prices of products that have relevant price fields.
     *
     * A price field is relevant if a mulitplier is configured for it.
     *
     * @param bool $activeOnly (optional) - Only update active products
     * @return int - Number of updated products
     */
    public function updateProductPrices(bool $activeOnly = false): int
    {
        $where = [];

        if (!empty($activeOnly)) {
            $where['active'] = 1;
        }

        $productIds = Products::getProductIds([
            'where' => $where
        ]);

        $updateCount  = 0;
        $priceFactors = Fields::getPriceFactorSettings();
        $SystemUser   = QUI::getUsers()->getSystemUser();

        foreach ($productIds as $productId) {
            try {
                $this->writeLn("Updating product #".$productId."...");
                $Product       = Products::getProduct($productId);
                $updateProduct = false;

                foreach ($priceFactors as $priceFieldId => $settings) {
                    if (!$Product->hasField($priceFieldId) || !$Product->hasField($settings['sourceFieldId'])) {
                        continue;
                    }

                    $updateProduct = true;
                    break;
                }

                if ($updateProduct) {
                    $Product->setForcePriceFieldFactorUse(true);
                    $Product->update($SystemUser);

                    $this->writeLn(" -> SUCCESS!");
                    $updateCount++;
                } else {
                    $this->writeLn(" -> Product does not contain relevant price fields. Skipping product.");
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->writeLn(" -> ERROR: ".$Exception->getMessage());
            }
        }

        return $updateCount;
    }
}
