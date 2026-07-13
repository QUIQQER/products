<?php

/**
 * This file contains QUI\ERP\Products\Controls\Category\Menu
 */

namespace QUI\ERP\Products\Controls\Category;

use QUI;

use function dirname;
use function implode;
use function md5;

/**
 * Class Button
 */
class Menu extends QUI\Control
{
    /**
     * constructor
     *
     * @param array<mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'Site' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/category/Menu',
            'disableCheckboxes' => false,
            'breadcrumb' => false
        ]);

        $this->addCSSClass('quiqqer-products-category-menu');
        $this->addCSSFile(dirname(__FILE__) . '/Menu.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $cache = $this->getCacheName();

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $Engine = QUI::getTemplateManager()->getEngine();
        $children = $this->getChildren($this->getSite());

        $Engine->assign([
            'children' => $children,
            'this' => $this,
            'childrenTemplate' => dirname(__FILE__) . '/Menu.Children.html',
            'Rewrite' => QUI::getRewrite()
        ]);

        $result = $Engine->fetch(dirname(__FILE__) . '/Menu.html');

        QUI\Cache\LongTermCache::set($cache, $result);

        return $result;
    }

    /**
     * Has the category a checkbox?
     *
     * @param QUI\Interfaces\Projects\Site $Site
     * @return bool
     *
     * @throws QUI\Exception
     */
    public function hasCategoryCheckBox(QUI\Interfaces\Projects\Site $Site): bool
    {
        // wenn generell aus, dann niemals checkboxen anzeigen
        if ($this->getAttribute('disableCheckboxes')) {
            return false;
        }

        $CurrentSide = QUI::getRewrite()->getSite();

        if (!$CurrentSide instanceof QUI\Interfaces\Projects\Site) {
            return false;
        }

        if (
            $this->getSite()->getAttribute('quiqqer.products.settings.categoryAsFilter')
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

        if (
            $this->getSite()->getAttribute('quiqqer.products.settings.categoryAsFilter')
            && QUI::getRewrite()->isIdInPath($Site->getParentId())
        ) {
            return true;
        }

        if (
            $CurrentSide->getAttribute('quiqqer.products.settings.categoryAsFilter')
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
     * @return array<mixed>
     *
     * @throws QUI\Exception
     */
    public function getChildren(null | QUI\Interfaces\Projects\Site $Site = null): array
    {
        if (!$Site) {
            $Site = $this->getSite();
        }

        $children = $Site->getNavigation([
            'where' => [
                'type' => 'quiqqer/products:types/category'
            ]
        ]);

        return is_array($children) ? $children : [];
    }

    /**
     * Return the number of the children
     *
     * @param null|QUI\Interfaces\Projects\Site $Site
     * @return integer
     */
    public function countChildren(null | QUI\Interfaces\Projects\Site $Site = null): int
    {
        try {
            if (!$Site) {
                $Site = $this->getSite();
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return 0;
        }

        $count = $Site->getNavigation([
            'count' => true,
            'where' => [
                'type' => 'quiqqer/products:types/category'
            ]
        ]);

        return is_int($count) ? $count : count($count);
    }

    /**
     * Return the number of the children
     *
     * @param QUI\Interfaces\Projects\Site $Site
     * @return bool
     */
    public function useBreadcrumbFlag(QUI\Interfaces\Projects\Site $Site): bool
    {
        if ($this->getAttribute('breadcrumb') === false) {
            return false;
        }

        return QUI::getRewrite()->isIdInPath($Site->getId());
    }

    /**
     * Return the css breacrumb css class, if the site is in the rewrite path
     *
     * @param QUI\Interfaces\Projects\Site $Site
     * @return string
     */
    public function getBreadcrumbFlag(QUI\Interfaces\Projects\Site $Site): string
    {
        return $this->useBreadcrumbFlag($Site) ?
            'quiqqer-products-category-menu-navigation__isInBreadcrumb' :
            '';
    }

    /**
     * @return QUI\Interfaces\Projects\Site
     * @throws QUI\Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        $Site = $this->getAttribute('Site');

        if (!$Site instanceof QUI\Interfaces\Projects\Site) {
            $Site = QUI::getRewrite()->getSite();
        }

        if (!$Site instanceof QUI\Interfaces\Projects\Site) {
            throw new QUI\Exception('Could not determine the category menu site.');
        }

        return $Site;
    }

    /**
     * @return string
     */
    protected function getCacheName(): string
    {
        try {
            $Site = $this->getSite();
            $Project = $Site->getProject();
            $RewriteSite = QUI::getRewrite()->getSite();

            if (!$RewriteSite instanceof QUI\Interfaces\Projects\Site) {
                throw new QUI\Exception('Could not determine the current rewrite site.');
            }

            $params = [
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'id' => $Site->getId(),
                'idRewrite' => $RewriteSite->getId(),
                'data-qui' => 'package/quiqqer/products/bin/controls/frontend/category/Menu',
                'disableCheckboxes' => $this->getAttribute('disableCheckboxes'),
                'breadcrumb' => $this->getAttribute('breadcrumb'),
                'showTitle' => $this->getAttribute('showTitle')
            ];

            $cache = md5(implode('', $params));
        } catch (QUI\Exception) {
            return QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'categories/menu';
        }

        return QUI\ERP\Products\Handler\Cache::getBasicCachePath() . 'categories/menu/' . $cache;
    }
}
