<?php

declare(strict_types=1);

namespace QUI\ERP\Products\DemoData;

use QUI;
use QUI\ERP\DemoData\Contract\DemoDataCreatorInterface;
use QUI\ERP\DemoData\DTO\CreatedDemoData;
use QUI\ERP\DemoData\DTO\CreatedDemoDataCollection;
use QUI\ERP\DemoData\DTO\DemoDataCreationContext;
use QUI\ERP\DemoData\DTO\DemoDataReferenceCollection;
use QUI\ERP\DemoData\Exception\DemoDataException;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;

final class ProductDemoDataCreator implements DemoDataCreatorInterface
{
    private const PROVIDER_IDENTIFIER = 'quiqqer.products';
    private const ENTITY_TYPE = 'product';

    public function getDependencies(): array
    {
        return [];
    }

    public function createDemoData(DemoDataCreationContext $context): CreatedDemoDataCollection
    {
        $systemUser = QUI::getUsers()->getSystemUser();
        $createdDemoData = [];

        foreach (['Demo product Basic', 'Demo product Business', 'Demo product Premium'] as $index => $title) {
            $productNumber = $this->getAvailableProductNumber();
            $localizedTitle = [];

            foreach (QUI::availableLanguages() as $language) {
                $localizedTitle[$language] = $title;
            }

            $titleField = clone Fields::getField(Fields::FIELD_TITLE);
            $titleField->setValue($localizedTitle);
            $priceField = clone Fields::getField(Fields::FIELD_PRICE);
            $priceField->setValue(19.99 + ($index * 20));
            $productNumberField = clone Fields::getField(Fields::FIELD_PRODUCT_NO);
            $productNumberField->setValue($productNumber);

            $product = Products::createProduct([], [$titleField, $priceField, $productNumberField]);
            $product->save($systemUser);

            $createdDemoData[] = new CreatedDemoData(
                self::ENTITY_TYPE,
                (string)$product->getId(),
                'product_' . ($index + 1)
            );
        }

        return new CreatedDemoDataCollection($createdDemoData);
    }

    public function deleteDemoData(DemoDataReferenceCollection $demoData): void
    {
        foreach ($demoData->forProvider(self::PROVIDER_IDENTIFIER) as $reference) {
            if ($reference->entityType !== self::ENTITY_TYPE || !ctype_digit($reference->entityUuid)) {
                throw new DemoDataException('Product demo data reference has an invalid entity type or identifier.');
            }

            Products::deleteProduct((int)$reference->entityUuid);
        }
    }

    private function getAvailableProductNumber(): string
    {
        do {
            $productNumber = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));

            try {
                Products::getProductByProductNo($productNumber);
            } catch (\QUI\Exception) {
                return $productNumber;
            }
        } while (true);
    }
}
