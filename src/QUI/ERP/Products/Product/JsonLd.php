<?php

namespace QUI\ERP\Products\Product;

use NumberFormatter;
use QUI;
use QUI\ERP\Products\Interfaces\ProductInterface;
use QUI\ERP\Products\Product\Types\VariantParent;

use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function json_encode;

/**
 * Class JsonLd
 *
 * @package QUI\ERP\Products\Product
 *
 * @todo ProductVariant
 * @todo consider stock
 * @todo consider offer price date -> $offers["priceValidUntil"]
 * @todo use cache -> consider price permissions
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
    ): string {
        $json = self::parse($Product, $Locale);

        $html = '<script type="application/ld+json">';
        $html .= json_encode($json);
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
        ?QUI\Locale $Locale = null
    ): array {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        $json = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $Product->getTitle($Locale),
            "description" => $Product->getDescription($Locale)
        ];

        $json = array_merge($json, self::getSKU($Product));
        $json = array_merge($json, self::getGTIN($Product));
        $json = array_merge($json, self::getBrand($Product));
        $json = array_merge($json, self::getImages($Product));

        return array_merge($json, self::getOffer($Product, $Locale));
    }

    /**
     * @param ProductInterface $Product
     * @return array
     */
    protected static function getSKU(ProductInterface $Product): array
    {
        return [
            'sku' => $Product->getFieldValue(
                QUI\ERP\Products\Handler\Fields::FIELD_PRODUCT_NO
            )
        ];
    }

    /**
     * @param ProductInterface $Product
     * @return array
     */
    protected static function getGTIN(ProductInterface $Product): array
    {
        return [
            'gtin' => $Product->getFieldValue(
                QUI\ERP\Products\Handler\Fields::FIELD_EAN
            )
        ];
    }

    /**
     * @param ProductInterface $Product
     * @return array
     */
    protected static function getBrand(ProductInterface $Product): array
    {
        $brandEntries = $Product->getFieldValue(
            QUI\ERP\Products\Handler\Fields::FIELD_MANUFACTURER
        );

        if (empty($brandEntries)) {
            return [];
        }

        $uid = $brandEntries[0];

        try {
            $User = QUI::getUsers()->get($uid);
            $Address = $User->getStandardAddress();

            $brand = $Address->getAttribute('company');

            if (empty($brand)) {
                $brand = $Address->getName();
            }

            if (empty($brand)) {
                $brand = $User->getName();
            }

            return [
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $brand
                ]
            ];
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        return [];
    }

    /**
     * Return the image array
     *
     * @param ProductInterface $Product
     * @return array|QUI\Projects\Media\Image[]
     */
    protected static function getImages(ProductInterface $Product): array
    {
        // images
        $images = [];

        try {
            $Image = $Product->getImage();

            if ($Image) {
                $url = $Image->getSizeCacheUrl();

                if (!empty($url)) {
                    $images[] = $url;
                }
            }
        } catch (QUI\Exception) {
            // nothing
        }

        // only show the main product image
        try {
            if (QUI::getPackage('quiqqer/products')->getConfig()->get('products', 'onlyProductImageAtJsonLd')) {
                return [
                    'image' => $images
                ];
            }
        } catch (\QUI\Exception) {
        }


        $productImages = $Product->getImages();

        foreach ($productImages as $Image) {
            try {
                $url = $Image->getSizeCacheUrl();

                if (!empty($url)) {
                    $images[] = $url;
                }
            } catch (QUI\Exception) {
                // nothing
            }
        }

        $images = array_unique($images);
        $images = array_values($images); // because of json array format

        if (!count($images)) {
            return [];
        }

        return [
            'image' => $images
        ];
    }

    /**
     * @param ProductInterface $Product
     * @param QUI\Locale|null $Locale
     *
     * @return array
     */
    protected static function getOffer(ProductInterface $Product, ?QUI\Locale $Locale = null): array
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return [];
        }

        $Formatter = new NumberFormatter('en_EN', NumberFormatter::CURRENCY);
        $Formatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '');

        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        // offers
        $Price = $Product->getPrice();
        $offers = self::getOfferEntry($Product, $Formatter);

        // offer price
        // @todo $offers["priceValidUntil"]
        if ($Product->hasOfferPrice()) {
        }

        // variant parent
        if (!($Product instanceof VariantParent)) {
            if (!isset($offers['lowPrice']) && isset($offers['price'])) {
                $offers['lowPrice'] = $offers['price'];
            }

            return [
                'offers' => $offers
            ];
        }


        /**
         * VARIANT PARENT
         */
        $offers = [
            "@type" => "AggregateOffer",
            "priceCurrency" => $Price->getCurrency()->getCode(),
            "availability" => "InStock"
        ];

        $offers = array_merge(
            $offers,
            self::getMaxMin($Product, $Formatter)
        );

        if (isset($offers['highPrice']) || isset($offers['lowPrice'])) {
            unset($offers['price']);
        } elseif (isset($offers['price'])) {
            $offers['lowPrice'] = $offers['price'];
        }

        // list variants
        $count = 0;
        $models = [];
        $variants = $Product->getVariants();

        foreach ($variants as $Variant) {
            if (!$Variant->isActive()) {
                continue;
            }

            $model = [
                "@type" => "ProductModel",
                "name" => $Variant->getTitle($Locale),
                "description" => $Variant->getDescription($Locale),
                "offers" => self::getOfferEntry($Variant, $Formatter)
            ];

            $model = array_merge($model, self::getImages($Variant));

            $models[] = $model;
            $count++;
        }

        $offers['offerCount'] = $count;

        return [
            'offers' => $offers,
            'model' => $models
        ];
    }

    /**
     * @param ProductInterface $Product
     * @param NumberFormatter $Formatter
     *
     * @return array
     */
    protected static function getMaxMin(
        ProductInterface $Product,
        NumberFormatter $Formatter
    ): array {
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
     * @param NumberFormatter $Formatter
     * @return array
     */
    protected static function getOfferEntry(
        ProductInterface $Product,
        NumberFormatter $Formatter
    ): array {
        $User = QUI::getUsers()->getUserBySession();
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($User);
        $price = $Product->getPrice()->getValue();

        if (!QUI\ERP\Utils\User::isNettoUser($User)) {
            try {
                $price = $Calc->getPrice($price);
            } catch (QUI\Exception) {
            }
        }


        $offerEntry = [
            "@type" => "Offer",
            "price" => $Formatter->format($price),
            "priceCurrency" => $Product->getPrice()->getCurrency()->getCode(),
            'availability' => 'InStock' // @todo consider stock
        ];

        $maxMin = self::getMaxMin($Product, $Formatter);

        if (!empty($maxMin)) {
            $offerEntry = array_merge($offerEntry, $maxMin);
        }

        if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
            if (isset($offerEntry['highPrice'])) {
                unset($offerEntry['highPrice']);
            }

            $offerEntry['lowPrice'] = $Formatter->format($price);

            //if (isset($offerEntry['lowPrice'])) {
            //    unset($offerEntry['lowPrice']);
            //}

            return $offerEntry;
        }


        if (isset($offerEntry['highPrice']) || isset($offerEntry['lowPrice'])) {
            unset($offerEntry['price']);

            $offerEntry['@type'] = 'AggregateOffer';
        }

        if (
            isset($offerEntry['highPrice'])
            && isset($offerEntry['lowPrice'])
            && $offerEntry['highPrice'] === $offerEntry['lowPrice']
        ) {
            $offerEntry['price'] = $offerEntry['highPrice'];

            unset($offerEntry['lowPrice']);
            unset($offerEntry['highPrice']);
        }


        return $offerEntry;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call(string $name, array $arguments)
    {
        // TODO: Implement __call() method.
    }
}
