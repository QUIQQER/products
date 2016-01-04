<?php

/**
 * This file contains QUI\Products\Product\Controller
 */
namespace QUI\Products\Product;

use QUI;

/**
 * Class Controller
 * Product Manager
 *
 * @example
 *
 *
 * @package QUI\Products\Product
 */
class Controller
{
    /**
     * @var Modell
     */
    protected $Product;

    /**
     * Controller constructor.
     * @param Modell $Product
     */
    public function __construct(Modell $Product)
    {
        $this->Product = $Product;
    }

    /**
     * Return the Product Modell
     * @return Modell
     */
    public function getModell()
    {
        return $this->Product;
    }

    /**
     * Return the Product View
     * @return View
     */
    public function getView()
    {
        return $this->getModell()->getView();
    }

    /**
     * Load the data for the modell
     *
     * @throws QUI\Exception
     */
    public function load()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from' => QUI\Products\Tables::getProductTable(),
            'where' => array(
                'id' => $this->Product->getId()
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.product.not.found'),
                404,
                array('productId' => $this->Product->getId())
            );
        }

        foreach ($result[0] as $key => $value) {
            $this->Product->setAttribute($key, $value);
        }
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('product.edit');


        QUI::getDataBase()->update(
            QUI\Products\Tables::getProductTable(),
            array(
                'productNo' => $this->Product->getAttribute('productNo'),
                'data' => $this->Product->getFields()
            ),
            array('id' => $this->Product->getId())
        );
    }

    /**
     * Delete the complete product
     */
    public function delete()
    {
        QUI\Rights\Permission::checkPermission('product.delete');

        QUI::getDataBase()->delete(
            QUI\Products\Tables::getProductTable(),
            array('id' => $this->Product->getId())
        );
    }
}
