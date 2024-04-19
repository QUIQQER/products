<?php

namespace QUI\ERP\Products\Controls\Search;

use QUI;

use QUI\ERP\Products\Search\FrontendSearch;

use QUI\Exception;

use function dirname;
use function is_null;

/**
 * Class Search
 * @package QUI\ERP\Products\Controls\Search
 * @deprecated
 */
class Search extends QUI\Control
{
    /**
     * @var bool|FrontendSearch|null
     */
    protected bool|null|FrontendSearch $Search = null;

    /**
     * @var array|null
     */
    protected ?array $fields = null;

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'categoryId' => false,
            'Site' => false,
            'data-qui' => 'package/quiqqer/productsearch/bin/controls/frontend/search/Search',
            'data-name' => false,
            'freeTextSearch' => true,
            'title' => true
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/Search.css');
        $this->addCSSClass('quiqqer-products-search');

        parent::__construct($attributes);
    }

    /**
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Site = $this->getSite();
        $Project = $Site->getProject();

        $this->setAttribute('data-project', $Project->getName());
        $this->setAttribute('data-lang', $Project->getLang());
        $this->setAttribute('data-siteid', $Site->getId());

        $Engine->assign([
            'fields' => $this->getSearchFieldData(),
            'this' => $this
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/Search.html');
    }

    /**
     * Has the search fields?
     *
     * @return boolean
     * @throws Exception
     */
    public function hasFields(): bool
    {
        return (bool)count($this->getSearchFieldData());
    }

    /**
     * Return the search
     *
     * @return bool|FrontendSearch|null
     */
    protected function getSearch(): bool|QUI\ERP\Products\Search\FrontendSearch|null
    {
        try {
            if (is_null($this->Search)) {
                $this->Search = new QUI\ERP\Products\Search\FrontendSearch($this->getSite());
            }
        } catch (QUI\Exception) {
            $this->Search = false;
        }

        return $this->Search;
    }

    /**
     * Return the search field data
     *
     * @return array
     * @throws Exception
     */
    protected function getSearchFieldData(): array
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
     * @return QUI\Interfaces\Projects\Site
     *
     * @throws QUI\Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
