<?php

/**
 * This file contains QUI\ERP\Products\Category\ProductList
 */

namespace QUI\ERP\Products\Controls\Category;

use QUI;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Search\FrontendSearch;
use QUI\Exception;

use function dirname;
use function explode;
use function get_class;
use function htmlspecialchars;
use function implode;
use function is_array;
use function is_null;
use function is_string;
use function json_encode;
use function uniqid;
use function usort;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
 */
class ProductList extends QUI\Control
{
    /**
     * @var null|QUI\ERP\Products\Interfaces\CategoryInterface
     */
    protected ?QUI\ERP\Products\Interfaces\CategoryInterface $Category = null;

    /**
     * @var null|QUI\ERP\Products\Search\FrontendSearch
     */
    protected ?QUI\ERP\Products\Search\FrontendSearch $Search = null;

    /**
     * @var array|null
     */
    protected ?array $filter = null;

    /**
     * Sorting fields -> can be added via addSort
     * @var array
     */
    protected array $sort = [];

    /**
     * CID
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * constructor
     *
     * @param array $attributes
     * @throws Exception
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'class' => 'quiqqer-product-list',
            'categoryId' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/category/ProductList',
            'data-cid' => false,
            'view' => 'gallery',
            'showCategories' => true,
            // gallery, list, detail
            'categoryView' => 'gallery', // gallery, list
            // gallery, list, detail
            'categoryPos' => 'top',
            // top, bottom, false = take setting from products
            'searchParams' => false,    // params used in FrontendSearch (see: $this->getSearchParams)
            'hideEmptyProductList' => false,
            'productLoadNumber' => false,
            // How many products should be loaded?
            'categoryStartNumber' => false,
            'showFilter' => true,
            // show the filter, or not
            'showFilterInfo' => true,
            // show the filter, or not
            'forceMobileFilter' => false,
            'autoload' => false,
            'autoloadAfter' => 3,
            'openProductMode' => '', // 'async', 'normal'. Empty value means take the value from config

            'categoryProductSearchType' => 'OR',

            //  After how many clicks are further products loaded automatically? (false = disable / number )
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice(),
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/ProductList.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListGallery.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListDetails.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListList.css');

        $this->addCSSFile(dirname(__FILE__) . '/ProductListCategoryGallery.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListCategoryList.css');

        $this->id = uniqid();

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @throws \Exception
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Category = $this->getCategory();
        $searchParams = $this->getAttribute('searchParams');
        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        // global settings: category pos
        $categoryPos = $this->getAttribute('categoryPos');

        if ($categoryPos === 'false') {
            $categoryPos = false;
        }

        if (!$categoryPos) {
            $this->setAttribute('categoryPos', $Config->get('products', 'categoryPos'));
        }

        // global settings: product load number
        if ($this->getAttribute('productLoadNumber') == '' || !$this->getAttribute('productLoadNumber')) {
            $this->setAttribute('productLoadNumber', $Config->get('products', 'productLoadNumber'));
        }

        // global settings: product autoload after x clicks
        if ($this->getAttribute('autoloadAfter') == '' || !$this->getAttribute('autoloadAfter')) {
            $this->setAttribute('autoloadAfter', $Config->get('products', 'autoloadAfter'));
        }

        // global settings: open product mode (normal or asynchronously)
        $openProductMode = match ($this->getAttribute('openProductMode')) {
            'async' => 1,
            'normal' => 0,
            default => $Config->get('products', 'openProductAsync')
        };

        $this->setAttribute('data-project', $this->getSite()->getProject()->getName());
        $this->setAttribute('data-lang', $this->getSite()->getProject()->getLang());
        $this->setAttribute('data-siteid', $this->getSite()->getId());
        $this->setAttribute('data-productlist-id', $this->id);
        $this->setAttribute('data-autoload', $this->getAttribute('autoload') ? 1 : 0);
        $this->setAttribute('data-autoloadAfter', $this->getAttribute('autoloadAfter'));
        $this->setAttribute('data-productLoadNumber', $this->getAttribute('productLoadNumber'));
        $this->setAttribute('data-openproductasync', $openProductMode);

        $products = '';
        $more = false;
        $count = 0;

        try {
            $count = $this->getSearch()->search(
                $this->getCountParams(),
                true
            );

            if ($Category) {
                $this->setAttribute('data-cid', $Category->getId());
            }

            if (isset($_REQUEST['sheet'])) {
                $begin = ((int)$_REQUEST['sheet'] - 1) * $this->getMax();
                $start = $this->getNext($begin, $count);
            } else {
                $start = $this->getStart($count);
            }

            $products = $start['html'];
            $more = $start['more'];
        } catch (QUI\Permissions\Exception $Exception) {
            QUI\System\Log::addNotice(
                $Exception->getMessage(),
                $Exception->getContext()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException(
                $Exception,
                QUI\System\Log::LEVEL_NOTICE
            );
        }

        // category view
        switch ($this->getAttribute('categoryView')) {
            case 'list':
                $categoryFile = dirname(__FILE__) . '/ProductListCategoryList.html';
                break;

            default:
            case 'gallery':
                $categoryFile = dirname(__FILE__) . '/ProductListCategoryGallery.html';
                break;
        }

        switch ($this->getAttribute('view')) {
            case 'detail':
                $this->setAttribute('data-qui-options-view', 'detail');
                break;

            case 'list':
                $this->setAttribute('data-qui-options-view', 'list');
                break;
            case 'gallery':
            default:
                $this->setAttribute('data-qui-options-view', 'gallery');
        }

        if (is_array($searchParams) && isset($searchParams['categories'])) {
            $this->setAttribute(
                'data-categories',
                htmlspecialchars(implode(',', $searchParams['categories']))
            );
        }

        if (is_array($searchParams) && isset($searchParams['tags'])) {
            $this->setAttribute(
                'data-tags',
                htmlspecialchars(implode(',', $searchParams['tags']))
            );
        }

        if (
            is_array($searchParams)
            && isset($searchParams['sortBy'])
            && isset($searchParams['sortOn'])
        ) {
            $sort = $searchParams['sortOn'] . ' ' . $searchParams['sortBy'];

            $this->setAttribute('data-sort', htmlspecialchars($sort));
        }

        $Pagination = new QUI\Controls\Navigating\Pagination([
            'count' => $count,
            'Site' => $this->getSite(),
            'showLimit' => false,
            'limit' => $this->getMax(),
            'useAjax' => false,
        ]);

        $Pagination->loadFromRequest();

        $showCategories = $this->getAttribute('showCategories');
        $categoryChildren = [];

        if ($showCategories) {
            $categoryChildren = $this->getSite()->getNavigation();
        }

        $Engine->assign([
            'this' => $this,
            'Pagination' => $Pagination,
            'count' => $count,
            'products' => $products,
            'showCategories' => $showCategories,
            'children' => $categoryChildren,
            'more' => $more,
            'filter' => $this->getFilter(),
            'hidePrice' => $this->getAttribute('hidePrice'),
            'Site' => $this->getSite(),
            'sorts' => $this->sort,

            'categoryFile' => $categoryFile,
            'placeholder' => $this->getProject()->getMedia()->getPlaceholder(),
            'categoryStartNumber' => $this->getAttribute('categoryStartNumber')
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.html');
    }

    /**
     * Return the html from the filter display
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function createFilter(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (class_exists('QUI\ERP\Tags\Manager')) {
            $Engine->assign(
                'tagsFromProducts',
                json_encode(QUI\ERP\Tags\Manager::getTagsFromProductCategorySite($this->getSite()))
            );
        }

        $Engine->assign([
            'this' => $this,
            'filter' => $this->getFilter(),
            'cid' => $this->id,
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.Filter.html');
    }

    /**
     * Return the available filter in sorted sequence
     *
     * @return array|null
     * @throws Exception
     */
    public function getFilter(): ?array
    {
        if (!is_null($this->filter)) {
            return $this->filter;
        }

        $filter = [];
        $tagGroups = $this->getSite()->getAttribute('quiqqer.tags.tagGroups');

        if (!empty($tagGroups) && is_string($tagGroups)) {
            $tagGroups = explode(',', $tagGroups);
        }

        if (is_array($tagGroups)) {
            foreach ($tagGroups as $tagGroup) {
                try {
                    $filter[] = QUI\Tags\Groups\Handler::get($this->getProject(), $tagGroup);
                } catch (QUI\Tags\Exception) {
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }

        $fields = $this->getSearch()->getSearchFieldData();

        foreach ($fields as $field) {
            try {
                $Field = QUI\ERP\Products\Handler\Fields::getField($field['id']);

                if ($Field->hasViewPermission()) {
                    $field['priority'] = $Field->getAttribute('priority');

                    if (!isset($field['searchData'])) {
                        $field['searchData'] = '';
                    } elseif (!is_string($field['searchData'])) {
                        $field['searchData'] = json_encode($field['searchData']);
                    }

                    $filter[] = $field;
                }
            } catch (QUI\ERP\Products\Field\Exception) {
                // nothing
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // sort
        usort($filter, function ($EntryA, $EntryB) {
            $priorityA = 0;
            $priorityB = 0;

            if (!is_array($EntryA) && get_class($EntryA) === QUI\Tags\Groups\Group::class) {
                $priorityA = $EntryA->getPriority();
            }

            if (!is_array($EntryB) && get_class($EntryB) === QUI\Tags\Groups\Group::class) {
                $priorityB = $EntryB->getPriority();
            }

            if (is_array($EntryA) && isset($EntryA['priority'])) {
                $priorityA = $EntryA['priority'];
            }

            if (is_array($EntryB) && isset($EntryB['priority'])) {
                $priorityB = $EntryB['priority'];
            }

            if ($priorityA == 0 && $priorityB == 0) {
                // sort via title?
            }

            if ($priorityA == 0) {
                return 1;
            }

            if ($priorityB == 0) {
                return -1;
            }

            if ($priorityA == $priorityB) {
                return 0;
            }

            return $priorityA > $priorityB ? 1 : -1;
        });

        $this->filter = $filter;

        return $filter;
    }

    /**
     * Add a sorting field
     *
     * @param string $title
     * @param string $value
     */
    public function addSort(string $title, string $value): void
    {
        $this->sort[] = [
            'title' => $title,
            'value' => $value,
        ];
    }

    /**
     * Return the first articles as html array
     *
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     *
     * @throws QUI\Exception
     */
    public function getStart(bool | int $count = false): array
    {
        return $this->renderData(1, $this->getMax(), $count);
    }

    /**
     * Return the next articles as html array
     *
     * @param boolean|integer $start - (optional) start position
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     *
     * @throws QUI\Exception
     */
    public function getNext(bool | int $start = false, bool | int $count = false): array
    {
        return $this->renderData($start, $this->getMax(), $count);
    }

    /**
     * Return the product count
     *
     * @return int
     */
    public function count(): int
    {
        try {
            return $this->getSearch()->search(
                $this->getCountParams(),
                true
            );
        } catch (QUI\Exception) {
            return 0;
        }
    }

    /**
     * Render the products data
     *
     * @param boolean|integer $start - (optional) start position
     * @param boolean|integer $max - (optional) max children
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     *
     * @throws QUI\Exception
     */
    protected function renderData(bool | int $start, bool | int $max, bool | int $count = false): array
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        switch ($this->getAttribute('view')) {
            case 'list':
                $productTpl = dirname(__FILE__) . '/ProductListList.html';
                break;

            case 'detail':
                $productTpl = dirname(__FILE__) . '/ProductListDetails.html';
                break;

            default:
            case 'gallery':
                $productTpl = dirname(__FILE__) . '/ProductListGallery.html';
                break;
        }

        if (!$start) {
            $start = 1;
        }

        $more = true;
        $Search = $this->getSearch();

        try {
            $searchParams = $this->getSearchParams($start, $max);
            $result = $Search->search($searchParams);

            // Send searched fields to frontend
            if (!empty($searchParams['fields'])) {
                $this->setJavaScriptControlOption('searchfields', json_encode($searchParams['fields']));
            }

            if ($count === false) {
                $count = $Search->search($this->getCountParams(), true);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_NOTICE);

            $count = 0;
            $result = [];
        }

        if ($start + $max > $count) {
            $more = false;
        }

        $products = [];

        foreach ($result as $product) {
            try {
                $products[] = Products::getProduct($product);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $Engine->assign([
            'this' => $this,
            'products' => $products,
            'productTpl' => $productTpl,
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice(),
            'count' => $count,
            'JsonLd' => new QUI\ERP\Products\Product\JsonLd()
        ]);

        return [
            'html' => $Engine->fetch(dirname(__FILE__) . '/ProductListRow.html'),
            'count' => $count,
            'more' => $more
        ];
    }

    /**
     * Render a product for the product list
     *
     * @param QUI\ERP\Products\Product\Product $Product
     * @param string $productTpl - view type tpl
     * @return string
     * @throws QUI\Exception
     */
    public function renderProduct(QUI\ERP\Products\Product\Product $Product, string $productTpl): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'this' => $this,
            'JsonLd' => new QUI\ERP\Products\Product\JsonLd(),
            'Product' => $Product->getView(),
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice()
        ]);

        return $Engine->fetch($productTpl);
    }

    /**
     * Get formatted old price (retail or offer)
     *
     * @param QUI\ERP\Products\Product\ViewFrontend $Product
     * @return QUI\ERP\Products\Controls\Price|null
     */
    public function getProductOldPriceDisplay(
        QUI\ERP\Products\Product\ViewFrontend $Product
    ): ?QUI\ERP\Products\Controls\Price {
        try {
            $OldPrice = null;
            $Price = $Product->getPrice();

            // Offer price has higher priority than retail price
            if ($Product->hasOfferPrice()) {
                $OldPrice = new QUI\ERP\Products\Controls\Price([
                    'Price' => new QUI\ERP\Money\Price(
                        $Product->getOriginalPrice()->getValue(),
                        QUI\ERP\Currency\Handler::getDefaultCurrency()
                    ),
                    'withVatText' => false
                ]);
            } elseif ($Product->getFieldValue('FIELD_PRICE_RETAIL')) {
                // retail price
                $PriceRetail = $Product->getCalculatedPrice(Fields::FIELD_PRICE_RETAIL)->getPrice();

                if ($Price->getPrice() < $PriceRetail->getPrice()) {
                    $OldPrice = new QUI\ERP\Products\Controls\Price([
                        'Price' => $PriceRetail,
                        'withVatText' => false
                    ]);
                }
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $OldPrice;
    }

    /**
     * Render the category list
     *
     * @param array $categories - list of site categories
     * @param string $categoryTpl - view type tpl
     * @return string
     * @throws QUI\Exception|\Exception
     */
    public function renderCategories(array $categories, string $categoryTpl): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'children' => $categories,
            'categoryStartNumber' => $this->getAttribute('categoryStartNumber'),
            'placeholder' => $this->getProject()->getMedia()->getPlaceholder()
        ]);

        return $Engine->fetch($categoryTpl);
    }

    /**
     * Return the default search params
     *
     * @param integer $start - start
     * @param bool|integer $max - optional, ax
     * @return array|mixed
     *
     * @throws QUI\Exception
     */
    protected function getSearchParams(int $start = 0, bool | int $max = false): mixed
    {
        $searchParams = $this->getAttribute('searchParams');

        if (!is_array($searchParams)) {
            $searchParams = [];
        }

        if ($this->getCategory()) {
            $searchParams['category'] = $this->getCategory()->getId();
        }

        if (!isset($searchParams['freetext'])) {
            $searchParams['freetext'] = '';
        }

        if (!$max) {
            $max = $this->getMax();
        }

        $searchParams['sheet'] = round($start / $max) + 1;
        $searchParams['limit'] = $max;

        $searchParams['categoryProductSearchType'] = $this->getAttribute('categoryProductSearchType');

//        $searchParams['ignoreFindVariantParentsByChildValues'] = true;

        return $searchParams;
    }

    /**
     * @return array|mixed
     *
     * @throws QUI\Exception
     */
    protected function getCountParams(): mixed
    {
        $searchParams = $this->getAttribute('searchParams');

        if (!is_array($searchParams)) {
            $searchParams = [];
        }

        if ($this->getCategory()) {
            $searchParams['category'] = $this->getCategory()->getId();
        }

        if (!isset($searchParams['freetext'])) {
            $searchParams['freetext'] = '';
        }

//        $searchParams['ignoreFindVariantParentsByChildValues'] = true;

        return $searchParams;
    }

    /**
     * Return the max children per row
     *
     * @return int
     */
    protected function getMax(): int
    {
        // settings
        if ($this->getAttribute('productLoadNumber')) {
            return $this->getAttribute('productLoadNumber');
        }

        switch ($this->getAttribute('view')) {
            case 'list':
                return 10;

            case 'detail':
                return 5;
        }

        // default
        return 9;
    }

    /**
     * Return the product list category
     *
     * @return null|QUI\ERP\Products\Category\ViewBackend|QUI\ERP\Products\Category\ViewFrontend
     *
     * @throws QUI\Exception
     */
    public function getCategory(): QUI\ERP\Products\Category\ViewBackend | QUI\ERP\Products\Category\ViewFrontend | null
    {
        if ($this->Category && method_exists($this->Category, 'getView')) {
            return $this->Category->getView();
        }

        $categoryId = $this->getSite()->getAttribute('quiqqer.products.settings.categoryId');

        if ($categoryId === false) {
            return null;
        }

        try {
            $this->Category = Categories::getCategory((int)$categoryId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return null;
        }

        if (method_exists($this->Category, 'getView')) {
            return $this->Category->getView();
        }

        return null;
    }

    /**
     * Return the search
     *
     * @return bool|FrontendSearch|null
     */
    protected function getSearch(): bool | QUI\ERP\Products\Search\FrontendSearch | null
    {
        try {
            if ($this->Search === null) {
                $this->Search = new QUI\ERP\Products\Search\FrontendSearch($this->getSite());
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
            $this->Search = null;
        }

        return $this->Search;
    }

    /**
     * @return QUI\Interfaces\Projects\Site
     * @throws QUI\Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
