<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\GroupList
 */

namespace QUI\ERP\Products\Field\Types;

use QUI;
use QUI\ERP\Products\Field\View;
use QUI\ERP\Products\Handler\Search;

use function count;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_last_error;

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
 * @todo Einstellung zur Verfügung stellen, Lieferant- und Herstellergruppe festlegen
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
        $this->setOptions([
            'groupIds'      => false,
            'multipleUsers' => true
        ]);

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
        $params = $this->getFieldDataForView();
        $users  = $this->getUsers();

        if (is_array($users)) {
            $value = [];

            foreach ($users as $User) {
                if ($User->isActive()) {
                    $value[] = $User->getName();
                }
            }

            $params['value'] = implode(', ', $value);
        }

        return new View($params);
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
        $userIds       = [];

        if (is_numeric($value)) {
            $userIds = [(int)$value];
        } elseif (is_string($value)) {
            // Check if string is username
            try {
                $User      = QUI::getUsers()->getUserByName($value);
                $userIds[] = $User->getId();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);

                // If string is no username -> assume it is JSON with user IDs
                $userIds = json_decode($value, true);

                // Check if string was username
                if (json_last_error() !== \JSON_ERROR_NONE) {
                    $userIds = [];
                }
            }
        } elseif (is_array($value)) {
            $userIds = $value;
        }

        if (empty($userIds)) {
            return;
        }

        if (count($userIds) > 1 && !$multipleUsers) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.grouplist.user.limit.reached',
                [
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle()
                ]
            ]);
        }

//        if (empty($userIds)) {
//            throw new QUI\ERP\Products\Field\Exception(array(
//                'quiqqer/products',
//                'exception.field.invalid',
//                array(
//                    'fieldId'    => $this->getId(),
//                    'fieldTitle' => $this->getTitle()
//                )
//            ));
//        }

        $isUserInGroups = function ($userGroups) use ($groupIds) {
            if (empty($groupIds)) {
                return true;
            }

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
                    throw new QUI\ERP\Products\Field\Exception([
                        'quiqqer/products',
                        'exception.field.grouplist.invalid.userId'
                    ]);
                }

                $User       = QUI::getUsers()->get($userId);
                $userGroups = $User->getGroups(false);

                if (!$isUserInGroups($userGroups)) {
                    throw new QUI\ERP\Products\Field\Exception([
                        'quiqqer/products',
                        'exception.field.grouplist.user.not.in.group',
                        [
                            'userId'   => $User->getId(),
                            'username' => $User->getUsername(),
                            'groups'   => implode(',', $groupIds)
                        ]
                    ]);
                }
            }
        } catch (QUI\Exception $Exception) {
            throw new QUI\ERP\Products\Field\Exception([
                'quiqqer/products',
                'exception.field.unexptected.error',
                [
                    'fieldId'    => $this->getId(),
                    'fieldTitle' => $this->getTitle(),
                    'errorMsg'   => $Exception->getMessage()
                ]
            ]);
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
        $userIds       = [];
        $result        = [];

        if (is_numeric($value)) {
            $userIds = [(int)$value];
        } elseif (is_string($value)) {
            // Check if string is username
            try {
                $User      = QUI::getUsers()->getUserByName($value);
                $userIds[] = $User->getId();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);

                // If string is no username -> assume it is JSON with user IDs
                $userIds = json_decode($value, true);

                // Check if string was username
                if (json_last_error() !== \JSON_ERROR_NONE) {
                    $userIds = [];
                }
            }
        } elseif (is_array($value)) {
            $userIds = $value;
        }

        if (count($userIds) > 1 && !$multipleUsers) {
            return [];
        }

        if (empty($userIds)) {
            return [];
        }

        $isUserInGroups = function ($userGroups) use ($groupIds) {
            if (empty($groupIds)) {
                return true;
            }

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
            return [];
        }

        return $result;
    }

    /**
     * Return value for use in product search cache
     *
     * @param QUI\Locale $Locale
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getSearchCacheValue($Locale = null)
    {
        if ($this->isEmpty()) {
            return null;
        }

        $userIds      = $this->getValue();
        $searchValues = [];

        foreach ($userIds as $userId) {
            $searchValues[] = QUI::getUsers()->get($userId)->getName();
        }

        if (count($searchValues) === 1) {
            return $searchValues[0];
        }

        return ',' . implode(',', $searchValues) . ',';
    }

    /**
     * Return all users IDs from the groups assigned to this fields
     *
     * @return int[]
     */
    public function getUserIds()
    {
        $groups = $this->getGroups();
        $result = [];

        /* @var $Group QUI\Groups\Group */
        /* @var $User QUI\Users\User */
        foreach ($groups as $Group) {
            $users = $Group->getUsers();

            foreach ($users as $User) {
                if (is_array($User)) {
                    $result[] = (int)$User['id'];
                }
            }
        }

        return $result;
    }

    /**
     * Return all users in from the groups
     *
     * @return QUI\Interfaces\Users\User[]
     */
    public function getUsers()
    {
        $result = [];

        foreach ($this->getUserIds() as $userId) {
            try {
                $result[$userId] = QUI::getUsers()->get($userId);
            } catch (QUI\Exception $Exception) {
                continue;
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
        $result   = [];

        if (!is_array($groupIds)) {
            return $result;
        }

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
        return [
            Search::SEARCHTYPE_TEXT,
            Search::SEARCHTYPE_SELECTSINGLE,
            Search::SEARCHTYPE_INPUTSELECTSINGLE,
            Search::SEARCHTYPE_SELECTMULTI,
            Search::SEARCHTYPE_HASVALUE,
        ];
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

    /**
     * Return the value in dependence of a locale (language)
     *
     * @param QUI\Locale $Locale (optional)
     * @return array|string
     */
    public function getValueByLocale($Locale = null)
    {
        $Users = QUI::getUsers();

        /**
         * @param QUI\Users\User $User
         * @return string
         */
        $getName = function ($User) {
            $parts = [];

            if (!empty($User->getAttribute('firstname'))) {
                $parts[] = $User->getAttribute('firstname');
            }

            if (!empty($User->getAttribute('lastname'))) {
                $parts[] = $User->getAttribute('lastname');
            }

            if (empty($parts)) {
                return $User->getUsername();
            }

            return implode(' ', $parts);
        };

        if (count($this->value) === 1) {
            try {
                return $getName($Users->get($this->value[0]));
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                return '';
            }
        }

        $values = [];

        foreach ($this->value as $userId) {
            try {
                $values[] = $getName($Users->get($userId));
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $values;
    }
}
