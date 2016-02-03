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
    protected $Modell;

    /**
     * Controller constructor.
     * @param Category $Modell
     */
    public function __construct(Category $Modell)
    {
        $this->Modell = $Modell;
    }

    /**
     * Return the Product Modell
     * @return Category
     */
    public function getModell()
    {
        return $this->Modell;
    }

    /**
     * Save the data to the database
     */
    public function save()
    {
        QUI\Rights\Permission::checkPermission('category.edit');

        $fields = array();

        /* @var $Field QUI\ERP\Products\Field\Field */
        foreach ($this->getModell()->getFields() as $Field) {
            $fields[] = $Field->getAttributes();
        }

        QUI::getDataBase()->update(
            QUI\ERP\Products\Utils\Tables::getCategoryTableName(),
            array(
                'name' => $this->getModell()->getAttribute('name'),
                'fields' => json_encode($fields)
            ),
            array('id' => $this->getModell()->getId())
        );

        QUI\ERP\Products\Handler\Categories::clearCache($this->getModell()->getId());
    }

    /**
     * Delete the complete category with sub categories
     * ID 0 cant be deleted
     */
    public function delete()
    {
        if ($this->getModell()->getId() === 0) {
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

        $recursiveHelper($this->Modell->getId());

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
