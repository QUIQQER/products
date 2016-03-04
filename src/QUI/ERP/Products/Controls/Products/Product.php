<?php

/**
 * This file contains QUI\ERP\Products\Products\Product
 */
namespace QUI\ERP\Products\Controls\Products;

use QUI;

/**
 * Class Button
 *
 * @package QUI\ERP\Products\Controls\Watchlist
 */
class Product extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'Product' => false
        ));

        $this->addCSSFile(dirname(__FILE__) . '/Product.css');

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

        $Engine->assign(array(
            'Product' => $this->getAttribute('Product')
        ));

        return $Engine->fetch(dirname(__FILE__) . '/Product.html');
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
