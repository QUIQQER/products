<?php

/**
 * This file contains QUI\ERP\Products\Product\Modell
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
 */
class Modell extends QUI\QDOM implements QUI\ERP\Products\Interfaces\Product
{
    /**
     * Product-ID
     * @var
     */
    protected $id;

    /**
     * Modell constructor
     *
     * @param integer $pid
     *
     * @throws QUI\Exception
     */
    public function __construct($pid)
    {
        $this->id = (int)$pid;
        $this->getController()->load();
    }

    /**
     * @return Controller
     */
    protected function getController()
    {
        return new Controller($this);
    }

    /**
     * @return View
     */
    public function getView()
    {
        return new View($this);
    }

    /**
     * @return View
     */
    public function getViewBackend()
    {
        return new ViewBackend($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return QUI\ERP\Products\Price
     */
    public function getPrice()
    {
        return new QUI\ERP\Products\Price(
            $this->getAttribute('price')
        );
    }

    /**
     * save the data
     */
    public function save()
    {
        $this->getController()->save();
    }

    /**
     * delete the complete product
     */
    public function delete()
    {
        $this->getController()->delete();
    }
}
