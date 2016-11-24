<?php

/**
 * This file contains QUI\ERP\Products\Category\Menu
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
class Menu extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'Site'     => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/category/Menu'
        ));

        $this->addCSSClass('quiqqer-products-category-menu');
        $this->addCSSFile(dirname(__FILE__) . '/Menu.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $children = $this->getSite()->getNavigation(array(
            'where' => array(
                'type' => 'quiqqer/products:types/category'
            )
        ));

        $Engine->assign(array(
            'children'         => $children,
            'this'             => $this,
            'childrenTemplate' => dirname(__FILE__) . '/Menu.Children.html'
        ));

        return $Engine->fetch(dirname(__FILE__) . '/Menu.html');
    }

    /**
     * Return the quiqqer/products:types/category children
     *
     * @param QUI\Interfaces\Projects\Site $Site
     * @return array
     */
    public function getChildren(QUI\Interfaces\Projects\Site $Site)
    {
        return $Site->getNavigation(array(
            'where' => array(
                'type' => 'quiqqer/products:types/category'
            )
        ));
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
