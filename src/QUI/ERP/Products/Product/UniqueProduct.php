<?php

/**
 * This file contains QUI\ERP\Products\Product\UniqueProduct
 */

namespace QUI\ERP\Products\Product;

use QUI;
use QUI\ERP\Accounting\ArticleInterface;
use QUI\ERP\Accounting\Calc as ErpCalc;
use QUI\ERP\Products\Field\UniqueField;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Fields as FieldHandler;
use QUI\ERP\Products\Interfaces\CategoryInterface;
use QUI\ERP\Products\Interfaces\FieldInterface;
use QUI\ERP\Products\Interfaces\UniqueFieldInterface;
use QUI\ERP\Products\Utils\Calc;
use QUI\ERP\Products\Utils\PriceFactor;
use QUI\ExceptionStack;
use QUI\Locale;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Users\Exception;
use QUI\Users\User;

use function class_exists;
use function class_implements;
use function count;
use function explode;
use function floatval;
use function get_class;
use function is_a;
use function is_array;
use function is_numeric;
use function is_string;
use function is_subclass_of;
use function md5;
use function serialize;

/**
 * Class UniqueProduct
 *
 * @event onQuiqqerProductsPriceFactorsInit [
 *      QUI\ERP\Products\Utils\PriceFactors,
 *      QUI\ERP\Products\Interfaces\ProductInterface
 * ]
 * @todo view für unique product
 */
class UniqueProduct extends QUI\QDOM implements QUI\ERP\Products\Interfaces\ProductInterface
{
    /**
     * is the product list calculated?
     * @var bool
     */
    protected bool $calculated = false;

    /**
     * @var integer|string
     */
    protected string | int $id;

    /**
     * User-ID
     *
     * @var int|string
     */
    protected int | string $uid;

    /**
     * @var array
     */
    protected array $userData = [];

    /**
     * @var integer|float
     */
    protected int | float $quantity = 1;

    /**
     * @var array
     */
    protected array $categories = [];

    /**
     * @var null|QUI\ERP\Products\Interfaces\CategoryInterface
     */
    protected ?QUI\ERP\Products\Interfaces\CategoryInterface $Category = null;

    /**
     * @var QUI\ERP\Products\Interfaces\FieldInterface[]
     */
    protected array $fields = [];

    /**
     * Price factors
     *
     * @var QUI\ERP\Products\Utils\PriceFactors
     */
    protected QUI\ERP\Products\Utils\PriceFactors $PriceFactors;

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * calculated price
     * @var float|int
     */
    protected int | float $price;

    /**
     * calculated basis price - netto or brutto
     *
     * @var float|int
     */
    protected int | float $basisPrice;

    /**
     * calculated basis price - netto or brutto - no round
     *
     * @var float|int
     */
    protected int | float $nettoPriceNotRounded;

    /**
     * calculated basis price - netto or brutto - no round
     *
     * @var float|int
     */
    protected int | float $nettoSumNotRounded;

    /**
     * calculated sum
     *
     * @var float|int
     */
    protected int | float $sum;

    /**
     * @var float|int|null
     */
    protected float | int | null $minimumPrice = null;

    /**
     * @var float|int|null
     */
    protected float | int | null $maximumPrice = null;

    /**
     * @var bool|int|float
     */
    protected bool | int | float $maximumQuantity = false;

    /**
     * calculated netto sum
     * @var float|int
     */
    protected int | float $nettoSum;

    /**
     * Netto price
     *
     * @var float|int
     */
    protected int | float $nettoPrice;

    /**
     * key 19% value[sum] = sum value[text] = text value[display_sum] formatiert
     * @var array
     */
    protected array $vatArray = [];

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
     * Calculated factors
     * @var array
     */
    protected array $factors = [];

    /**
     * @var null|QUI\ERP\Currency\Currency
     */
    protected ?QUI\ERP\Currency\Currency $Currency = null;

    protected string $uuid;
    protected ?string $productSetParentUuid = null;

    /**
     * UniqueProduct constructor.
     *
     * @param integer $pid - Product ID
     * @param array $attributes - attributes
     *
     * @throws QUI\ERP\Products\Product\Exception
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function __construct(int $pid, array $attributes = [])
    {
        $this->id = $pid;
        $this->attributes = $attributes;
        $this->Currency = QUI\ERP\Defaults::getCurrency();

        // UUID
        if (empty($attributes['uuid'])) {
            $this->uuid = QUI\Utils\Uuid::get();
        } else {
            $this->uuid = $attributes['uuid'];
        }

        // Parent set UUID
        if (!empty($attributes['productSetParentUuid'])) {
            $this->productSetParentUuid = $attributes['productSetParentUuid'];
        }

        if (!isset($attributes['uid'])) {
            throw new QUI\ERP\Products\Product\Exception([
                'quiqqer/products',
                'exception.missing.uid.unique.product'
            ]);
        }

        if (isset($attributes['minimumPrice'])) {
            $this->minimumPrice = $attributes['minimumPrice'];
        }

        if (isset($attributes['maximumPrice'])) {
            $this->maximumPrice = $attributes['maximumPrice'];
        }

        if (isset($attributes['maximumQuantity'])) {
            $this->maximumQuantity = $attributes['maximumQuantity'];
        }

        if (isset($attributes['price_currency'])) {
            try {
                $this->Currency = QUI\ERP\Currency\Handler::getCurrency($attributes['price_currency']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        $this->uid = $attributes['uid'];


        // fields
        $this->parseFieldsFromAttributes($attributes);
        $this->parseCategoriesFromAttributes($attributes);

        // generate the price factors
        $fields = $this->getFields();

        $this->PriceFactors = new QUI\ERP\Products\Utils\PriceFactors();

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            if (!method_exists($Field, 'getParentClass')) {
                continue;
            }

            if (!is_a($Field->getParentClass(), QUI\ERP\Products\Field\CustomCalcFieldInterface::class, true)) {
                continue;
            }

            $FieldView = $Field->getView();

            $attributes = $Field->getAttributes();
            $factorAttributes = $attributes['custom_calc'];

            $factorAttributes['visible'] = $FieldView->hasViewPermission($this->getUser());

            $Factor = new PriceFactor($factorAttributes);
            $Factor->setTitle($Field->getTitle());
            $Factor->setCurrency($this->Currency->getCode());

            // Add price addition to valueText
            $fieldOptions = method_exists($Field, 'getOptions') ? $Field->getOptions() : [];

            if (
                !empty($fieldOptions['display_discounts']) &&
                (!QUI::isFrontend() || !QUI\ERP\Products\Utils\Package::hidePrice()) &&
                $Factor->getValue() > 0
            ) {
                if ((float)$Factor->getValue() >= 0) {
                    $priceAddition = '+';
                } else {
                    $priceAddition = '-';
                }

                switch ($Factor->getCalculation()) {
                    case QUI\ERP\Accounting\Calc::CALCULATION_PERCENTAGE:
                        $priceAddition .= $Factor->getValue() . '%';
                        break;

                    default:
                        $priceAddition .= $this->getCurrency()->format($Factor->getValue());
                        break;
                }

                $Factor->setValueText($Factor->getValueText() . ' (' . $priceAddition . ')');
            }

            $this->PriceFactors->add($Factor);
        }

        if (isset($attributes['quantity']) && (float)$attributes['quantity']) {
            $this->setQuantity((float)$attributes['quantity']);
        }

        QUI::getEvents()->fireEvent(
            'quiqqerProductsPriceFactorsInit',
            [$this->PriceFactors, $this]
        );

        $this->recalculation();
    }

    /**
     * Parse the field data
     *
     * @param array $attributes - product attributes
     */
    protected function parseFieldsFromAttributes(array $attributes = []): void
    {
        if (!isset($attributes['fields'])) {
            return;
        }

        $fields = $attributes['fields'];

        try {
            QUI::getEvents()->fireEvent(
                'quiqqerProductsUniqueProductParseFields',
                [$this, &$fields]
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        foreach ($fields as $field) {
            // quiqqer/products#391
            if (is_array($field) && isset($field['__class__']) && isset($field['id'])) {
                $fieldClass = $field['__class__'];

                if (is_subclass_of($fieldClass, QUI\ERP\Products\Field\Field::class)) {
                    $Field = new $fieldClass($field['id'], $field);
                    $Field->setProduct($this);
                    $FieldView = $Field->getView();

                    if ($FieldView instanceof QUI\ERP\Products\Field\View) {
                        $viewClass = get_class($FieldView);

                        $Instance = new $viewClass($field);
                        $Instance->setProduct($this);

                        $this->fields[] = $Instance;
                        continue;
                    }
                }
            }

            if (!Fields::isField($field) && !is_a($field, UniqueFieldInterface::class)) {
                $Instance = new UniqueField($field['id'], $field);
                $Instance->setProduct($this);

                $this->fields[] = $Instance;
                continue;
            }

            if ($field instanceof QUI\ERP\Products\Field\Field) {
                $field = $field->createUniqueField();
            }

            $field->setProduct($this);
            $this->fields[] = $field;
        }
    }

    /**
     * Parse the category data
     *
     * @param array $attributes
     */
    protected function parseCategoriesFromAttributes(array $attributes = []): void
    {
        if (!isset($attributes['categories'])) {
            return;
        }

        $list = [];
        $categories = explode(',', $attributes['categories']);

        foreach ($categories as $cid) {
            try {
                $list[] = Categories::getCategory($cid);
            } catch (QUI\Exception) {
            }
        }

        $this->categories = $list;
    }

    /**
     * @param array $attributes
     */
    protected function parseCategoryFromAttributes(array $attributes = []): void
    {
        if (!isset($attributes['category'])) {
            return;
        }

        try {
            $this->Category = QUI\ERP\Products\Handler\Categories::getCategory(
                $attributes['category']
            );
        } catch (QUI\Exception) {
        }
    }

    /**
     * Return the user for the unique product
     */
    public function getUser(): QUI\Interfaces\Users\User
    {
        try {
            return QUI::getUsers()->get($this->uid);
        } catch (QUI\Exception) {
            return QUI::getUsers()->getNobody();
        }
    }

    /**
     * Return the price factor list of the product
     *
     * @return QUI\ERP\Products\Utils\PriceFactors
     */
    public function getPriceFactors(): QUI\ERP\Products\Utils\PriceFactors
    {
        return $this->PriceFactors;
    }

    /**
     * Unique identifier
     *
     * @return string
     */
    public function getCacheIdentifier(): string
    {
        return md5(serialize($this->getAttributes()));
    }

    /**
     * Return the Product-ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Get the UUID of another UniqueProduct that acts as the parent of a "product set".
     *
     * @return string|null
     */
    public function getProductSetParentUuid(): ?string
    {
        return $this->productSetParentUuid;
    }

    /**
     * Set the UUID of another UniqueProduct that acts as the parent of a "product set".
     *
     * @param string|null $productSetParentUuid
     */
    public function setProductSetParentUuid(?string $productSetParentUuid): void
    {
        $this->productSetParentUuid = $productSetParentUuid;
    }

    /**
     * @return UniqueProduct|UniqueProductFrontendView
     *
     * @throws QUI\Exception
     */
    public function getView(): UniqueProduct | UniqueProductFrontendView | static
    {
        $this->calc();

        if (QUI::isBackend()) {
            return $this;
        }

        $attributes = $this->getAttributes();
        $attributes['uid'] = $this->getUser()->getUUID();
        $attributes['maximumQuantity'] = $this->getMaximumQuantity();

        return new UniqueProductFrontendView($this->id, $attributes);
    }

    //region calculation

    /**
     * Calculates
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function calc(null | QUI\ERP\Products\Utils\Calc $Calc = null): static
    {
        if ($this->calculated) {
            return $this;
        }

        if (!$Calc) {
            $Calc = QUI\ERP\Products\Utils\Calc::getInstance($this->getUser());
        }

        $Calc->getProductPrice($this, function ($data) {
            $self = $this;

            $self->price = $data['price'];
            $self->basisPrice = $data['basisPrice'];
            $self->sum = $data['sum'];
            $self->nettoPrice = $data['nettoPrice'];
            $self->nettoSum = $data['nettoSum'];
            $self->vatArray = $data['vatArray'];
            $self->isEuVat = $data['isEuVat'];
            $self->isNetto = $data['isNetto'];
            $self->factors = $data['factors'];
            $self->nettoPriceNotRounded = $data['nettoPriceNotRounded'];
            $self->nettoSumNotRounded = $data['nettoSumNotRounded'];
            $self->calculated = true;
        });

        return $this;
    }

    /**
     * @return void
     */
    public function resetCalculation(): void
    {
        $this->calculated = false;
    }

    /**
     * @param Calc|null $Calc
     *
     * @return UniqueProduct
     *
     * @throws Exception
     * @throws ExceptionStack
     * @throws QUI\Exception
     */
    public function recalculation(null | QUI\ERP\Products\Utils\Calc $Calc = null): UniqueProduct | static
    {
        QUI::getEvents()->fireEvent('quiqqerProductsUniqueProductRecalculation', [$this]);

        $this->resetCalculation();

        return $this->calc($Calc);
    }

    /**
     * Convert all prices to the new currency
     *
     * @param QUI\ERP\Currency\Currency $Currency
     *
     * @todo save original price
     */
    public function convert(QUI\ERP\Currency\Currency $Currency): void
    {
        if ($this->Currency->getCode() === $Currency->getCode()) {
            return;
        }

        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($this->getUser());

        foreach ($this->fields as $key => $Field) {
            if (!method_exists($Field, 'getType')) {
                continue;
            }

            if (
                $Field->getType() !== FieldHandler::TYPE_PRICE
                && $Field->getType() !== FieldHandler::TYPE_PRICE_BY_QUANTITY
                && $Field->getType() !== FieldHandler::TYPE_PRICE_BY_TIMEPERIOD
            ) {
                continue;
            }

            $value = $Field->getValue();

            if (empty($value)) {
                continue;
            }

            try {
                if (is_array($value) && !empty($value['price'])) {
                    $value['price'] = $this->Currency->convert($value['price'], $Currency);
                    $value['price'] = $Calc->round($value['price']);
                    $value['price'] = $Currency->amount($value['price']);
                } elseif (!is_array($value)) {
                    $value = $this->Currency->convert($value, $Currency);
                    $value = $Calc->round($value);
                    $value = $Currency->amount($value);
                }

                $OriginalField = Fields::getField($Field->getId());
                $OriginalField->setValue($value);
            } catch (QUI\Exception) {
                continue;
            }

            $this->fields[$key] = $OriginalField->createUniqueField();
        }

        $priceFactors = $this->getPriceFactors()->sort();

        foreach ($priceFactors as $PriceFactor) {
            if ($PriceFactor->getCalculation() === ErpCalc::CALCULATION_COMPLEMENT) {
                try {
                    $value = $PriceFactor->getValue();
                    $value = $this->Currency->convert($value, $Currency);
                    $value = $Calc->round($value);
                    $value = $Currency->amount($value);

                    $PriceFactor->setValue($value);
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        try {
            $this->recalculation();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * @return QUI\ERP\Currency\Currency|null
     */
    public function getCurrency(): ?QUI\ERP\Currency\Currency
    {
        return $this->Currency;
    }

    //endregion

    /**
     * Return the translated title
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getTitle(QUI\Locale | null $Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title = $this->getField(Fields::FIELD_TITLE);

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (is_string($values)) {
            return $values;
        }

        return $values[$current] ?? '';
    }

    /**
     * Return the translated description
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getDescription(QUI\Locale | null $Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current = $Locale->getCurrent();
        $Description = $this->getField(Fields::FIELD_SHORT_DESC);
        $values = $Description->getValue();

        if (is_string($values)) {
            return $values;
        }

        return $values[$current] ?? '';
    }

    /**
     * Return the translated content
     *
     * @param Locale|null $Locale
     * @return string
     */
    public function getContent(QUI\Locale | null $Locale = null): string
    {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $current = $Locale->getCurrent();
        $Title = $this->getField(Fields::FIELD_CONTENT);

        if (!$Title) {
            return '';
        }

        $values = $Title->getValue();

        if (is_string($values)) {
            return $values;
        }

        return $values[$current] ?? '';
    }

    /**
     * Return the image url
     *
     * @return QUI\Projects\Media\Image
     * @throws QUI\Exception
     */
    public function getImage(): QUI\Projects\Media\Image
    {
        $image = $this->getFieldValue(Fields::FIELD_IMAGE);

        try {
            return MediaUtils::getImageByUrl($image);
        } catch (QUI\Exception) {
        }

        try {
            $Folder = MediaUtils::getMediaItemByUrl(
                $this->getFieldValue(Fields::FIELD_FOLDER)
            );

            if (method_exists($Folder, 'getImages')) {
                $images = $Folder->getImages([
                    'limit' => 1
                ]);

                if (isset($images[0])) {
                    return $images[0];
                }
            }
        } catch (QUI\Exception) {
        }

        try {
            $Project = QUI::getRewrite()->getProject();

            if (!$Project) {
                $Project = QUI::getProjectManager()->getStandard();
            }

            $Media = $Project->getMedia();
            $Placeholder = $Media->getPlaceholderImage();

            if ($Placeholder instanceof QUI\Projects\Media\Image) {
                return $Placeholder;
            }
        } catch (QUI\Exception) {
        }

        throw new QUI\ERP\Products\Product\Exception([
            'quiqqer/products',
            'exception.product.no.image',
            [
                'productId' => $this->getId()
            ]
        ]);
    }

    /**
     * @return array|QUI\Projects\Media\Image[]
     */
    public function getImages(): array
    {
        try {
            $Folder = MediaUtils::getMediaItemByUrl(
                $this->getFieldValue(Fields::FIELD_FOLDER)
            );

            if ($Folder instanceof Folder) {
                return $Folder->getImages();
            }
        } catch (QUI\Exception) {
        }

        return [];
    }

    /**
     * Return the wanted field
     *
     * @param int $fieldId
     * @return UniqueFieldInterface|null
     */
    public function getField(int $fieldId): ?UniqueFieldInterface
    {
        $fields = $this->getFields();

        foreach ($fields as $Field) {
            if ($Field->getId() == $fieldId) {
                return $Field;
            }
        }

        return null;
    }

    /**
     * Return all fields
     *
     * @return UniqueFieldInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string|array $type
     * @return array
     */
    public function getFieldsByType(string | array $type): array
    {
        $fields = $this->getFields();
        $result = [];

        foreach ($fields as $Field) {
            if (!method_exists($Field, 'getType')) {
                continue;
            }

            if ($Field && $Field->getType() == $type) {
                $result[] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getPrice(): QUI\ERP\Money\Price
    {
        $this->calc();

        $Price = new QUI\ERP\Money\Price(
            $this->sum,
            QUI\ERP\Currency\Handler::getDefaultCurrency(),
            $this->getUser()
        );

        // wenn attribute listen existieren
        // dann muss der kleinste preis rausgefunden werden
        // d.h. bei attribute listen wird der kleinste preis ausgewählt
        $attributesLists = $this->getFieldsByType(Fields::TYPE_ATTRIBUTE_LIST);

        if (!count($attributesLists)) {
            return $Price;
        }

        // quiqqer/products#292
        if (
            $this->minimumPrice &&
            $this->maximumPrice &&
            $this->minimumPrice !== $this->maximumPrice
        ) {
            $Price->enableMinimalPrice();
        }
//
//        foreach ($attributesLists as $List) {
//            /* @var $List UniqueField */
//            if ($List->isRequired() && $List->getValue() === '') {
//                $Price->enableMinimalPrice();
//
//                return $Price;
//            }
//        }

        return $Price;
    }

    /**
     * Has the product an offer price
     *
     * @return bool
     */
    public function hasOfferPrice(): bool
    {
        $OfferPrice = $this->getField(Fields::FIELD_PRICE_OFFER);

        if (!$OfferPrice) {
            return false;
        }

        $value = $OfferPrice->getValue();

        if ($value === false) {
            return false;
        }

        return $value !== '';
    }

    /**
     * @return UniqueFieldInterface|bool|QUI\ERP\Money\Price
     */
    public function getOriginalPrice(): UniqueFieldInterface | bool | QUI\ERP\Money\Price
    {
        return $this->getCalculatedPrice(Fields::FIELD_PRICE, true);
    }

    /**
     * @param int $fieldId
     * @param bool $ignorePriceFactors - default false
     * @return FieldInterface|UniqueFieldInterface
     */
    public function getCalculatedPrice(
        int $fieldId,
        bool $ignorePriceFactors = false
    ): FieldInterface | UniqueFieldInterface {
        $Calc = QUI\ERP\Products\Utils\Calc::getInstance($this->getUser());
        $calculations = [];

        $Field = $this->getField($fieldId);

        try {
            $Calc->getProductPrice(
                $this,
                function ($calcResult) use (&$calculations) {
                    $calculations = $calcResult;
                },
                $this->getField($fieldId),
                $ignorePriceFactors
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return $Field;
        }

        $priceAttributes = $Field->getAttributes();
        $priceAttributes['value'] = $calculations['sum'];

        return new UniqueField(
            $Field->getId(),
            $priceAttributes
        );
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getMinimumPrice(): QUI\ERP\Money\Price
    {
        if ($this->minimumPrice !== null) {
            return new QUI\ERP\Money\Price(
                $this->minimumPrice,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return $this->getPrice();
    }

    /**
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     * @throws QUI\Users\Exception
     */
    public function getMaximumPrice(): QUI\ERP\Money\Price
    {
        if ($this->maximumPrice !== null) {
            return new QUI\ERP\Money\Price(
                $this->maximumPrice,
                QUI\ERP\Currency\Handler::getDefaultCurrency()
            );
        }

        return $this->getPrice();
    }

    /**
     * @return int|bool|float
     */
    public function getMaximumQuantity(): int | bool | float
    {
        return $this->maximumQuantity;
    }

    /**
     * Return a price object (single price)
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function getUnitPrice(): QUI\ERP\Money\Price
    {
        $this->calc();

        return new QUI\ERP\Money\Price(
            $this->nettoPrice,
            QUI\ERP\Currency\Handler::getDefaultCurrency()
        );
    }

    /**
     * Return the netto price of the product
     *
     * @return QUI\ERP\Money\Price
     *
     * @throws QUI\Exception
     */
    public function getNettoPrice(): QUI\ERP\Money\Price
    {
        return QUI\ERP\Products\Utils\Products::getPriceFieldForProduct($this, $this->getUser());
    }

    /**
     * Return the value of the wanted field
     *
     * @param int $fieldId
     * @return mixed
     */
    public function getFieldValue(int $fieldId): mixed
    {
        $Field = $this->getField($fieldId);

        if ($Field) {
            return $Field->getValue();
        }

        return false;
    }

    /**
     * Return all custom fields
     * - Custom fields are only fields that the customer fills out
     *
     * @return array
     */
    public function getCustomFields(): array
    {
        $result = [];

        foreach ($this->fields as $Field) {
            if (method_exists($Field, 'isCustomField') && $Field->isCustomField()) {
                $result[$Field->getId()] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return all public fields
     * custom fields are only fields that the customer fills out
     *
     * @return array
     */
    public function getPublicFields(): array
    {
        $result = [];

        foreach ($this->fields as $Field) {
            if ($Field->isPublic()) {
                $result[$Field->getId()] = $Field;
            }
        }

        return $result;
    }

    /**
     * Return the main category
     *
     * @return ?QUI\ERP\Products\Interfaces\CategoryInterface
     */
    public function getCategory(): ?QUI\ERP\Products\Interfaces\CategoryInterface
    {
        if ($this->Category) {
            return $this->Category;
        }
        if (!isset($this->attributes['category'])) {
            return $this->Category;
        }

        try {
            $this->Category = Categories::getCategory($this->attributes['category']);
        } catch (QUI\Exception) {
        }

        return $this->Category;
    }

    /**
     * Return the product categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }


    /**
     * Set the quantity of the product
     *
     * @param float|integer $quantity
     */
    public function setQuantity(float | int $quantity): void
    {
        if (!is_numeric($quantity)) {
            return;
        }

        $quantity = floatval($quantity);
        $max = $this->getMaximumQuantity();

        if ($quantity < 0) {
            $quantity = 0;
        }

        if ($max && $max < $quantity) {
            $quantity = $this->getMaximumQuantity();
        }

        $this->quantity = floatval($quantity);

        try {
            $this->recalculation();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }
    }

    /**
     * Return the quantity
     *
     * @reutrn integer|float
     */
    public function getQuantity(): float | int
    {
        return $this->quantity;
    }

    /**
     * Return the product attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();

        $attributes['title'] = $this->getTitle();
        $attributes['description'] = $this->getDescription();
        $attributes['quantity'] = $this->getQuantity();
        $attributes['id'] = $this->getId();
        $attributes['fields'] = $this->getFields();
        $attributes['uid'] = $this->uid;
        $attributes['image'] = '';
        $attributes['maximumQuantity'] = $this->getMaximumQuantity();

        $oldCalc = $this->calculated;

        $Price = $this->getOriginalPrice();

        $this->calculated = $oldCalc;

        $attributes['hasOfferPrice'] = $this->hasOfferPrice();

        if ($Price instanceof UniqueField) {
            $attributes['originalPrice'] = $Price->getValue();
        } elseif ($Price instanceof QUI\ERP\Money\Price) {
            $attributes['originalPrice'] = $Price->getPrice();
        } elseif ($Price instanceof UniqueFieldInterface && method_exists($Price, 'getPrice')) {
            $attributes['originalPrice'] = $Price->getPrice()->getPrice();
        }


        if ($this->getCategory()) {
            $attributes['category'] = $this->getCategory()->getId();
        } else {
            try {
                $attributes['category'] = Categories::getMainCategory()->getId();
            } catch (QUI\Exception) {
                $attributes['category'] = 0;
            }
        }

        // image
        try {
            $Image = $this->getImage();
        } catch (QUI\Exception) {
            $Image = null;
        }


        if ($Image) {
            $attributes['image'] = $Image->getUrl(true);
        }

        $attributes['calculated_price'] = $this->price;
        $attributes['calculated_sum'] = $this->sum;
        $attributes['calculated_nettoSum'] = $this->nettoSum;
        $attributes['calculated_isEuVat'] = $this->isEuVat;
        $attributes['calculated_isNetto'] = $this->isNetto;
        $attributes['calculated_vatArray'] = $this->vatArray;
        $attributes['calculated_factors'] = $this->factors;

        $attributes['calculated_basisPrice'] = $this->basisPrice;
        $attributes['calculated_nettoPriceNotRounded'] = $this->nettoPriceNotRounded;
        $attributes['calculated_nettoSumNotRounded'] = $this->nettoSumNotRounded;

        $attributes['user_data'] = $this->userData;

        if (isset($attributes['fieldData'])) {
            unset($attributes['fieldData']);
        }

        return $attributes;
    }

    /**
     * Alias for getAttributes()
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->getAttributes();

        try {
            $Price = $this->getPrice();

            $attributes['price_display'] = $Price->getDisplayPrice();
            $attributes['price_is_minimal'] = $Price->isMinimalPrice();
        } catch (QUI\Exception) {
            $attributes['price_display'] = false;
            $attributes['price_is_minimal'] = false;
        }

        $attributes['uuid'] = $this->uuid;
        $attributes['productSetParentUuid'] = $this->productSetParentUuid;

        return $attributes;
    }

    /**
     * Return the unique product as an ERP Article
     *
     * @param null|QUI\Locale $Locale
     * @param bool $fieldsAreChangeable - default = true
     *
     * @return QUI\ERP\Accounting\Article
     *
     * @throws QUI\Users\Exception
     * @throws QUI\Exception
     */
    public function toArticle(
        null | QUI\Locale $Locale = null,
        bool $fieldsAreChangeable = true
    ): QUI\ERP\Accounting\Article {
        if (!$Locale) {
            $Locale = QUI\ERP\Products\Handler\Products::getLocale();
        }

        $initialCalcStatus = $this->calculated;
        $gtin = $this->getFieldValue(QUI\ERP\Products\Handler\Fields::FIELD_EAN);

        $article = [
            'id' => $this->getId(),
            'uuid' => $this->getUuid(),
            'productSetParentUuid' => $this->getProductSetParentUuid(),
            'articleNo' => $this->getFieldValue(Fields::FIELD_PRODUCT_NO),
            'gtin' => $gtin,
            'title' => $this->getTitle($Locale),
            'description' => $this->getDescription($Locale),
            'unitPrice' => $this->getUnitPrice()->value(),
            'nettoPriceNotRounded' => $this->nettoPriceNotRounded,
            'nettoSumNotRounded' => $this->nettoSumNotRounded,
            'quantity' => $this->getQuantity(),
            'customFields' => $this->getCustomFieldsData(),
            'customData' => $this->getCustomData(),
            'displayPrice' => true
        ];

        if (!$initialCalcStatus) {
            $this->resetCalculation();
        }

        // quantity unit
        $SysField = QUI\ERP\Products\Handler\Fields::getField(Fields::FIELD_UNIT);
        $Field = $this->getField(Fields::FIELD_UNIT);

        if ($Field) {
            $value = $Field->getView()->getValue();

            if (empty($value)) {
                $value = [];
            }

            $value['title'] = method_exists($SysField, 'getTitleByValue') ?
                $SysField->getTitleByValue($Field->getValue()) : '';
            $article['quantityUnit'] = $value;
        }

        if ($this->calculated) {
            if (isset($this->vatArray['vat'])) {
                $article['vat'] = $this->vatArray['vat'];
            }

            $article['calculated'] = [
                'price' => $this->price,
                'basisPrice' => $this->basisPrice,
                'nettoPriceNotRounded' => $this->nettoPriceNotRounded,
                'nettoSumNotRounded' => $this->nettoSumNotRounded,
                'sum' => $this->sum,
                'nettoBasisPrice' => $this->basisPrice,
                'nettoPrice' => $this->nettoPrice,
                'nettoSum' => $this->nettoSum,
                'vatArray' => $this->vatArray,
                'isEuVat' => $this->isEuVat,
                'isNetto' => $this->isNetto
            ];
        }

        if ($this->existsAttribute('displayPrice')) {
            $article['displayPrice'] = (bool)$this->getAttribute('displayPrice');
        }

        $class = $this->getAttribute('class');

        if (class_exists($class)) {
            $interfaces = class_implements($class);

            if (isset($interfaces[ArticleInterface::class])) {
                return new $class($article);
            }
        }

        return new QUI\ERP\Accounting\Article($article);
    }

    /**
     * Return the custom fields for saving
     *
     * @return array
     */
    protected function getCustomFieldsData(): array
    {
        $fields = $this->getCustomFields();
        $customFields = [];

        if (!count($fields)) {
            return [];
        }

        /* @var $Field QUI\ERP\Products\Field\UniqueField */
        foreach ($fields as $Field) {
            $attributes = $Field->getAttributes();

            if (isset($attributes['options'])) {
                unset($attributes['options']);
            }

            $customFields[$Field->getId()] = $attributes;
        }

        // price factors -> quiqqer/discount#7
        $priceFactors = $this->getPriceFactors()->toErpPriceFactorList()->toArray();

        foreach ($priceFactors as $factor) {
            if (!empty($factor['identifier'])) {
                $factor['custom_calc']['valueText'] = $factor['valueText'];
                $factor['custom_calc']['value'] = $factor['value'];

                $customFields[$factor['identifier']] = $factor;
            }
        }

        return $customFields;
    }

    /**
     * Return the custom fields for saving
     *
     * @return array
     */
    protected function getCustomData(): array
    {
        $data = $this->getAttribute('customData');

        if (is_array($data)) {
            return $data;
        }

        return [];
    }
}
