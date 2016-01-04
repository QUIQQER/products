<?php

/**
 * This file contains QUI\Products\Product\Modell
 */
namespace QUI\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @package QUI\Products\Product
 */
class Modell extends QUI\QDOM implements QUI\Products\Interfaces\Product
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
    public function getViewAdmin()
    {
        return new ViewAdmin($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return QUI\Products\Price
     */
    public function getPrice()
    {
        return new QUI\Products\Price(
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
