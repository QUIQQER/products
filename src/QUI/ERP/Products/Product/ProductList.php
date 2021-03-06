<?php

/**
 * This file contains QUI\ERP\Products\Product\Product
 */

namespace QUI\ERP\Products\Product;

use QUI;

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
    protected $calculated = false;

    /**
     * @var int|float|double
     */
    protected $sum;

    /**
     * @var QUI\Interfaces\Users\User
     */
    protected $User = null;

    /**
     * @var int|float|double
     */
    protected $subSum;

    /**
     * @var int|float|double
     */
    protected $nettoSum;

    /**
     * @var int|float|double
     */
    protected $nettoSubSum;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected $vatArray = [];

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array()
     */
    protected $vatText;

    /**
     * Prüfen ob EU Vat für den Benutzer in Frage kommt
     * @var
     */
    protected $isEuVat = false;

    /**
     * Wird Brutto oder Netto gerechnet
     * @var bool
     */
    protected $isNetto = true;

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected $Currency = null;

    /**
     * @var null
     */
    protected $Order = null;

    /**
     * Currency information
     * @var array
     */
    protected $currencyData = [
        'currency_sign' => '',
        'currency_code' => '',
        'user_currency' => '',
        'currency_rate' => '',
    ];

    /**
     * @var UniqueProduct[]
     */
    protected $products = [];

    /**
     * Doublicate entries allowed?
     * Default = false
     * @var bool
     */
    public $duplicate = true;

    /**
     * PriceFactor List
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected $PriceFactors = false;

    /**
     * @var bool
     */
    protected $hidePrice;

    /**
     * ProductList constructor.
     *
     * @param array $params - optional, list settings
     * @param QUI\Interfaces\Users\User|boolean $User - optional, User for calculation
     */
    public function __construct($params = [], $User = false)
    {
        if (isset($params['duplicate'])) {
            $this->duplicate = (boolean)$params['duplicate'];
        }

        if (isset($params['calculations'])) {
            $calc = $params['calculations'];

            $this->sum          = $calc['sum'];
            $this->subSum       = $calc['subSum'];
            $this->nettoSum     = $calc['nettoSum'];
            $this->nettoSubSum  = $calc['nettoSubSum'];
            $this->vatArray     = $calc['vatArray'];
            $this->vatText      = $calc['vatText'];
            $this->isEuVat      = $calc['isEuVat'];
            $this->isNetto      = $calc['isNetto'];
            $this->currencyData = $calc['currencyData'];

            $this->calculated = true;
        }

        if (!QUI::getUsers()->isUser($User)) {
            $User = QUI::getUserBySession();
        }

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();
        $this->User         = $User;
        $this->hidePrice    = QUI\ERP\Products\Utils\Package::hidePrice();
    }

    /**
     * Set the user for the list
     * User for calculation
     *
     * @param QUI\Interfaces\Users\User $User
     */
    public function setUser(QUI\Interfaces\Users\User $User)
    {
        if (QUI::getUsers()->isUser($User)) {
            $this->User = $User;
        }
    }

    /**
     * Return the list user
     *
     * @return QUI\Interfaces\Users\User|QUI\Users\User
     */
    public function getUser()
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
    public function calc($Calc = null)
    {
        if ($this->calculated) {
            return $this;
        }

        $self = $this;

        if (!$Calc) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance();
            $Calc->setUser($this->User);
        }

        $Calc->setCurrency($this->getCurrency());

        $Calc->calcProductList($this, function ($data) use ($self) {
            $self->sum          = $data['sum'];
            $self->subSum       = $data['subSum'];
            $self->nettoSum     = $data['nettoSum'];
            $self->nettoSubSum  = $data['nettoSubSum'];
            $self->vatArray     = $data['vatArray'];
            $self->vatText      = $data['vatText'];
            $self->isEuVat      = $data['isEuVat'];
            $self->isNetto      = $data['isNetto'];
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
    public function recalculation($Calc = null)
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
     * @param null $Calc
     * @return ProductList
     * @throws QUI\Exception
     */
    public function recalculate($Calc = null)
    {
        return $this->recalculation($Calc);
    }

    /**
     * Return the length of the list
     *
     * @return int
     */
    public function count()
    {
        return \count($this->getProducts());
    }

    /**
     * Return the product count of the list
     * it includes the quantity of each product
     *
     * @return int
     */
    public function getQuantity()
    {
        $quantity = 0;
        $products = $this->getProducts();

        foreach ($products as $Products) {
            /* @var $Products UniqueProduct */
            $quantity = $quantity + $Products->getQuantity();
        }

        return $quantity;
    }

    /**
     * Return the products
     *
     * @return QUI\ERP\Products\Interfaces\ProductInterface[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Return the price factors list (list of price indicators)
     *
     * @return QUI\ERP\Products\Utils\PriceFactors
     */
    public function getPriceFactors()
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
    public function addProduct(QUI\ERP\Products\Interfaces\ProductInterface $Product)
    {
        // only UniqueProduct can be calculated

        /* @var $Product QUI\ERP\Products\Product\Model */
        if (!($Product instanceof UniqueProduct)) {
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

        $this->products[$Product->getId()] = $Product;
    }

    /**
     * Clears the list
     */
    public function clear()
    {
        $this->calculated   = false;
        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();
        $this->products     = [];
    }

    /**
     * Return the products as array list
     *
     * @param null|QUI\Locale $Locale - optional
     * @return array
     *
     * @throws QUI\Exception
     */
    public function toArray($Locale = null)
    {
        if ($Locale === null) {
            $Locale = $this->User->getLocale();
        }

        QUI\ERP\Products\Handler\Products::setLocale($Locale);

        $this->calc();
        $products = [];

        /* @var $Product UniqueProduct */
        foreach ($this->products as $Product) {
            $attributes = $Product->getAttributes();
            $fields     = $Product->getFields();

            $attributes['fields'] = [];

            /* @var $Field QUI\ERP\Products\Interfaces\FieldInterface */
            foreach ($fields as $Field) {
                $attributes['fields'][] = $Field->getAttributes();
            }

            $products[] = $attributes;
        }

        // display data
        $Currency = $this->getCurrency();

        $calculations = [
            'sum'          => $this->sum,
            'subSum'       => $this->subSum,
            'nettoSum'     => $this->nettoSum,
            'nettoSubSum'  => $this->nettoSubSum,
            'vatArray'     => $this->vatArray,
            'vatText'      => $this->vatText,
            'isEuVat'      => $this->isEuVat,
            'isNetto'      => $this->isNetto,
            'currencyData' => $this->currencyData
        ];

        $calculations['vatSum'] = QUI\ERP\Accounting\Calc::calculateTotalVatOfInvoice(
            $calculations['vatArray']
        );

        $calculations['display_subSum'] = $Currency->format($calculations['subSum']);
        $calculations['display_sum']    = $Currency->format($calculations['sum']);
        $calculations['display_vatSum'] = $Currency->format($calculations['vatSum']);

        $result = [
            'products'     => $products,
            'sum'          => $this->sum,
            'subSum'       => $this->subSum,
            'nettoSum'     => $this->nettoSum,
            'nettoSubSum'  => $this->nettoSubSum,
            'vatArray'     => $this->vatArray,
            'vatText'      => $this->vatText,
            'isEuVat'      => $this->isEuVat,
            'isNetto'      => $this->isNetto,
            'currencyData' => $this->currencyData,
            'calculations' => $calculations
        ];

        return $result;
    }

    /**
     * Return the products as json notation
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function toJSON()
    {
        return \json_encode($this->toArray());
    }

    //region Price methods

    /**
     * Set the price to hidden
     */
    public function hidePrices()
    {
        $this->hidePrice = true;
    }

    /**
     * Set the price to visible
     */
    public function showPrices()
    {
        $this->hidePrice = false;
    }

    /**
     * Return if prices are hidden or not
     *
     * @return bool|int
     */
    public function isPriceHidden()
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
    public function getView($Locale = null)
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
    public function setCurrency($Currency = null)
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

        try {
            $this->recalculate();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * Return the currency
     *
     * @return QUI\ERP\Currency\Currency
     */
    public function getCurrency()
    {
        if ($this->Currency !== null) {
            return $this->Currency;
        }

        if (\is_array($this->currencyData) && !empty($this->currencyData['currency_code'])) {
            try {
                $this->Currency = QUI\ERP\Currency\Handler::getCurrency(
                    $this->currencyData['currency_code']
                );

                return $this->Currency;
            } catch (QUI\Exception $Exception) {
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
    public function getFrontendView($Locale = null)
    {
        return new ProductListFrontendView($this, $Locale);
    }

    /**
     * @param null|QUI\Locale $Locale
     * @return ProductListBackendView
     * @throws QUI\Exception
     */
    public function getBackendView($Locale = null)
    {
        return new ProductListBackendView($this, $Locale);
    }

    //region order

    /**
     * @param QUI\ERP\Order\AbstractOrder $Order
     */
    public function setOrder(QUI\ERP\Order\AbstractOrder $Order)
    {
        $this->Order = $Order;
    }

    /**
     * @return QUI\ERP\Order\AbstractOrder|null
     */
    public function getOrder()
    {
        return $this->Order;
    }

    //endregion
}
