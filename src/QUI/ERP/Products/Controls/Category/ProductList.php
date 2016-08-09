<?php

/**
 * This file contains QUI\ERP\Products\Category\ProductList
 */
namespace QUI\ERP\Products\Controls\Category;

use QUI;
use QUI\ERP\Products\Handler\Categories;
use QUI\ERP\Products\Handler\Products;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
 */
class ProductList extends QUI\Control
{
    /**
     * @var null|QUI\ERP\Products\Category\Category
     */
    protected $Category = null;

    /**
     * @var null|QUI\ERP\Products\Search\FrontendSearch
     */
    protected $Search = null;

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'categoryId'           => false,
            'data-qui'             => 'package/quiqqer/products/bin/controls/frontend/category/ProductList',
            'data-cid'             => false,
            'view'                 => 'gallery', // gallery, list, detail
            'categoryView'         => 'gallery', // gallery, list, detail
            'Search'               => false,
            'searchParams'         => false,
            'hideEmptyProductList' => false,
            'categoryStartNumber'  => false
        ));

        $this->addCSSFile(dirname(__FILE__) . '/ProductList.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListGallery.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListDetails.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListList.css');

        $this->addCSSFile(dirname(__FILE__) . '/ProductListCategoryGallery.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListCategoryList.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine   = QUI::getTemplateManager()->getEngine();
        $Category = $this->getCategory();
        $Search   = $this->getAttribute('Search');

        if ($Search instanceof QUI\ERP\Products\Controls\Search\Search) {
            /* @var $Search QUI\ERP\Products\Controls\Search\Search */
            $this->setAttribute('data-search', $Search->getAttribute('data-name'));
        }

        $this->setAttribute('data-project', $this->getSite()->getProject()->getName());
        $this->setAttribute('data-lang', $this->getSite()->getProject()->getLang());
        $this->setAttribute('data-siteid', $this->getSite()->getId());

        $products = '';
        $more     = false;
        $count    = 0;

        try {
            $count = $this->getSearch()->search(
                $this->getCountParams(),
                true
            );

            if ($Category) {
                $this->setAttribute('data-cid', $Category->getId());
            }

            $start    = $this->getStart($count);
            $products = $start['html'];
            $more     = $start['more'];
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
            default:
            case 'gallery':
                $categoryFile = dirname(__FILE__) . '/ProductListCategoryGallery.html';
                break;

            case 'list':
                $categoryFile = dirname(__FILE__) . '/ProductListCategoryList.html';
                break;
        }

        // tag groups -> filter
        $filter    = array();
        $tagGroups = $this->getSite()->getAttribute('quiqqer.tags.tagGroups');

        if (!empty($tagGroups) && is_string($tagGroups)) {
            $tagGroups = explode(',', $tagGroups);
        }

        if (is_array($tagGroups)) {
            foreach ($tagGroups as $tagGroup) {
                try {
                    $filter[] = QUI\Tags\Groups\Handler::get($this->getProject(), $tagGroup);
                } catch (QUI\Tags\Exception $Exception) {
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
            } catch (QUI\ERP\Products\Field\Exception $Exception) {
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
                /* @var QUI\Tags\Groups\Group $EntryA */
                $priorityA = $EntryA->getPriority();
            }

            if (!is_array($EntryB) && get_class($EntryB) === QUI\Tags\Groups\Group::class) {
                /* @var QUI\Tags\Groups\Group $EntryB */
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


        $Engine->assign(array(
            'this'      => $this,
            'count'     => $count,
            'products'  => $products,
            'children'  => $this->getSite()->getNavigation(),
            'more'      => $more,
            'filter'    => $filter,
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice(),
            'Site'      => $this->getSite(),

            'categoryFile'        => $categoryFile,
            'placeholder'         => $this->getProject()->getMedia()->getPlaceholder(),
            'categoryStartNumber' => $this->getAttribute('categoryStartNumber')
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.html');
    }

    /**
     * Return the first articles as html array
     *
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     *
     * @throws QUI\Exception
     */
    public function getStart($count = false)
    {
        return $this->renderData(0, $this->getMax(), $count);
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
    public function getNext($start = false, $count = false)
    {
        return $this->renderData($start, $this->getMax(), $count);
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
    protected function renderData($start, $max, $count = false)
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
            $start = 0;
        }

        $more   = true;
        $Search = $this->getSearch();

        try {
            $result = $Search->search(
                $this->getSearchParams($start, $max)
            );

            if ($count === false) {
                $count = $Search->search($this->getCountParams(), true);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_NOTICE);

            $count  = 0;
            $result = array();
        }

        if ($start + $max >= $count) {
            $more = false;
        }

        $products = array();

        foreach ($result as $product) {
            try {
                $products[] = Products::getProduct($product);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $Engine->assign(array(
            'products'   => $products,
            'productTpl' => $productTpl,
            'hidePrice'  => QUI\ERP\Products\Utils\Package::hidePrice(),
            'count'      => $count
        ));

        return array(
            'html'  => $Engine->fetch(dirname(__FILE__) . '/ProductListRow.html'),
            'count' => $count,
            'more'  => $more
        );
    }

    /**
     * Return the default search params
     *
     * @param integer $start - start
     * @param integer|bool $max - optional, ax
     * @return array|mixed
     */
    protected function getSearchParams($start = 0, $max = false)
    {
        $searchParams = $this->getAttribute('searchParams');

        if (!is_array($searchParams)) {
            $searchParams = array();
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

        $searchParams['limit'] = $start . ',' . $max;

        return $searchParams;
    }

    /**
     * @return array|mixed
     */
    protected function getCountParams()
    {
        $searchParams = $this->getAttribute('searchParams');

        if (!is_array($searchParams)) {
            $searchParams = array();
        }

        if ($this->getCategory()) {
            $searchParams['category'] = $this->getCategory()->getId();
        }

        if (!isset($searchParams['freetext'])) {
            $searchParams['freetext'] = '';
        }

        return $searchParams;
    }

    /**
     * Return the max children per row
     *
     * @return int
     */
    protected function getMax()
    {
        // @todo als setting machen
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
     */
    public function getCategory()
    {
        if ($this->Category) {
            return $this->Category->getView();
        }

        $categoryId = $this->getSite()->getAttribute('quiqqer.products.settings.categoryId');

        if (!$categoryId) {
            return null;
        }

        try {
            $this->Category = Categories::getCategory($categoryId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        return $this->Category->getView();
    }

    /**
     * Return the search
     *
     * @return false|QUI\ERP\Products\Search\FrontendSearch
     */
    protected function getSearch()
    {
        try {
            if (is_null($this->Search)) {
                $this->Search = new QUI\ERP\Products\Search\FrontendSearch($this->getSite());
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_DEBUG);
            $this->Search = false;
        }

        return $this->Search;
    }

    /**
     * @return mixed|QUI\Projects\Site
     */
    protected function getSite()
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
