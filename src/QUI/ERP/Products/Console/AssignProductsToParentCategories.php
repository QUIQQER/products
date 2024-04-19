<?php

namespace QUI\ERP\Products\Console;

use Exception;
use QUI;
use QUI\ERP\Products\Handler\Products;

use function array_map;
use function explode;

/**
 * Class AssignProductsToParentCategories
 *
 * Assign products to all parent categories of its assigned categories
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class AssignProductsToParentCategories extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:assign-parent-categories')
            ->setDescription('Assign products to all parent categories of its assigned categories')
            ->addArgument(
                'productIds',
                'Comma-separated list of product IDs that should be assigned to categories.',
                false,
                true
            );
    }

    /**
     * Execute the console tool
     */
    public function execute(): void
    {
        // Fetch productIds
        $productIds = $this->getArgument('productIds');

        if (!empty($productIds)) {
            $productIds = explode(',', $productIds);

            array_map(function ($v) {
                return (int)$v;
            }, $productIds);
        } else {
            $productIds = Products::getProductIds(); // All product IDs
        }

        $SystemUser = QUI::getUsers()->getSystemUser();

        foreach ($productIds as $productId) {
            $this->writeLn("Assigning categories to product #" . $productId . "...");

            try {
                $Product = Products::getProduct($productId);
                $this->assignCategoriesToProduct($Product);

                $Product->save($SystemUser);
            } catch (Exception $Exception) {
                $this->write(" ERROR! -> " . $Exception->getMessage());

                QUI\System\Log::writeException($Exception);
                continue;
            }

            $this->write(" OK!");
        }

        $this->writeLn("\n\nDone.\n");
    }

    /**
     * @param QUI\ERP\Products\Product\Product $Product
     * @return void
     * @throws QUI\Exception
     */
    protected function assignCategoriesToProduct(QUI\ERP\Products\Product\Product $Product): void
    {
        $assign = function (QUI\ERP\Products\Interfaces\CategoryInterface $Category) use ($Product, &$assign) {
            $Product->addCategory($Category);

            if ($Category->getParent()) {
                $assign($Category->getParent());
            }
        };

        /** @var QUI\ERP\Products\Category\Category $Category */
        foreach ($Product->getCategories() as $Category) {
            $assign($Category);
        }
    }
}
