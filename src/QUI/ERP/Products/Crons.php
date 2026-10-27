<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */

namespace QUI\ERP\Products;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Quality\TranslationCheck;
use QUI\ERP\Products\Utils\Tables;
use QUI\Exception;
use QUI\System\Log;

use function class_exists;
use function count;
use function ini_get;
use function set_time_limit;

/**
 * Class EventHandling
 */
class Crons
{
    private const QUALITY_REPORT_LIMIT = 100;

    /**
     * Check product quality and send a bounded report only when issues were found.
     *
     * @param array<string, mixed> $params
     * @throws \Exception
     */
    public static function productQualityCheck(array $params = []): void
    {
        $notification = self::getQualityNotificationSettings($params);
        $languages = self::getQualityCheckLanguages($params);
        $Check = new TranslationCheck();
        $Locale = QUI::getLocale();
        $details = [];
        $issueCount = 0;
        $checked = 0;
        $lastId = 0;

        do {
            $Query = QUI::getQueryBuilder();
            $ids = $Query
                ->select('id')
                ->from(QUI\Utils\Doctrine::quoteIdentifier(Tables::getProductTableName()))
                ->where('id > :lastId')
                ->setParameter('lastId', $lastId)
                ->orderBy('id', 'ASC')
                ->setMaxResults(100)
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($ids as $id) {
                $lastId = (int)$id;

                try {
                    $Product = Products::getNewProductInstance($lastId);
                    $issues = $Check->check($Product, $languages);
                    $checked++;

                    foreach ($issues as $issue) {
                        $issueCount++;

                        if (count($details) < self::QUALITY_REPORT_LIMIT) {
                            $details[] = $Locale->get('quiqqer/products', 'cron.productQualityCheck.missing', [
                                'productId' => $lastId,
                                'fieldId' => $issue['fieldId'],
                                'fieldTitle' => $issue['fieldTitle'],
                                'languages' => implode(', ', $issue['languages'])
                            ]);
                        }
                    }
                } catch (\Throwable $Exception) {
                    Log::writeException($Exception);
                    $issueCount++;

                    if (count($details) < self::QUALITY_REPORT_LIMIT) {
                        $details[] = $Locale->get('quiqqer/products', 'cron.productQualityCheck.failed', [
                            'productId' => $lastId
                        ]);
                    }
                } finally {
                    Products::cleanProductInstanceMemCache($lastId);
                }
            }
        } while (count($ids) === 100);

        if ($issueCount === 0) {
            return;
        }

        $summary = $Locale->get('quiqqer/products', 'cron.productQualityCheck.summary', [
            'checked' => $checked,
            'issues' => $issueCount,
            'languages' => implode(', ', $languages)
        ]);

        if ($issueCount > count($details)) {
            $details[] = $Locale->get('quiqqer/products', 'cron.productQualityCheck.truncated', [
                'limit' => self::QUALITY_REPORT_LIMIT
            ]);
        }

        $report = $summary . "\n\n" . implode("\n", $details);
        $escapedReport = htmlspecialchars($report, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = '<p>' . nl2br($escapedReport) . '</p>';
        $subject = $Locale->get('quiqqer/products', 'cron.productQualityCheck.title');

        foreach ($notification['users'] as $User) {
            QUI::getMessagesHandler()->sendAttention($User, $body);
        }

        if ($notification['email'] !== '') {
            QUI::getMailManager()->send($notification['email'], $subject, $body);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array{email: string, users: list<QUI\Interfaces\Users\User>}
     * @throws QUI\Exception
     */
    private static function getQualityNotificationSettings(array $params): array
    {
        $mode = $params['notification'] ?? 'email';

        if ($mode === '') {
            $mode = 'email';
        }

        if (!in_array($mode, ['email', 'backend', 'both'], true)) {
            throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidNotification']);
        }

        $email = '';
        $users = [];

        if ($mode === 'email' || $mode === 'both') {
            $email = $params['email'] ?? '';

            if (is_string($email) && trim($email) === '') {
                $email = QUI::conf('mail', 'admin_mail');
            }

            if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidEmail']);
            }

            $email = trim($email);
        }

        if ($mode === 'backend' || $mode === 'both') {
            $recipients = $params['users'] ?? '';

            if (!is_string($recipients)) {
                throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidUsers']);
            }

            if (trim($recipients) === '') {
                $users = array_values(QUI::getUsers()->getUsers([
                    'where' => [
                        'su' => 1
                    ]
                ]));
            } else {
                $ids = array_unique(array_filter(
                    array_map('trim', explode(',', $recipients)),
                    static fn (string $id): bool => $id !== ''
                ));

                foreach ($ids as $id) {
                    $users[] = QUI::getUsers()->get($id);
                }
            }

            if ($users === []) {
                throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidUsers']);
            }

            foreach ($users as $User) {
                if (QUI::getUsers()->isSystemUser($User) || QUI::getUsers()->isNobodyUser($User)) {
                    throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidUsers']);
                }
            }
        }

        return [
            'email' => $email,
            'users' => $users
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return list<string>
     * @throws QUI\Exception
     */
    private static function getQualityCheckLanguages(array $params): array
    {
        $configured = $params['languages'] ?? '';

        if (!is_string($configured)) {
            throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidLanguages']);
        }

        $languages = [];

        if (trim($configured) !== '') {
            $languages = preg_split('/[\s,;]+/', strtolower(trim($configured)), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        } else {
            foreach (QUI::getProjectManager()->getProjects(true) as $Project) {
                $languages = array_merge($languages, $Project->getLanguages());
            }
        }

        if ($languages === []) {
            throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidLanguages']);
        }

        foreach ($languages as $language) {
            if (!is_string($language) || !preg_match('/^[a-z]{2}$/', $language)) {
                throw new QUI\Exception(['quiqqer/products', 'cron.productQualityCheck.invalidLanguages']);
            }
        }

        return array_values(array_unique($languages));
    }

    /**
     * Time for one product to update its cache (seconds)
     */
    const PRODUCT_CACHE_UPDATE_TIME = 10;

    /**
     * Updates cache values for all products
     * @throws Exception
     */
    public static function updateProductCache(): void
    {
        // global watcher disable
        if (class_exists('\QUI\Watcher')) {
            QUI\Watcher::$globalWatcherDisable = true;
        }

        // clear search cache
        QUI\ERP\Products\Search\Cache::clear();

        $ids = Products::getProductIds();

        foreach ($ids as $id) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getNewProductInstance($id);

                $t = microtime(true);
                $Product->updateCache();
                $Product->buildCache();
                Log::addDebug("update cache for product #" . $id . " | time: " . (microtime(true) - $t));
            } catch (Exception $Exception) {
                Log::writeException($Exception);

                Log::addWarning(
                    'cron :: updateProductCache() :: Could not update cache'
                    . ' for Product #' . $id . ' -> '
                    . $Exception->getMessage()
                );
            }
        }

        // reset time limit
        set_time_limit((int)ini_get('max_execution_time'));
    }

    /**
     * Go through all images and build the image cache
     * So the first call is faster
     *
     * @throws Exception
     */
    public static function generateCacheImagesOfProducts(): void
    {
        $ids = Products::getProductIds();
        $count = count($ids);
        $current = 0;

        foreach ($ids as $id) {
            QUI::getEvents()->fireEvent('generateCacheImagesOfProductsBegin', [$id, $current, $count]);

            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product = Products::getNewProductInstance($id);

                if ($Product instanceof QUI\ERP\Products\Product\Types\VariantChild) {
                    $current++;
                    continue;
                }

                $Image = $Product->getImage();

                $Image->createCache();

                $Image->createSizeCache(400); // product gallery
                $Image->createSizeCache(500); // product slider
                $Image->createSizeCache(100, 200); // product gallery. preview
            } catch (\Exception $Exception) {
                Log::addNotice($Exception->getMessage(), [
                    'stack' => $Exception->getTraceAsString(),
                    'productId' => $id,
                    'cron' => 'generateCacheImagesOfProducts'
                ]);
            }

            QUI::getEvents()->fireEvent('generateCacheImagesOfProductsEnd', [$id, $current, $count]);

            $current++;
        }

        // reset time limit
        set_time_limit((int)ini_get('max_execution_time'));
    }
}
