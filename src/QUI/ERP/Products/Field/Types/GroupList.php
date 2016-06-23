<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\GroupList
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

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
    protected $searchDataType = Search::SEARCHDATATYPE_TEXT;

    /**
     * GroupList constructor.
     * @param int $fieldId
     * @param array $params
     */
    public function __construct($fieldId, array $params)
    {
        $this->setOptions(array(
            'groupIds'      => false,
            'multipleUsers' => true
        ));

        parent::__construct($fieldId, $params);
    }

    /**
     * @return View
     */
    public function getBackendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return View
     */
    public function getFrontendView()
    {
        return new View($this->getFieldDataForView());
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/GroupList';
    }

    /**
     * @return string
     */
    public function getJavaScriptSettings()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/GroupListSettings';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\ERP\Products\Field\Exception
     */
    public function validate($value)
    {
        if (empty($value)) {
            return;
        }

        $groupIds      = $this->getOption('groupIds');
        $multipleUsers = $this->getOption('multipleUsers');
        $userIds       = array();

        if (is_numeric($value)) {
            $userIds = array((int)$value);

        } elseif (is_string($value)) {
            $userIds = json_decode($value, true);

            if (!is_array($userIds) && !is_numeric($userIds)) {
                $userIds = array();
            }

        } elseif (is_array($value)) {
            $userIds = $value;
        }

        if (count($userIds) > 1 && !$multipleUsers) {
            throw new QUI\ERP\Products\Field\Exception(array(
                'quiqqer/products',
                'exception.field.grouplist.user.limit.reached',
                array(
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle()
                )
            ));
        }

        if (empty($userIds)) {
            throw new QUI\ERP\Products\Field\Exception(array(
                'quiqqer/products',
                'exception.field.invalid',
                array(
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle()
                )
            ));
        }

        $isUserInGroups = function ($userGroups) use ($groupIds) {
            foreach ($userGroups as $userGroup) {
                if (!in_array($userGroup, $groupIds)) {
                    return true;
                }
            }
            return false;
        };


        try {
            foreach ($userIds as $userId) {
                if (!is_numeric($userId)) {
                    throw new QUI\ERP\Products\Field\Exception(array(
                        'quiqqer/products',
                        'exception.field.grouplist.invalid.userId'
                    ));
                }

                $User       = QUI::getUsers()->get($userId);
                $userGroups = $User->getGroups(false);

                if (!$isUserInGroups($userGroups)) {
                    throw new QUI\ERP\Products\Field\Exception(array(
                        'quiqqer/products',
                        'exception.field.grouplist.user.not.in.group',
                        array(
                            'userId'   => $User->getId(),
                            'username' => $User->getUsername(),
                            'groups'   => implode(',', $groupIds)
                        )
                    ));
                }
            }

        } catch (QUI\Exception $Exception) {
            throw new QUI\ERP\Products\Field\Exception(array(
                'quiqqer/products',
                'exception.field.unexptected.error',
                array(
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'errorMsg'   => $Exception->getMessage()
                )
            ));
        }
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     */
    public function cleanup($value)
    {
        $groupIds      = $this->getOption('groupIds');
        $multipleUsers = $this->getOption('multipleUsers');
        $userIds       = array();
        $result        = array();

        if (is_numeric($value)) {
            $userIds = array((int)$value);

        } elseif (is_string($value)) {
            $userIds = json_decode($value, true);

            if (!is_array($userIds) && !is_numeric($userIds)) {
                $userIds = array();
            }

        } elseif (is_array($value)) {
            $userIds = $value;
        }

        if (count($userIds) > 1 && !$multipleUsers) {
            return array();
        }

        if (empty($userIds)) {
            return array();
        }

        $isUserInGroups = function ($userGroups) use ($groupIds) {
            foreach ($userGroups as $userGroup) {
                if (!in_array($userGroup, $groupIds)) {
                    return true;
                }
            }
            return false;
        };


        try {
            foreach ($userIds as $userId) {
                if (!is_numeric($userId)) {
                    continue;
                }

                $User       = QUI::getUsers()->get($userId);
                $userGroups = $User->getGroups(false);

                if ($isUserInGroups($userGroups)) {
                    $result[] = $User->getId();
                }
            }

        } catch (QUI\Exception $Exception) {
            return array();
        }

        return $result;
    }

    /**
     * Return value for use in product search cache
     *
     * @param QUI\Locale $Locale
     * @return string
     */
    public function getSearchCacheValue($Locale = null)
    {
        if ($this->isEmpty()) {
            return null;
        }

        $userIds      = $this->getValue();
        $searchValues = array();

        foreach ($userIds as $userId) {
            $searchValues[] = QUI::getUsers()->get($userId)->getName();
        }

        if (count($searchValues) === 1) {
            return $searchValues[0];
        }

        return ',' . implode(',', $searchValues) . ',';
    }

    /**
     * Return all users in from the groups
     *
     * @return array
     */
    public function getUsers()
    {
        $groups = $this->getGroups();
        $result = array();

        /* @var $Group QUI\Groups\Group */
        /* @var $User QUI\Users\User */
        foreach ($groups as $Group) {
            $users = $Group->getUsers();

            foreach ($users as $User) {
                $result[$User->getId()] = $User;
            }
        }

        return $result;
    }

    /**
     * Return the groups in the group list
     *
     * @return array
     */
    public function getGroups()
    {
        $Groups   = QUI::getGroups();
        $groupIds = $this->getOption('groupIds');
        $result   = array();

        foreach ($groupIds as $groupId) {
            try {
                $result[] = $Groups->get($groupId);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_NOTICE
                );
            }
        }

        return $result;
    }

    /**
     * Get all available search types
     *
     * @return array
     */
    public function getSearchTypes()
    {
        return array(
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE,
        );
    }

    /**
     * Get default search type
     *
     * @return string
     */
    public function getDefaultSearchType()
    {
        return Search::SEARCHTYPE_INPUTSELECTSINGLE;
    }
}
