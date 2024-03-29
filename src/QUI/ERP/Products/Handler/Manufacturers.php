<?php

namespace QUI\ERP\Products\Handler;

use QUI;
use QUI\Projects\Media\Utils as QUIMediaUtils;
use QUI\Utils\StringHelper;

/**
 * Class Manufacturers
 *
 * Handler for manufacturer purposes.
 */
class Manufacturers
{
    const SITE_TYPE_MANUFACTURER_LIST = 'quiqqer/products:types/manufacturerList';

    /**
     * @var array
     */
    protected static $manufacturerData = [];

    /**
     * Get QUIQQER user IDs of all manufacturers
     *
     * @param bool $activeOnly (optional) - Only return IDs of active users
     * @return int[]
     */
    public static function getManufacturerUserIds(bool $activeOnly = false)
    {
        try {
            /** @var QUI\ERP\Products\Field\Types\GroupList $ManufacturerField */
            $ManufacturerField = Fields::getField(Fields::FIELD_MANUFACTURER);
            $userIds = $ManufacturerField->getUserIds();

            if (!$activeOnly || empty($userIds)) {
                return $userIds;
            }

            $result = QUI::getDataBase()->fetch([
                'select' => ['id'],
                'from' => QUI\Users\Manager::table(),
                'where' => [
                    'id' => [
                        'type' => 'IN',
                        'value' => $userIds
                    ],
                    'active' => 1
                ]
            ]);

            $userIds = \array_column($result, 'id');

            return \array_map(function ($v) {
                return (int)$v;
            }, $userIds);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return [];
        }
    }

    /**
     * Get QUIQQER users of all manufacturers
     *
     * @param int $limit (optional) - [default: all]
     * @param int $offset (optional) [default: 0]
     * @param bool $activeOnly (optional) - [default: get all users (active and inactive)]
     * @return QUI\Interfaces\Users\User[]
     */
    public static function getManufacturerUsers($limit = null, $offset = 0, $activeOnly = false)
    {
        $users = [];

        try {
            $userIds = self::getManufacturerUserIds($activeOnly);
            $userIds = \array_slice($userIds, $offset, $limit);

            foreach ($userIds as $userId) {
                $users[] = QUI::getUsers()->get($userId);
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $users;
    }

    /**
     * Check if a user is a manufacturer
     *
     * @param int $userId - QUIQQER User ID of manufacturer user
     * @return bool
     */
    public static function isManufacturer(int $userId)
    {
        return \in_array($userId, self::getManufacturerUserIds());
    }

    /**
     * Get title of manufacturer (name)
     *
     * @param int $userId - QUIQQER User ID of manufacturer user
     * @return string
     */
    public static function getManufacturerTitle(int $userId)
    {
        $parts = [];

        try {
            $User = QUI::getUsers()->get($userId);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return '';
        }

        try {
            $StandardAddress = $User->getStandardAddress();
            $company = $StandardAddress->getAttribute('company');

            if (!empty($company)) {
                return $company;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        if (!empty($User->getAttribute('firstname'))) {
            $parts[] = $User->getAttribute('firstname');
        }

        if (!empty($User->getAttribute('lastname'))) {
            $parts[] = $User->getAttribute('lastname');
        }

        if (empty($parts)) {
            return $User->getUsername();
        }

        return \implode(' ', $parts);
    }

    /**
     * Get image of manufacturer
     *
     * @param int $userId - QUIQQER User ID of manufacturer user
     * @return QUI\Projects\Media\Image|false
     */
    public static function getManufacturerImage(int $userId)
    {
        $manufacturer = self::getManufacturerData($userId);

        if (!empty($manufacturer['avatar'])) {
            try {
                return QUIMediaUtils::getMediaItemByUrl($manufacturer['avatar']);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $Image = QUI::getProjectManager()->getStandard()->getMedia()->getPlaceholderImage();

        if (!empty($Image)) {
            return $Image;
        }

        return false;
    }

    /**
     * Get virtual URL for manufacturer product "site"
     *
     * @param int $userId - QUIQQER User ID of manufacturer user
     * @param QUI\Projects\Project $Project (optional) - [default: get project by rewrite]
     *
     * @return string|false - URL or false if not available
     */
    public static function getManufacturerUrl(int $userId, QUI\Projects\Project $Project = null)
    {
        if (empty($Project)) {
            try {
                $Project = QUI::getRewrite()->getProject();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                return false;
            }
        }

        $manufacturerListSites = $Project->getSites([
            'where' => [
                'active' => 1,
                'type' => self::SITE_TYPE_MANUFACTURER_LIST
            ]
        ]);

        if (empty($manufacturerListSites)) {
            return false;
        }

        /** @var QUI\Projects\Site $Site */
        $Site = $manufacturerListSites[0];

        try {
            $manufacturer = self::getManufacturerData($userId);
            return $Site->getUrlRewrittenWithHost() . '/' . $manufacturer['username'];
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }
    }

    /**
     * Get manufacturer data
     *
     * @param int $userId - QUIQQER User ID of manufacturer user
     * @return array
     */
    protected static function getManufacturerData(int $userId)
    {
        if (!empty(self::$manufacturerData[$userId])) {
            return self::$manufacturerData[$userId];
        }

        $result = QUI::getDataBase()->fetch([
            'select' => ['username', 'firstname', 'lastname', 'avatar'],
            'from' => QUI::getUsers()::table(),
            'where' => [
                'id' => $userId
            ]
        ]);

        self::$manufacturerData[$userId] = $result[0];

        return self::$manufacturerData[$userId];
    }

    /**
     * Register virtual URL paths for manufacturer product "sites"
     *
     * @return void
     */
    public static function registerManufacturerUrlPaths()
    {
        // Loop through all projects
        $Projects = QUI::getProjectManager();

        try {
            $projects = $Projects->getProjects();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        $langs = QUI::availableLanguages();

        foreach ($projects as $project) {
            foreach ($langs as $lang) {
                try {
                    $Project = $Projects->getProject($project, $lang);
                    $manufacturerListSites = $Project->getSites([
                        'where' => [
                            'active' => 1,
                            'type' => self::SITE_TYPE_MANUFACTURER_LIST
                        ]
                    ]);

                    /** @var QUI\Projects\Site $Site */
                    foreach ($manufacturerListSites as $Site) {
                        $url = $Site->getLocation();
                        $url = StringHelper::strReplaceFromEnd(QUI\Rewrite::URL_DEFAULT_SUFFIX, '', $url);

                        QUI::getRewrite()->registerPath($url . '/*', $Site);
                    }
                } catch (\Exception $Exception) {
                    // project does probably not exist in given language
                    QUI\System\Log::writeDebugException($Exception);
                }
            }
        }
    }
}
