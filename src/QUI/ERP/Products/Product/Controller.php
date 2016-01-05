<?php

/**
 * This file contains QUI\ERP\Products\Product\Controller
 */
namespace QUI\ERP\Products\Product;

use QUI;

/**
 * Class Controller
 * - Product data connection
 *
 * @package QUI\ERP\Products\Product
 *
 * @example
 * QUI\ERP\Products\Handler\Products::getProduct( ID );
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
            'from' => QUI\ERP\Products\Tables::getProductTable(),
            'where' => array(
                'id' => $this->Product->getId()
            )
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception(
                array('quiqqer/products', 'exception.product.not.found'),
                404,
                array('id' => $this->Product->getId())
            );
        }

        unset($result[0]['id']);

        $this->Product->setAttributes($result[0]);
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('product.edit');


        QUI::getDataBase()->update(
            QUI\ERP\Products\Tables::getProductTable(),
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
            QUI\ERP\Products\Tables::getProductTable(),
            array('id' => $this->Product->getId())
        );
    }
}
