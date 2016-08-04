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

        try {
            $count = $this->getSearch()->search(
                $this->getCountParams(),
                true
            );

            if ($Category) {
                $this->setAttribute('data-cid', $Category->getId());
            }

            $rows = array(
                $this->getRow(0, $count),
                $this->getRow(1, $count),
                $this->getRow(2, $count)
            );

            // get more?
            $more = true;

            foreach ($rows as $entry) {
                if (isset($entry['more']) && $entry['more'] === false) {
                    $more = false;
                    break;
                }
            }

        } catch (QUI\Permissions\Exception $Exception) {
            QUI\System\Log::addNotice(
                $Exception->getMessage(),
                $Exception->getContext()
            );

            $more  = false;
            $count = 0;
            $rows  = array();

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_NOTICE);

            $more  = false;
            $count = 0;
            $rows  = array();
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
                $Field    = QUI\ERP\Products\Handler\Fields::getField($field['id']);
                $filter[] = $Field;

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

            if (get_class($EntryA) === QUI\Tags\Groups\Group::class) {
                /* @var QUI\Tags\Groups\Group $EntryA */
                $priorityA = $EntryA->getPriority();
            }

            if (get_class($EntryB) === QUI\Tags\Groups\Group::class) {
                /* @var QUI\Tags\Groups\Group $EntryB */
                $priorityB = $EntryB->getPriority();
            }

            if (QUI\ERP\Products\Utils\Fields::isField($EntryA)) {
                /* @var QUI\ERP\Products\Field\Field $EntryA */
                $priorityA = $EntryA->getAttribute('priority');
            }

            if (QUI\ERP\Products\Utils\Fields::isField($EntryB)) {
                /* @var QUI\ERP\Products\Field\Field $EntryB */
                $priorityB = $EntryB->getAttribute('priority');
            }

            if ($priorityA == $priorityB) {
                return 0;
            }

            return $priorityA > $priorityB ? 1 : -1;
        });

        $Engine->assign(array(
            'this'      => $this,
            'count'     => $count,
            'rows'      => $rows,
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
     * Return the row html
     *
     * @param integer $rowNumber
     * @param boolean|integer $count - (optional) count of children, if false, it looks for a count
     * @return array [html, count, more]
     * @throws QUI\Exception
     */
    public function getRow($rowNumber, $count = false)
    {
        $Engine    = QUI::getTemplateManager()->getEngine();
        $rowNumber = (int)$rowNumber;

        switch ($this->getAttribute('view')) {
            case 'list':
                $max        = 10;
                $productTpl = dirname(__FILE__) . '/ProductListList.html';
                break;

            case 'detail':
                $max        = 5;
                $productTpl = dirname(__FILE__) . '/ProductListDetails.html';
                break;

            default:
            case 'gallery':
                $max        = 3;
                $productTpl = dirname(__FILE__) . '/ProductListGallery.html';
                break;
        }

        $more   = true;
        $start  = $rowNumber * $max;
        $Search = $this->getSearch();

        try {
            $result = $Search->search(
                $this->getSearchParams($rowNumber, $max)
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
            }
        }

        $Engine->assign(array(
            'products'   => $products,
            'rowNumber'  => $rowNumber,
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
     * @param $rowNumber
     * @param $max
     * @return array|mixed
     */
    protected function getSearchParams($rowNumber, $max)
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

        $searchParams['limit'] = $max;
        $searchParams['sheet'] = $rowNumber + 1;

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
