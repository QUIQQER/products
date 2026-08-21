<?php

declare(strict_types=1);

namespace QUI\ERP\Products\DemoData;

use Doctrine\DBAL\Connection;
use QUI\Locale;
use QUI\ERP\DemoData\Contract\DemoDataCreatorInterface;
use QUI\ERP\DemoData\Contract\DemoDataProviderInterface;

final class ProductDemoDataProvider implements DemoDataProviderInterface
{
    public function getIdentifier(): string
    {
        return 'quiqqer.products';
    }

    public function getTitle(?Locale $locale = null): string
    {
        $locale ??= \QUI::getLocale();

        return (string)$locale->get('quiqqer/products', 'demo_data.provider.title');
    }

    public function getDemoDataCreator(Connection $connection): DemoDataCreatorInterface
    {
        return new ProductDemoDataCreator();
    }
}
