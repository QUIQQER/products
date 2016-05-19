<?php

namespace QUI\ERP\Products\Controls\Search;

use QUI;

/**
 * Class Search
 * @package QUI\ERP\Products\Controls\Search
 */
class Search extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'categoryId' => false,
            'Site' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/search/Search',
            'data-name' => false
        ));

        $this->addCSSFile(dirname(__FILE__) . '/Search.css');
        $this->addCSSClass('quiqqer-products-search');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Site    = $this->getSite();
        $Project = $Site->getProject();
        $fields  = array();

        try {
            $Search = new QUI\ERP\Products\Search\FrontendSearch($Site);
            $fields = $Search->getSearchFieldData();
        } catch (QUI\Exception $Exception) {
        }

        $this->setAttribute('data-project', $Project->getName());
        $this->setAttribute('data-lang', $Project->getLang());
        $this->setAttribute('data-siteid', $Site->getId());

        $Engine->assign(array(
            'fields' => $fields
        ));

        return $Engine->fetch(dirname(__FILE__) . '/Search.html');
    }

    /**
     * Return the current site
     *
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
