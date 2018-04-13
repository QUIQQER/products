<?php

namespace QUI\ERP\Products\Controls\Search;

use QUI;

/**
 * Class Search
 * @package QUI\ERP\Products\Controls\Search
 * @deprecated
 */
class Search extends QUI\Control
{
    /**
     * @var null
     */
    protected $Search = null;

    /**
     * @var array
     */
    protected $fields = null;

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $this->setAttributes([
            'categoryId'     => false,
            'Site'           => false,
            'data-qui'       => 'package/quiqqer/products/bin/controls/frontend/search/Search',
            'data-name'      => false,
            'freeTextSearch' => true,
            'title'          => true
        ]);

        $this->addCSSFile(dirname(__FILE__).'/Search.css');
        $this->addCSSClass('quiqqer-products-search');

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
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Site    = $this->getSite();
        $Project = $Site->getProject();

        $this->setAttribute('data-project', $Project->getName());
        $this->setAttribute('data-lang', $Project->getLang());
        $this->setAttribute('data-siteid', $Site->getId());

        $Engine->assign([
            'fields' => $this->getSearchFieldData(),
            'this'   => $this
        ]);

        return $Engine->fetch(dirname(__FILE__).'/Search.html');
    }

    /**
     * Has the search fields?
     *
     * @return boolean
     */
    public function hasFields()
    {
        return count($this->getSearchFieldData()) ? true : false;
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
            $this->Search = false;
        }

        return $this->Search;
    }

    /**
     * Return the search field data
     *
     * @return array
     */
    protected function getSearchFieldData()
    {
        if (is_null($this->fields)) {
            $Search = $this->getSearch();

            if ($Search) {
                $this->fields = $Search->getSearchFieldData();
            } else {
                $this->fields = [];
            }
        }

        return $this->fields;
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
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
