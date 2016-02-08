<?php

/**
 * This file contains QUI\ERP\Products\Category\Controller
 */
namespace QUI\ERP\Products\Category;

use QUI;

/**
 * Class Controller
 * - Category data connection
 *
 * @package QUI\ERP\Products\Category
 *
 * @example
 * QUI\ERP\Products\Handler\Categories::getCategory( ID );
 */
class Controller
{
    /**
     * @var Category
     */
    protected $Model;

    /**
     * Controller constructor.
     * @param Category $Modell
     */
    public function __construct(Category $Model)
    {
        $this->Model = $Model;
    }

    /**
     * Return the Product Modell
     * @return Category
     */
    public function getModel()
    {
        return $this->Model;
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('category.edit');

        $fields = array();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->getModel()->getFields() as $Field) {
            $attributes                 = $Field->getAttributes();
            $attributes['publicStatus'] = $Field->getAttribute('publicStatus') ? 1 : 0;
            $attributes['searchStatus'] = $Field->getAttribute('searchStatus') ? 1 : 0;

            $fields[] = $attributes;
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array(
                'fields' => json_encode($fields)
            ),
            array('id' => $this->getModel()->getId())
        );

        QUI\ERP\Products\Handler\Categories::clearCache($this->getModel()->getId());
    }

    /**
     * Delete the complete category with sub categories
     * ID 0 cant be deleted
     */
    public function delete()
    {
        if ($this->getModel()->getId() === 0) {
            return;
        }

        QUI\Rights\Permission::checkPermission('category.delete');

        // get children ids
        $ids = array();

        $recursiveHelper = function ($parentId) use (&$ids, &$recursiveHelper) {
            try {
                $Category = QUI\ERP\Products\Handler\Categories::getCategory($parentId);
                $children = $Category->getChildren();

                $ids[] = $Category->getId();

                /* @var $Child QUI\ERP\Products\Category\Category */
                foreach ($children as $Child) {
                    $recursiveHelper($Child->getId(), $ids, $recursiveHelper);
                }

            } catch (QUI\Exception $Exception) {
            }
        };

        $recursiveHelper($this->Model->getId());

        foreach ($ids as $id) {
            $id = (int)$id;

            if (!$id) {
                continue;
            }

            QUI::getDataBase()->delete(
                QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
                array('id' => $id)
            );

            QUI\Translator::delete(
                'quiqqer/products',
                'products.category.' . $id . '.title'
            );

            QUI\ERP\Products\Handler\Categories::clearCache($id);
        }
    }
}
