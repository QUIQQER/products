<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Currency\Currency;
use QUI\ERP\Products\Utils\Calc;
use QUI\ERP\Products\Utils\PriceFactors;
use QUI\Exception;
use QUI\Interfaces\Users\User;

use function count;
use function explode;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function json_encode;
use function md5;
use function usort;

/**
 * Class ProductList
 *
 * @package QUI\ERP\Products\Product
 */
class ProductList
{
    /**
     * is the product list calculated?
     *
     * @var bool
     */
    protected bool $calculated = false;

    /**
     * @var int|float
     */
    protected mixed $sum;

    /**
     * @var ?QUI\Interfaces\Users\User
     */
    protected QUI\Interfaces\Users\User | null $User = null;

    /**
     * @var int|float
     */
    protected mixed $subSum;

    /**
     * @var int|float
     */
    protected mixed $grandSubSum;

    /**
     * @var int|float
     */
    protected mixed $nettoSum;

    /**
     * @var int|float
     */
    protected mixed $nettoSubSum;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected mixed $vatArray = [];

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array()
     */
    protected mixed $vatText;

    /**
     * Prüfen ob EU Vat für den Benutzer in Frage kommt
     * @var bool
     */
    protected bool $isEuVat = false;

    /**
     * Wird Brutto oder Netto gerechnet
     * @var bool
     */
    protected bool $isNetto = true;

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected ?QUI\ERP\Currency\Currency $Currency = null;

    /**
     * @var null|QUI\ERP\Order\AbstractOrder
     */
    protected ?QUI\ERP\Order\AbstractOrder $Order = null;

    /**
     * Currency information
     * @var array
     */
    protected mixed $currencyData = [
        'currency_sign' => '',
        'currency_code' => '',
        'user_currency' => '',
        'currency_rate' => '',
    ];

    /**
     * @var UniqueProduct[]
     */
    protected array $products = [];

    /**
     * Doublicate entries allowed?
     * Default = false
     * @var bool
     */
    public bool $duplicate = true;

    /**
     * PriceFactor List
     * @var ?QUI\ERP\Products\Utils\PriceFactors
     */
    protected ?QUI\ERP\Products\Utils\PriceFactors $PriceFactors = null;

    /**
     * @var bool
     */
    protected int | bool | null $hidePrice;

    /**
     * ProductList constructor.
     *
     * @param array $params - optional, list settings
     * @param User|null $User - optional, User for calculation
     */
    public function __construct(array $params = [], null | QUI\Interfaces\Users\User $User = null)
    {
        if (isset($params['duplicate'])) {
            $this->duplicate = (bool)$params['duplicate'];
        }

        if (isset($params['calculations'])) {
            $calc = $params['calculations'];

            $this->sum = $calc['sum'];
            $this->grandSubSum = $calc['grandSubSum'];
            $this->subSum = $calc['subSum'];
            $this->nettoSum = $calc['nettoSum'];
            $this->nettoSubSum = $calc['nettoSubSum'];
            $this->vatArray = $calc['vatArray'];
            $this->vatText = $calc['vatText'];
            $this->isEuVat = $calc['isEuVat'];
            $this->isNetto = $calc['isNetto'];
            $this->currencyData = $calc['currencyData'];

            $this->calculated = true;
        }

        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();
        $this->User = $User;
        $this->hidePrice = QUI\ERP\Products\Utils\Package::hidePrice();

        if ($this->Currency) {
            $this->PriceFactors->setCurrency($this->Currency);
        }
    }

    /**
     * Set the user for the list
     * User for calculation
     *
     * @param QUI\Interfaces\Users\User $User
     */
    public function setUser(null | QUI\Interfaces\Users\User $User): void
    {
        if (QUI::getUsers()->isUser($User)) {
            $this->User = $User;
        }
    }

    /**
     * Return the list user
     *
     * @return ?QUI\Interfaces\Users\User
     */
    public function getUser(): QUI\Interfaces\Users\User | null
    {
        return $this->User;
    }

    /**
     * Calculate the prices in the list
     *
     * @param QUI\ERP\Products\Utils\Calc|null $Calc - optional, calculation object
     * @return ProductList
     *
     * @throws QUI\Exception
     */
    public function calc(null | QUI\ERP\Products\Utils\Calc $Calc = null): ProductList
    {
        if ($this->calculated) {
            return $this;
        }

        if (!$Calc) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance();
            $Calc->setUser($this->User);
            $Calc->setLocale(QUI\ERP\Products\Handler\Products::getLocale());
        }

        $Calc->setCurrency($this->getCurrency());

        $Calc->calcProductList($this, function ($data) {
            $self = $this;

            $self->sum = $data['sum'];
            $self->subSum = $data['subSum'];
            $self->grandSubSum = $data['grandSubSum'];
            $self->nettoSum = $data['nettoSum'];
            $self->nettoSubSum = $data['nettoSubSum'];
            $self->vatArray = $data['vatArray'];
            $self->vatText = $data['vatText'];
            $self->isEuVat = $data['isEuVat'];
            $self->isNetto = $data['isNetto'];
            $self->currencyData = $data['currencyData'];
            $self->calculated = true;
        });

        return $this;
    }

    /**
     * Execute a recalculation
     *
     * @param QUI\ERP\Products\Utils\Calc|null $Calc - optional, calculation object
     * @return ProductList
     *
     * @throws QUI\Exception
     */
    public function recalculation(null | QUI\ERP\Products\Utils\Calc $Calc = null): ProductList
    {
        $this->calculated = false;

        foreach ($this->products as $Product) {
            $Product->resetCalculation();
        }

        return $this->calc($Calc);
    }

    /**
     * Alias for recalculation()
     *
     * @param Calc|null $Calc
     * @return ProductList
     * @throws Exception
     */
    public function recalculate(null | QUI\ERP\Products\Utils\Calc $Calc = null): ProductList
    {
        return $this->recalculation($Calc);
    }

    /**
     * Return the length of the list
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getProducts());
    }

    /**
     * Return the product count of the list
     * it includes the quantity of each product
     *
     * @return int
     */
    public function getQuantity(): int
    {
        $quantity = 0;
        $products = $this->getProducts();

        foreach ($products as $Products) {
            if (method_exists($Products, 'getQuantity')) {
                $quantity = $quantity + $Products->getQuantity();
            }
        }

        return $quantity;
    }

    /**
     * Return the products
     *
     * @return QUI\ERP\Products\Interfaces\ProductInterface[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * Return the price factors list (list of price indicators)
     *
     * @return PriceFactors|null
     */
    public function getPriceFactors(): ?QUI\ERP\Products\Utils\PriceFactors
    {
        return $this->PriceFactors;
    }

    /**
     * Add a product to the list
     *
     * @param QUI\ERP\Products\Interfaces\ProductInterface $Product
     *
     * @throws QUI\Exception
     * @throws QUI\ERP\Products\Product\Exception
     */
    public function addProduct(QUI\ERP\Products\Interfaces\ProductInterface $Product): void
    {
        // only UniqueProduct can be calculated
        if (!($Product instanceof UniqueProduct) && method_exists($Product, 'createUniqueProduct')) {
            $Product = $Product->createUniqueProduct($this->User);
        }

        if (!($Product instanceof UniqueProduct)) {
            return;
        }

        /* @var $Product UniqueProduct */
        if ($this->Currency) {
            $Product->convert($this->Currency);
        }

        if ($this->duplicate) {
            $this->products[] = $Product;

            return;
        }

        $fields = $Product->getFields();
        $hash = [];

        foreach ($fields as $Field) {
            $fieldId = $Field->getId();
            $fieldValue = $Field->getValue();

            if (is_array($fieldValue)) {
                $fieldValue = md5(json_encode($fieldValue));
            }

            if (!is_string($fieldValue) && !is_numeric($fieldValue)) {
                continue;
            }

            $hash[] = $fieldId . ':' . $fieldValue;
        }

        // sort fields
        usort($hash, function ($a, $b) {
            $aId = (int)explode(':', $a)[0];
            $bId = (int)explode(':', $b)[0];

            return $aId - $bId;
        });

        // generate hash
        $hash = ';' . implode(';', $hash) . ';';
        $hash = md5($hash);

        $this->products[$hash] = $Product;
    }

    /**
     * @throws Exception
     */
    public function removePos(int $pos): void
    {
        if ($this->duplicate && isset($this->products[$pos])) {
            unset($this->products[$pos]);
            $this->recalculation();
            return;
        }

        $key = 0;

        foreach ($this->products as $productId => $Product) {
            if ($key === $pos) {
                unset($this->products[$productId]);
                break;
            }
        }

        $this->recalculation();
    }

    /**
     * Clears the list
     */
    public function clear(): void
    {
        $this->calculated = false;
        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();
        $this->products = [];
    }

    /**
     * Return the products as array list
     *
     * @param null|QUI\Locale $Locale - optional
     * @return array
     *
     * @throws QUI\Exception
     */
    public function toArray(null | QUI\Locale $Locale = null): array
    {
        if ($Locale === null) {
            $Locale = $this->User->getLocale();
        }

        QUI\ERP\Products\Handler\Products::setLocale($Locale);

        $this->calc();
        $products = [];

        foreach ($this->products as $Product) {
            $attributes = $Product->getAttributes();
            $attributes['uuid'] = $Product->getUuid();

            $fields = $Product->getFields();

            $attributes['fields'] = [];

            foreach ($fields as $Field) {
                $attributes['fields'][] = $Field->getAttributes();
            }

            $products[] = $attributes;
        }

        // display data
        $Currency = $this->getCurrency();

        $calculations = [
            'sum' => $this->sum,
            'subSum' => $this->subSum,
            'grandSubSum' => $this->grandSubSum,
            'nettoSum' => $this->nettoSum,
            'nettoSubSum' => $this->nettoSubSum,
            'vatArray' => $this->vatArray,
            'vatText' => $this->vatText,
            'isEuVat' => $this->isEuVat,
            'isNetto' => $this->isNetto,
            'currencyData' => $this->currencyData
        ];

        $calculations['vatSum'] = QUI\ERP\Accounting\Calc::calculateTotalVatOfInvoice(
            $calculations['vatArray']
        );

        $calculations['display_subSum'] = $Currency->format($calculations['subSum']);
        $calculations['display_sum'] = $Currency->format($calculations['sum']);
        $calculations['display_vatSum'] = $Currency->format($calculations['vatSum']);

        return [
            'products' => $products,
            'sum' => $this->sum,
            'subSum' => $this->subSum,
            'grandSubSum' => $this->grandSubSum,
            'nettoSum' => $this->nettoSum,
            'nettoSubSum' => $this->nettoSubSum,
            'vatArray' => $this->vatArray,
            'vatText' => $this->vatText,
            'isEuVat' => $this->isEuVat,
            'isNetto' => $this->isNetto,
            'currencyData' => $this->currencyData,
            'calculations' => $calculations
        ];
    }

    /**
     * Return the products as json notation
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function toJSON(): string
    {
        return json_encode($this->toArray());
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
    public function isPriceHidden(): bool | int | null
    {
        return $this->hidePrice;
    }

    //endregion

    /**
     * Return the product list view for the frontend
     *
     * @param null|QUI\Locale $Locale
     * @return ProductListFrontendView|ProductListBackendView
     * @throws QUI\Exception
     */
    public function getView(null | QUI\Locale $Locale = null): ProductListBackendView | ProductListFrontendView
    {
        if (!$this->calculated) {
            $this->calc();
        }

        if (QUI::isBackend()) {
            return $this->getBackendView($Locale);
        }

        return $this->getFrontendView($Locale);
    }

    /**
     * Set the currency for the calculation
     * - Convert all product price fields
     *
     * @param QUI\ERP\Currency\Currency|null $Currency
     */
    public function setCurrency(null | QUI\ERP\Currency\Currency $Currency = null): void
    {
        if (!($Currency instanceof QUI\ERP\Currency\Currency)) {
            $Currency = QUI\ERP\Defaults::getCurrency();
        }

        if ($this->Currency && $this->Currency->getCode() === $Currency->getCode()) {
            return;
        }

        $this->Currency = $Currency;

        foreach ($this->products as $Product) {
            $Product->convert($Currency);
        }

        $this->PriceFactors->setCurrency($this->Currency);

        try {
            $this->recalculate();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * Return the currency
     *
     * @return Currency|null
     */
    public function getCurrency(): ?QUI\ERP\Currency\Currency
    {
        if ($this->Currency !== null) {
            return $this->Currency;
        }

        if (is_array($this->currencyData) && !empty($this->currencyData['currency_code'])) {
            try {
                $this->Currency = QUI\ERP\Currency\Handler::getCurrency(
                    $this->currencyData['currency_code']
                );

                return $this->Currency;
            } catch (QUI\Exception) {
            }
        }

        return QUI\ERP\Defaults::getCurrency();
    }

    /**
     * Return the product list view for the frontend
     *
     * @param null|QUI\Locale $Locale
     * @return ProductListFrontendView
     * @throws QUI\Exception
     */
    public function getFrontendView(null | QUI\Locale $Locale = null): ProductListFrontendView
    {
        return new ProductListFrontendView($this, $Locale);
    }

    /**
     * @param null|QUI\Locale $Locale
     * @return ProductListBackendView
     * @throws QUI\Exception
     */
    public function getBackendView(null | QUI\Locale $Locale = null): ProductListBackendView
    {
        return new ProductListBackendView($this, $Locale);
    }

    //region order

    /**
     * @param QUI\ERP\Order\AbstractOrder $Order
     */
    public function setOrder(QUI\ERP\Order\AbstractOrder $Order): void
    {
        $this->Order = $Order;
    }

    /**
     * @return QUI\ERP\Order\AbstractOrder|null
     */
    public function getOrder(): ?QUI\ERP\Order\AbstractOrder
    {
        return $this->Order;
    }

    //endregion
}
