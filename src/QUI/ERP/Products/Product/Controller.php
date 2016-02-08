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
     * @var Product
     */
    protected $Product;

    /**
     * Controller constructor.
     * @param Product $Product
     */
    public function __construct(Product $Product)
    {
        $this->Product = $Product;
    }

    /**
     * Return the Product Model
     * @return Product
     */
    public function getModel()
    {
        return $this->Product;
    }

    /**
     * Return the Product View
     *
     * @return ViewBackend|ViewFrontend
     */
    public function getView()
    {
        return $this->getModel()->getView();
    }

    /**
     * Load the data for the model
     *
     * @throws QUI\Exception
     */
    public function load()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from' => QUI\ERP\Products\Utils\Tables::getProductTableName(),
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
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
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
            QUI\ERP\Products\Utils\Tables::getProductTableName(),
            array('id' => $this->Product->getId())
        );
    }
}
