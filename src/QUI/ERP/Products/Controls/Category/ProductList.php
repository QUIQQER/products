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
     * @var null
     */
    protected $filter = null;

    /**
     * Sorting fields -> can be added via addSort
     * @var array
     */
    protected $sort = array();

    /**
     * CID
     *
     * @var null|QUI\Control
     */
    protected $id = null;

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
            'searchParams'         => false,
            'hideEmptyProductList' => false,
            'categoryStartNumber'  => false,
            'showFilter'           => true, // show the filter, or not
            'showFilterInfo'       => true, // show the filter, or not
            'forceMobileFilter'    => false,
            'autoload'             => false
        ));

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
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine       = QUI::getTemplateManager()->getEngine();
        $Category     = $this->getCategory();
        $searchParams = $this->getAttribute('searchParams');

        $this->setAttribute('data-project', $this->getSite()->getProject()->getName());
        $this->setAttribute('data-lang', $this->getSite()->getProject()->getLang());
        $this->setAttribute('data-siteid', $this->getSite()->getId());
        $this->setAttribute('data-productlist-id', $this->id);
        $this->setAttribute('data-autoload', $this->getAttribute('autoload') ? 1 : 0);

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

            if (isset($_REQUEST['sheet'])) {
                $begin = ((int)$_REQUEST['sheet'] - 1) * $this->getMax();
                $start = $this->getNext($begin, $count);
            } else {
                $start = $this->getStart($count);
            }

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

        if (is_array($searchParams)
            && isset($searchParams['sortBy'])
            && isset($searchParams['sortOn'])
        ) {
            $sort = $searchParams['sortOn'] . ' ' . $searchParams['sortBy'];

            $this->setAttribute('data-sort', htmlspecialchars($sort));
        }

        $Pagination = new QUI\Bricks\Controls\Pagination(array(
            'count'     => $count,
            'Site'      => $this->getSite(),
            'showLimit' => false,
            'limit'     => $this->getMax(),
            'useAjax'   => false
        ));

        $Pagination->loadFromRequest();

        $Engine->assign(array(
            'this'       => $this,
            'Pagination' => $Pagination,
            'count'      => $count,
            'products'   => $products,
            'children'   => $this->getSite()->getNavigation(),
            'more'       => $more,
            'filter'     => $this->getFilter(),
            'hidePrice'  => QUI\ERP\Products\Utils\Package::hidePrice(),
            'Site'       => $this->getSite(),
            'sorts'      => $this->sort,

            'categoryFile'        => $categoryFile,
            'placeholder'         => $this->getProject()->getMedia()->getPlaceholder(),
            'categoryStartNumber' => $this->getAttribute('categoryStartNumber')
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.html');
    }

    /**
     * Return the html from the filter display
     *
     * @return string
     */
    public function createFilter()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'this'   => $this,
            'filter' => $this->getFilter(),
            'cid'    => $this->id
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.Filter.html');
    }

    /**
     * Return the available filter in sorted sequence
     *
     * @return array
     */
    public function getFilter()
    {
        if (!is_null($this->filter)) {
            return $this->filter;
        }

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

        $this->filter = $filter;

        return $filter;
    }

    /**
     * Add a sorting field
     *
     * @param string $title
     * @param string $value
     */
    public function addSort($title, $value)
    {
        $this->sort[] = array(
            'title' => $title,
            'value' => $value
        );
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
     * Return the product count
     *
     * @return int
     */
    public function count()
    {
        return $this->getSearch()->search(
            $this->getCountParams(),
            true
        );
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

        if ($categoryId === false) {
            return null;
        }

        try {
            $this->Category = Categories::getCategory((int)$categoryId);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
            return null;
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
            if ($this->Search === null) {
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
