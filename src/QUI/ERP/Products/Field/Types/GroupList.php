<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\GroupList
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;

/**
 * Class GroupList
 *
 * Beschreibung des GroupList Typs:
 * In diesem Feld ist es möglich eine Gruppe auszuwählen.
 * Wenn dieser Typ einem Produkt zugewiesen ist,
 * kann nun aus der zugewiesenen Gruppe ein Benutzer ausgewählt werden.
 * Nutzbar zB. für Hersteller und Lieferanten
 *
 * D.h. Hersteller und Lieferanten sind Benutzer
 *
 * @package QUI\ERP\Products\Field
 * @todo Benutzergruppe beim Setup anlegen
 * @todo Einstellung zur Verfügung stellen, Lieferant und Hersteller gruppe festlegen
 */
class GroupList extends QUI\ERP\Products\Field\Field
{
    /**
     * GroupList constructor.
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setAttributes(array(
            'groupId' => false,
            'multipleUsers' => true
        ));

        parent::__construct($fieldId, $params);
    }

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View(array(
            'value' => $this->cleanup($this->getValue()),
            'title' => $this->getTitle(),
            'prefix' => $this->getAttribute('prefix'),
            'suffix' => $this->getAttribute('suffix'),
            'priority' => $this->getAttribute('priority')
        ));
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/GroupList';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $groupId       = $this->getAttribute('groupId');
        $multipleUsers = $this->getAttribute('multipleUsers');
        $checkIds      = array();

        if (is_array($value)) {
            if (count($value) > 1
                && !$multipleUsers
            ) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.grouplist.user.limit.reached',
                    array(
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle()
                    )
                ));
            }

            $checkIds = $value;
        } else {
            if (!is_numeric($value)) {
                throw new QUI\Exception(array(
                    'quiqqer/products',
                    'exception.field.invalid',
                    array(
                        'fieldId' => $this->getId(),
                        'fieldTitle' => $this->getTitle()
                    )
                ));
            }

            $checkIds[] = $value;
        }

        try {
            foreach ($checkIds as $userId) {
                if (!is_numeric($userId)) {
                    throw new QUI\Exception(array(
                        'quiqqer/products',
                        'exception.field.grouplist.invalid.userId'
                    ));
                }

                $User       = QUI::getUsers()->get($userId);
                $userGroups = $User->getGroups(false);

                if (!in_array($groupId, $userGroups)) {
                    throw new QUI\Exception(array(
                        'quiqqer/products',
                        'exception.field.grouplist.user.not.in.group',
                        array(
                            'userId' => $this->getId(),
                            'groupId' => $this->getTitle()
                        )
                    ));
                }
            }

        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(array(
                'quiqqer/products',
                'exception.field.unexptected.error',
                array(
                    'fieldId' => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'errorMsg' => $Exception->getMessage()
                )
            ));
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     * @throws \QUI\Exception
     */
    public function cleanup($value)
    {
        $groupId       = $this->getAttribute('groupId');
        $multipleUsers = $this->getAttribute('multipleUsers');
        $checkIds      = array();
        $userIds       = array();

        if (is_array($value)) {
            if (count($value) > 1
                && !$multipleUsers
            ) {
                $checkIds = array_shift($value);
            } else {
                $checkIds = $value;
            }
        } else {
            $checkIds[] = $value;
        }

        foreach ($checkIds as $userId) {
            try {
                $User       = QUI::getUsers()->get($userId);
                $userGroups = $User->getGroups(false);
            } catch (QUI\Exception $Exception) {
                continue;
            }

            if (!is_numeric($groupId)) {
                continue;
            }

            if (!in_array($groupId, $userGroups)) {
                continue;
            }

            $userIds[] = $userId;
        }

        return $userIds;
    }
}
