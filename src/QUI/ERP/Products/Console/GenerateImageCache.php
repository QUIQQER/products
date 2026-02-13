<?php

/**
 * This file contains QUI\ERP\Products\Console\GenerateImageCache
 */

namespace QUI\ERP\Products\Console;

use QUI;
use QUI\Database\Exception;

use function date;
use function mb_strlen;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * Console tool for HKL used patches
 */
class GenerateImageCache extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('products:generate-image-cache')
            ->setDescription(
                'Generate the primary image cache'
            );
    }

    /**
     * Execute the console tool
     * @throws Exception
     * @throws QUI\Exception
     */
    public function execute(): void
    {
        $self = $this;

        QUI::getEvents()->addEvent('onGenerateCacheImagesOfProductsBegin', function ($id, $i, $count) use ($self) {
            if ($i % 10 === 0) {
                $out = str_pad($i, mb_strlen($count), '0', STR_PAD_LEFT);
                $time = date('H:i:s');

                $self->writeLn('- ' . $time . ' :: ' . $out . ' of ' . $count);
            }
        });

        QUI\ERP\Products\Crons::generateCacheImagesOfProducts();
    }
}
