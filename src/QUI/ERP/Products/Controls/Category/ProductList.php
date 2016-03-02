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
     * @var null
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
            'view' => 'gallery' // gallery, list, detail
        ));

        $this->addCSSFile(dirname(__FILE__) . '/ProductList.css');

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

        if ($Category) {
            $this->setAttribute('data-cid', $Category->getId());
        }

        $rows = array(
            $this->getRow(0),
            $this->getRow(1)
        );

        $Engine->assign(array(
            'rows' => $rows,
            'children' => $this->getSite()->getNavigation()
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductList.html');
    }

    /**
     * Return the row html
     *
     * @param integer $rowNumber
     * @return string
     * @throws QUI\Exception
     */
    public function getRow($rowNumber)
    {
        $Engine    = QUI::getTemplateManager()->getEngine();
        $Category  = $this->getCategory();
        $rowNumber = (int)$rowNumber;

        if (!$Category) {
            return '';
        }

        switch ($this->getAttribute('view')) {
            case 'list':
                $max        = 5;
                $productTpl = dirname(__FILE__) . '/ProductListList.html';
                break;

            case 'details':
                $max        = 10;
                $productTpl = dirname(__FILE__) . '/ProductListDetails.html';
                break;

            default:
            case 'gallery':
                $max        = 3;
                $productTpl = dirname(__FILE__) . '/ProductListGallery.html';
                break;
        }

        $start    = $rowNumber * $max;
        $products = $Category->getProducts(array(
            'limit' => $start . ',' . $max
        ));

        $Engine->assign(array(
            'products' => $products,
            'rowNumber' => $rowNumber,
            'productTpl' => $productTpl
        ));

        return $Engine->fetch(dirname(__FILE__) . '/ProductListRow.html');
    }

    /**
     * Return the product list category
     *
     * @return null|QUI\ERP\Products\Category\Category
     */
    public function getCategory()
    {
        if ($this->Category) {
            return $this->Category;
        }

        $categoryId = $this->getAttribute('categoryId');

        if ($categoryId) {
            try {
                $this->Category = Categories::getCategory($categoryId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $this->Category;
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
