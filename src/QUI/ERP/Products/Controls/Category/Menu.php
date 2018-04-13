<?php

/**
 * This file contains QUI\ERP\Products\Controls\Category\Menu
 */

namespace QUI\ERP\Products\Controls\Category;

use QUI;

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
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'Site'              => false,
            'data-qui'          => 'package/quiqqer/products/bin/controls/frontend/category/Menu',
            'disableCheckboxes' => false,
            'breadcrumb'        => false
        ]);

        $this->addCSSClass('quiqqer-products-category-menu');
        $this->addCSSFile(dirname(__FILE__).'/Menu.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     *
     * @throws QUI\Exception
     */
    public function getBody()
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        $children = $this->getChildren($this->getSite());

        $Engine->assign([
            'children'         => $children,
            'this'             => $this,
            'childrenTemplate' => dirname(__FILE__).'/Menu.Children.html',
            'Rewrite'          => QUI::getRewrite()
        ]);

        return $Engine->fetch(dirname(__FILE__).'/Menu.html');
    }

    /**
     * Has the category a checkbox?
     *
     * @param QUI\Projects\Site $Site
     * @return bool
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function getChildren($Site = null)
    {
        if (!$Site) {
            $Site = $this->getSite();
        }

        return $Site->getNavigation([
            'where' => [
                'type' => 'quiqqer/products:types/category'
            ]
        ]);
    }

    /**
     * Return the number of the children
     *
     * @param null $Site
     * @return integer
     *
     * @throws QUI\Exception
     */
    public function countChildren($Site = null)
    {
        if (!$Site) {
            $Site = $this->getSite();
        }

        try {
            return $Site->getNavigation([
                'count' => true,
                'where' => [
                    'type' => 'quiqqer/products:types/category'
                ]
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return 0;
        }
    }

    /**
     * Return the number of the children
     *
     * @param QUI\Projects\Site $Site
     * @return bool
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
     */
    public function getBreadcrumbFlag($Site)
    {
        return $this->useBreadcrumbFlag($Site) ?
            'quiqqer-products-category-menu-navigation__isInBreadcrumb' :
            '';
    }

    /**
     * @return mixed|QUI\Projects\Site
     * @throws QUI\Exception
     */
    protected function getSite()
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
