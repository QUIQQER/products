<?php

/**
 * This file contains QUI\ERP\Products\Controls\Search\Suggest
 */

namespace QUI\ERP\Products\Controls\Search;

use QUI;
use QUI\ERP\Products\Search\FrontendSearch;

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
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'Site'                => false,
            'Project'             => false,
            'data-qui'            => 'package/quiqqer/productsearch/bin/controls/frontend/search/Suggest',
            'hideOnProductSearch' => false,
            'globalsearch'        => false
        ]);

        $this->addCSSFile(\dirname(__FILE__).'/Suggest.css');
        $this->addCSSClass('quiqqer-products-search-suggest');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     */
    public function create()
    {
//        $Site = $this->getSite();
//        if ($Site->getAttribute('quiqqer.products.settings.showFreeTextSearch')) {
//            return '';
//        }

        return parent::create();
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

        $Site   = $this->getSite();
        $Search = $this->getSite();

        if ($Site->getAttribute('quiqqer.products.settings.showFreeText')) {
            return '';
        }

        if ($this->getAttribute('globalsearch')) {
            $this->setAttribute('data-qui-options-globalsearch', 1);
            $Search = $this->getSearch();
        }

        $Engine->assign([
            'this'   => $this,
            'Site'   => $this->getSite(),
            'Search' => $Search
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/Suggest.html');
    }

    /**
     * Return the current site
     *
     * @return mixed|QUI\Projects\Site
     *
     * @throws QUI\Exception
     */
    protected function getSite()
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
     * @return false|QUI\Projects\Site
     *
     * @throws QUI\Exception
     */
    protected function getSearch()
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
     * @return mixed|QUI\Projects\Project
     *
     * @throws QUI\Exception
     */
    protected function getProject()
    {
        if ($this->getAttribute('Project')) {
            return $this->getAttribute('Project');
        }

        return QUI::getRewrite()->getProject();
    }
}
