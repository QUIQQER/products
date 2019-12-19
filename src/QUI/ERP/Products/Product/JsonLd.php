<?php

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Interfaces\ProductInterface;
use QUI\ERP\Products\Product\Types\VariantParent;

/**
 * Class JsonLd
 *
 * @package QUI\ERP\Products\Product
 *
 * @todo ProductVariant
 * @todo consider stock
 * @todo consider offer price date -> $offers["priceValidUntil"]
 */
class JsonLd
{
    /**
     * Return the complete JSON LD with script tag of a product
     *
     * @param ProductInterface $Product
     * @param null $Locale
     *
     * @return string
     */
    public static function getJsonLd(
        ProductInterface $Product,
        $Locale = null
    ) {
        $json = self::parse($Product, $Locale);

        $html = '<script type="application/ld+json">';
        $html .= \json_encode($json);
        $html .= '</script>';

        return $html;
    }

    /**
     * Parse a product to JSON LD representation
     *
     * @param ProductInterface $Product
     * @param null|QUI\Locale $Locale - optional
     *
     * @return array
     */
    public static function parse(
        ProductInterface $Product,
        $Locale = null
    ) {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $json = [
            "@context"    => "https://schema.org/",
            "@type"       => "Product",
            "name"        => $Product->getTitle($Locale),
            "description" => $Product->getDescription($Locale)
        ];

        $json = \array_merge($json, self::getSKU($Product));
        $json = \array_merge($json, self::getImages($Product));
        $json = \array_merge($json, self::getOffer($Product, $Locale));

        return $json;
    }

    /**
     * @param ProductInterface $Product
     * @return array
     */
    protected static function getSKU(ProductInterface $Product)
    {
        return [
            'sku' => $Product->getFieldValue(
                QUI\ERP\Products\Handler\Fields::FIELD_PRODUCT_NO
            )
        ];
    }

    /**
     * Return the image array
     *
     * @param ProductInterface $Product
     * @return array|QUI\Projects\Media\Image[]
     */
    protected static function getImages(ProductInterface $Product)
    {
        // images
        $images = [];

        try {
            $Image = $Product->getImage();

            if ($Image) {
                $images[] = $Image->getSizeCacheUrl();
            }
        } catch (QUI\Exception $Exception) {
            // nothing
        }

        $images = $Product->getImages();

        foreach ($images as $Image) {
            try {
                $images[] = $Image->getSizeCacheUrl();
            } catch (QUI\Exception $Exception) {
                // nothing
            }
        }

        $images = \array_unique($images);

        if (!\count($images)) {
            return [];
        }

        return [
            'image' => $images
        ];
    }

    /**
     * @param ProductInterface $Product
     * @param QUI\Locale $Locale
     *
     * @return array
     */
    protected static function getOffer(ProductInterface $Product, $Locale = null)
    {
        $Formatter = new \NumberFormatter('en_EN', \NumberFormatter::CURRENCY);
        $Formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        // offers
        $Price  = $Product->getPrice();
        $offers = self::getOfferEntry($Product, $Formatter);

        // offer price
        // @todo $offers["priceValidUntil"]
        if ($Product->hasOfferPrice()) {
        }

        // variant parent
        if (!($Product instanceof VariantParent)) {
            return [
                'offers' => $offers
            ];
        }


        /**
         * VARIANT PARENT
         */
        $offers = [
            "@type"         => "AggregateOffer",
            "priceCurrency" => $Price->getCurrency()->getCode(),
            "availability"  => "InStock"
        ];

        $offers = \array_merge(
            $offers,
            self::getMaxMin($Product, $Formatter)
        );

        if (isset($prices['highPrice']) || isset($prices['lowPrice'])) {
            unset($offers['price']);
        }


        // list variants
        $count    = 0;
        $models   = [];
        $variants = $Product->getVariants();

        foreach ($variants as $Variant) {
            if (!$Variant->isActive()) {
                continue;
            }

            $model = [
                "@type"       => "ProductModel",
                "name"        => $Variant->getTitle($Locale),
                "description" => $Variant->getDescription($Locale),
                "offers"      => self::getOfferEntry($Variant, $Formatter)
            ];

            $model = \array_merge($model, self::getImages($Variant));

            $models[] = $model;
            $count++;
        }

        $offers['offerCount'] = $count;

        return [
            'offers' => $offers,
            'model'  => $models
        ];
    }

    /**
     * @param ProductInterface $Product
     * @param \NumberFormatter $Formatter
     *
     * @return array
     */
    protected static function getMaxMin(
        ProductInterface $Product,
        \NumberFormatter $Formatter
    ) {
        $MaxPrice = $Product->getMaximumPrice();
        $MinPrice = $Product->getMinimumPrice();

        $offers = [];

        if ($MinPrice && $MinPrice->getValue()) {
            $offers['lowPrice'] = $Formatter->format($MinPrice->getValue());
        }

        if ($MaxPrice && $MaxPrice->getValue()) {
            $offers['highPrice'] = $Formatter->format($MaxPrice->getValue());
        }

        return $offers;
    }

    /**
     * @param ProductInterface $Product
     * @param \NumberFormatter $Formatter
     * @return array
     */
    protected static function getOfferEntry(
        ProductInterface $Product,
        \NumberFormatter $Formatter
    ) {
        $offerEntry = [
            "@type"         => "Offer",
            "price"         => $Formatter->format($Product->getPrice()->getValue()),
            "priceCurrency" => $Product->getPrice()->getCurrency()->getCode(),
            'availability'  => 'InStock' // @todo consider stock
        ];

        $offerEntry = \array_merge(
            $offerEntry,
            self::getMaxMin($Product, $Formatter)
        );

        if (isset($offerEntry['highPrice']) || isset($offerEntry['lowPrice'])) {
            unset($offerEntry['price']);

            $offerEntry['@type'] = 'AggregateOffer';
        }

        if ($offerEntry['highPrice'] === $offerEntry['lowPrice']) {
            unset($offerEntry['lowPrice']);
            unset($offerEntry['highPrice']);

            $offerEntry['price'] = $offerEntry['highPrice'];
        }

        return $offerEntry;
    }
}
