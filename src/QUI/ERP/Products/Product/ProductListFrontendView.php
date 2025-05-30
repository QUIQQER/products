<?php

/**
 * This file contains QUI\ERP\Products\Product\ProductListFrontendView
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Products\Field\View;

use function dirname;
use function file_get_contents;
use function is_a;
use function json_encode;

/**
 * Class ProductListView
 * FrontendView for a product list
 *
 * @package QUI\ERP\Products\Product
 */
class ProductListFrontendView
{
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var ?ProductList
     */
    protected ?ProductList $ProductList = null;

    /**
     * @var bool
     */
    protected int|bool|null $hidePrice;

    /**
     * @var null|QUI\Locale
     */
    protected ?QUI\Locale $Locale = null;

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected ?QUI\ERP\Currency\Currency $Currency = null;

    /**
     * ProductListView constructor.
     *
     * @param ProductList $ProductList
     * @param null|QUI\Locale $Locale
     * @throws QUI\Exception
     */
    public function __construct(ProductList $ProductList, null | QUI\Locale $Locale = null)
    {
        $this->ProductList = $ProductList;
        $this->Currency = $ProductList->getCurrency();
        $this->hidePrice = $ProductList->isPriceHidden();
        $this->Locale = $Locale;

        $this->parse();
    }

    /**
     * Parse the list to an array
     * Set the internal data
     *
     * @throws QUI\Exception
     */
    protected function parse(): void
    {
        $Locale = $this->Locale;

        if ($Locale === null) {
            $Locale = $this->ProductList->getUser()->getLocale();
        }

        $list = $this->ProductList->toArray($Locale);
        $products = $this->ProductList->getProducts();

        // currency stuff
        $this->Currency->setLocale($Locale);

        $productList = [];
        $hidePrice = $this->hidePrice;

        foreach ($products as $Product) {
            $attributes = $Product->getAttributes();
            $fields = $Product->getFields();
            $PriceFactors = new QUI\ERP\Products\Utils\PriceFactor();

            if (method_exists($Product, 'getPriceFactors')) {
                $PriceFactors = $Product->getPriceFactors();
            }

            $product = [
                'productNo' => '',
                'fields' => [],
                'attributeFields' => [],
                'groupFields' => [],
                'vatArray' => [],
                'hasOfferPrice' => $Product->hasOfferPrice(),
                'originalPrice' => $this->formatPrice($Product->getOriginalPrice()->getValue())
            ];

            foreach ($fields as $Field) {
                if (!$Field->isPublic()) {
                    continue;
                }

                $product['fields'][] = $Field->getView();

                if ($Field->getType() === QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_LIST) {
                    $product['attributeFields'][] = $Field->getView();
                    continue;
                }

                if ($Field->getType() === QUI\ERP\Products\Handler\Fields::TYPE_ATTRIBUTE_GROUPS) {
                    $product['groupFields'][] = $Field->getView();
                }

                if ($Field->getId() === QUI\ERP\Products\Handler\Fields::FIELD_PRODUCT_NO) {
                    $product['productNo'] = $Field->getValue();
                }
            }

            $imageSrc = '';

            try {
                if ($Product->getImage()) {
                    $imageSrc = $Product->getImage()->getUrl();
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }

            // format
            $product['price'] = $hidePrice ? '' : $this->formatPrice($attributes['calculated_price']);
            $product['sum'] = $hidePrice ? '' : $this->formatPrice($attributes['calculated_sum']);
            $product['nettoSum'] = $hidePrice ? '' : $this->formatPrice($attributes['calculated_nettoSum']);
            $product['basisPrice'] = $hidePrice ? '' : $this->formatPrice($attributes['calculated_basisPrice']);
            $product['maximumQuantity'] = $Product->getMaximumQuantity();

            $product['id'] = $attributes['id'];
            $product['category'] = $attributes['category'];
            $product['title'] = $attributes['title'];
            $product['description'] = $attributes['description'];
            $product['image'] = $attributes['image'];
            $product['imageSrc'] = $imageSrc;
            $product['quantity'] = $attributes['quantity'];
            $product['displayPrice'] = true;
            $product['attributes'] = [];

            if (isset($attributes['displayPrice'])) {
                $product['displayPrice'] = $attributes['displayPrice'];
            }

            $calculatedSum = 0;
            $calculatedVat = 0;

            if (isset($attributes['calculated_vatArray']['sum'])) {
                $calculatedSum = $attributes['calculated_vatArray']['sum'];
            }

            if (isset($attributes['calculated_vatArray']['vat'])) {
                $calculatedVat = $attributes['calculated_vatArray']['vat'];
            }

            if ($calculatedSum == 0) {
                $calculatedSum = '';
            } else {
                $calculatedSum = $this->formatPrice($attributes['calculated_vatArray']['sum']);
            }

            $product['vatArray'][$calculatedVat]['sum'] = $hidePrice ? '' : $calculatedSum;


            /* @var QUI\ERP\Products\Utils\PriceFactor $Factor */
            foreach ($PriceFactors->sort() as $Factor) {
                if (!$Factor->isVisible()) {
                    continue;
                }

                if ($hidePrice) {
                    $product['attributes'][] = [
                        'title' => $Factor->getTitle(),
                        'value' => '',
                        'valueText' => $Factor->getValueText(),
                    ];
                    continue;
                }

                $product['attributes'][] = [
                    'title' => $Factor->getTitle(),
                    'value' => $Factor->getSumFormatted(),
                    'valueText' => $Factor->getValueText()
                ];
            }

            /** @var QUI\ERP\Products\Field\UniqueField $Field */
            foreach ($fields as $Field) {
                if (!$Field->isPublic()) {
                    continue;
                }

                if (!is_a($Field->getParentClass(), QUI\ERP\Products\Field\CustomInputFieldInterface::class, true)) {
                    continue;
                }

                $fieldAttributes = $Field->getAttributes();

                $product['attributes'][] = [
                    'title' => $Field->getTitle(),
                    'value' => $fieldAttributes['value'],
                    'valueText' => $fieldAttributes['valueText']
                ];
            }

            $productList[] = $product;
        }

        // result
        $result = [
            'attributes' => [],
            'vat' => [],
        ];

        foreach ($list['vatArray'] as $key => $entry) {
            $result['vat'][] = [
                'text' => $list['vatText'][$key],
                'value' => $hidePrice ? '' : $this->formatPrice($entry['sum']),
            ];
        }

        /* @var $Factor QUI\ERP\Products\Utils\PriceFactor */
        $product['grandTotalFactors'] = [];

        foreach ($this->ProductList->getPriceFactors()->sort() as $Factor) {
            if (!$Factor->isVisible()) {
                continue;
            }

            $key = 'attributes';

            if ($Factor->getCalculationBasis() === QUI\ERP\Accounting\Calc::CALCULATION_GRAND_TOTAL) {
                $key = 'grandTotalFactors';
            }

            if ($hidePrice) {
                $product[$key][] = [
                    'title' => $Factor->getTitle(),
                    'value' => '',
                    'valueText' => $Factor->getValueText(),
                ];
                continue;
            }

            $result[$key][] = [
                'title' => $Factor->getTitle(),
                'value' => $Factor->getSumFormatted(),
                'valueText' => $Factor->getValueText()
            ];
        }

        $result['products'] = $productList;
        $result['sum'] = $hidePrice ? '' : $this->formatPrice($list['sum']);
        $result['subSum'] = $hidePrice ? '' : $this->formatPrice($list['subSum']);
        $result['nettoSum'] = $hidePrice ? '' : $this->formatPrice($list['nettoSum']);
        $result['nettoSubSum'] = $hidePrice ? '' : $this->formatPrice($list['nettoSubSum']);
        $result['grandSubSum'] = $hidePrice ? '' : $this->formatPrice($list['grandSubSum']);

        $this->data = $result;
    }

    /**
     * Format the currency
     * - or recalculate it into another currency
     *
     * @param float|int $price
     * @return string
     */
    protected function formatPrice(float|int $price): string
    {
        return $this->Currency->format($price);
    }

    //region Price methods

    /**
     * Set the price to hidden
     */
    public function hidePrices(): void
    {
        $this->hidePrice = true;
    }

    /**
     * Set the price to visible
     */
    public function showPrices(): void
    {
        $this->hidePrice = false;
    }

    /**
     * Return if prices are hidden or not
     *
     * @return bool|int|null
     */
    public function isPriceHidden(): bool|int|null
    {
        return $this->hidePrice;
    }

    //endregion

    /**
     * Return the ProductListView as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = $this->data;

        /* @var $Field View */
        foreach ($data['products'] as $key => $product) {
            foreach ($product['fields'] as $fKey => $Field) {
                $data['products'][$key]['fields'][$fKey] = $Field->getAttributes();
            }
        }

        return $data;
    }

    /**
     * Return the list view as JSON
     *
     * @return string
     */
    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Return the quantity of products
     *
     * @return int
     */
    public function count(): int
    {
        return $this->ProductList->count();
    }

    /**
     * Return the sum
     *
     * @return string
     */
    public function getSum(): string
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['sum'];
    }

    /**
     * Return the sub sum
     *
     * @return string
     */
    public function getSubSum(): string
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['subSum'];
    }

    /**
     * Return the netto sum
     *
     * @return string
     */
    public function getNettoSum(): string
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['nettoSum'];
    }

    /**
     * Return the netto sub sum
     *
     * @return string
     */
    public function getNettoSubSum(): string
    {
        if (QUI\ERP\Products\Utils\Package::hidePrice()) {
            return '';
        }

        return $this->data['nettoSubSum'];
    }

    /**
     * Return the products
     *
     * @return array
     */
    public function getProducts(): array
    {
        return $this->data['products'];
    }

    /**
     * Return the generated standard product listing
     *
     * @param bool $css - optional, with inline style, default = true
     * @return string
     */
    public function toHTML(bool $css = true): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $style = '';

        if ($css) {
            $style = '<style>';
            $style .= file_get_contents(dirname(__FILE__) . '/ProductListView.css');
            $style .= '</style>';
        }

        $Engine->assign([
            'this' => $this,
            'data' => $this->data,
            'style' => $style,
            'hidePrice' => $this->isPriceHidden(),
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ProductListView.html');
    }
}
