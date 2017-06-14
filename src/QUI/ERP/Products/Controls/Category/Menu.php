<?php

/**
 * This file contains QUI\ERP\Products\Controls\Category\Menu
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
            'Site'              => false,
            'data-qui'          => 'package/quiqqer/products/bin/controls/frontend/category/Menu',
            'disableCheckboxes' => false,
            'breadcrumb'        => false
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
        $Engine   = QUI::getTemplateManager()->getEngine();
        $children = $this->getChildren($this->getSite());

        $Engine->assign(array(
            'children'         => $children,
            'this'             => $this,
            'childrenTemplate' => dirname(__FILE__) . '/Menu.Children.html',
            'Rewrite'          => QUI::getRewrite()
        ));

        return $Engine->fetch(dirname(__FILE__) . '/Menu.html');
    }

    /**
     * Has the category a checkbox?
     *
     * @param QUI\Projects\Site $Site
     * @return bool
     */
    public function hasCategoryCheckBox($Site)
    {
        // wenn generell aus, dann niemals checkboxen anzeigen
        if ($this->getAttribute('disableCheckboxes')) {
            return false;
        }

        $CurrentSide = QUI::getRewrite()->getSite();

        if ($this->getSite()->getAttribute('quiqqer.products.settings.categoryAsFilter')
            && $CurrentSide->getId() === 1
        ) {
            return true;
        }

        if ($Site->getId() == $CurrentSide->getId()) {
            return false;
        }

        if ($Site->getParentId() != $CurrentSide->getId()) {
            return false;
        }

        if ($this->getSite()->getAttribute('quiqqer.products.settings.categoryAsFilter')
            && QUI::getRewrite()->isIdInPath($Site->getParentId())
        ) {
            return true;
        }

        if ($CurrentSide->getAttribute('quiqqer.products.settings.categoryAsFilter')
            && QUI::getRewrite()->isIdInPath($Site->getParentId())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Return the quiqqer/products:types/category children
     *
     * @param QUI\Interfaces\Projects\Site|null $Site
     * @return array
     */
    public function getChildren($Site = null)
    {
        if (!$Site) {
            $Site = $this->getSite();
        }

        return $Site->getNavigation(array(
            'where' => array(
                'type' => 'quiqqer/products:types/category'
            )
        ));
    }

    /**
     * Return the number of the children
     *
     * @param null $Site
     * @return integer
     */
    public function countChildren($Site = null)
    {
        if (!$Site) {
            $Site = $this->getSite();
        }

        return $Site->getNavigation(array(
            'count' => true,
            'where' => array(
                'type' => 'quiqqer/products:types/category'
            )
        ));
    }

    /**
     * Return the number of the children
     *
     * @param QUI\Projects\Site $Site
     * @return bool
     */
    public function useBreadcrumbFlag($Site)
    {
        if ($this->getAttribute('breadcrumb') === false) {
            return false;
        }

        return QUI::getRewrite()->isIdInPath($Site->getId());
    }

    /**
     * Return the css breacrumb css class, if the site is in the rewrite path
     *
     * @param QUI\Projects\Site $Site
     * @return string
     */
    public function getBreadcrumbFlag($Site)
    {
        return $this->useBreadcrumbFlag($Site) ?
            'quiqqer-products-category-menu-navigation__isInBreadcrumb' :
            '';
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
