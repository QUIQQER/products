<?php

namespace QUITests\ERP\Products;

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSqlitePlatform;
use Doctrine\DBAL\Schema\Index;

final class SqlitePlatform extends DoctrineSqlitePlatform
{
    public function getCreateIndexSQL(Index $index, string $table): string
    {
        return str_replace(
            'INDEX ',
            'INDEX IF NOT EXISTS ',
            parent::getCreateIndexSQL($index, $table)
        );
    }
}
