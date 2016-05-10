<?php

/**
 * This file contains QUI\ERP\Products\Category\ProductList
 */
namespace QUI\ERP\Products\Controls\Category;

use QUI;
use QUI\ERP\Products\Handler\Categories;

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
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'categoryId' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/category/ProductList',
            'data-cid' => false,
            'view' => 'galery' // galery, list, detail
        ));

        $this->addCSSFile(dirname(__FILE__) . '/ProductList.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListGalery.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListDetails.css');
        $this->addCSSFile(dirname(__FILE__) . '/ProductListList.css');

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
        $count    = $Category->countProducts();

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

        $Engine->assign(array(
            'rows' => $rows,
            'children' => $this->getSite()->getNavigation(),
            'more' => $more,
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice()
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
        $Category  = $this->getCategory();
        $rowNumber = (int)$rowNumber;

        if (!$Category) {
            return array(
                'html' => '',
                'count' => 0,
                'more' => false
            );
        }

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
            case 'galery':
                $max        = 3;
                $productTpl = dirname(__FILE__) . '/ProductListGalery.html';
                break;
        }

        $more  = true;
        $start = $rowNumber * $max;

        if ($count === false) {
            $count = $Category->countProducts();
        }

        $products = $Category->getProducts(array(
            'limit' => $start . ',' . $max
        ));

        if ($start + $max >= $count) {
            $more = false;
        }

        $Engine->assign(array(
            'products' => $products,
            'rowNumber' => $rowNumber,
            'productTpl' => $productTpl,
            'hidePrice' => QUI\ERP\Products\Utils\Package::hidePrice()
        ));

        return array(
            'html' => $Engine->fetch(dirname(__FILE__) . '/ProductListRow.html'),
            'count' => $count,
            'more' => $more
        );
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

        $categoryId = $this->getAttribute('categoryId');

        if ($categoryId) {
            try {
                $this->Category = Categories::getCategory($categoryId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $this->Category->getView();
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
