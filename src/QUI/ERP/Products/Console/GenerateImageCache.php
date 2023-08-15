<?php

/**
 * This file contains \Hklused\Machines\Patch
 */

namespace QUI\ERP\Products\Console;

use QUI;

/**
 * Console tool for HKL used patches
 *
 * @author www.pcsg.de (Patrick Müller)
 */
class GenerateImageCache extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
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
     */
    public function execute()
    {
        $self = $this;

        QUI::getEvents()->addEvent('onGenerateCacheImagesOfProductsBegin', function ($id, $i, $count) use ($self) {
            if ($i % 10 === 0) {
                $out = \str_pad($i, \mb_strlen($count), '0', \STR_PAD_LEFT);
                $time = \date('H:i:s');

                $self->writeLn('- ' . $time . ' :: ' . $out . ' of ' . $count);
            }
        });

        QUI\ERP\Products\Crons::generateCacheImagesOfProducts();
    }
}
