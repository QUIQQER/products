<?php

/**
 * This file contains QUI\ERP\Products\Controls\Search\Suggest
 */

namespace QUI\ERP\Products\Controls\Search;

use QUI;
use QUI\ERP\Products\Search\FrontendSearch;
use QUI\Exception;
use QUI\Projects\Project;

use function dirname;

/**
 * Class Suggest
 * @package QUI\ERP\Products\Controls\Search\Suggest
 */
class Suggest extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'Site' => false,
            'Project' => false,
            'data-qui' => 'package/quiqqer/productsearch/bin/controls/frontend/search/Suggest',
            'hideOnProductSearch' => false,
            'globalsearch' => false,
            'limit' => false,
            'showLinkToSearchSite' => false
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/Suggest.css');
        $this->addCSSClass('quiqqer-products-search-suggest');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @see \QUI\Control::create()
     *
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Site = $this->getSite();
        $Search = $this->getSite();
        $Config = QUI::getPackage('quiqqer/productsearch')->getConfig();


        if ($Site->getAttribute('quiqqer.products.settings.showFreeText')) {
            return '';
        }

        if ($this->getAttribute('globalsearch')) {
            $this->setAttribute('data-qui-options-globalsearch', 1);
            $Search = $this->getSearch();
        }

        $limit = $this->getAttribute('limit');

        if (!$limit) {
            $limit = $Config->get('frontendSuggestSearch', 'limit');
        }

        $showLinkToSearchSite = $this->getAttribute('showLinkToSearchSite');

        if (!$showLinkToSearchSite) {
            $showLinkToSearchSite = $Config->get('frontendSuggestSearch', 'showLinkToSearchSite');
        }

        $this->setJavaScriptControlOption('searchurl', $Search->getUrlRewritten());
        $this->setJavaScriptControlOption('limit', $limit);
        $this->setJavaScriptControlOption('showlinktosearchsite', $showLinkToSearchSite);

        $Engine->assign([
            'this' => $this,
            'Site' => $this->getSite(),
            'Search' => $Search
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Suggest.html');
    }

    /**
     * Return the current site
     *
     * @return QUI\Interfaces\Projects\Site
     *
     * @throws QUI\Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        $Site = $this->getAttribute('Site');

        if ($Site) {
            switch ($Site->getAttribute('type')) {
                case FrontendSearch::SITETYPE_SEARCH:
                case FrontendSearch::SITETYPE_CATEGORY:
                    return $Site;
            }
        }

        $Project = $this->getProject();

        $search = $Project->getSites([
            'where' => [
                'type' => FrontendSearch::SITETYPE_SEARCH
            ],
            'limit' => 1
        ]);

        if (isset($search[0])) {
            $this->setAttribute('Site', $search[0]);
        } else {
            $this->setAttribute('Site', $Project->firstChild());
        }

        return $this->getAttribute('Site');
    }

    /**
     * Return the global search
     *
     * @return QUI\Interfaces\Projects\Site
     *
     * @throws QUI\Exception
     */
    protected function getSearch(): QUI\Interfaces\Projects\Site
    {
        $Project = $this->getProject();

        $search = $Project->getSites([
            'where' => [
                'type' => FrontendSearch::SITETYPE_SEARCH
            ],
            'limit' => 1
        ]);

        if (isset($search[0])) {
            return $search[0];
        }

        return $this->getSite();
    }

    /**
     * Return the current project
     *
     * @return Project
     *
     * @throws Exception
     */
    protected function getProject(): QUI\Projects\Project
    {
        if ($this->getAttribute('Project')) {
            return $this->getAttribute('Project');
        }

        return QUI::getRewrite()->getProject();
    }
}
