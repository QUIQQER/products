<?php

namespace QUI\ERP\Products\Controls\ManufacturerList;

use Exception;
use QUI;
use QUI\ERP\Products\Handler\Manufacturers as ManufacturersHandler;

use function count;
use function dirname;
use function strnatcmp;
use function usort;

/**
 * Class ManufacturerList
 *
 * Displays a list of all manufacturers
 */
class ManufacturerList extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'class' => 'quiqqer-product-list',
            'categoryId' => false,
            'data-qui' => 'package/quiqqer/products/bin/controls/frontend/manufacturerList/ManufacturerList',
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/ManufacturerList.css');

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @throws Exception
     * @see \QUI\Control::create()
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Config = QUI::getPackage('quiqqer/products')->getConfig();

        // global settings: product autoload after x clicks
        if ($this->getAttribute('autoloadAfter') == '' || !$this->getAttribute('autoloadAfter')) {
            // @todo get setting from site
            $this->setAttribute('autoloadAfter', $Config->get('products', 'autoloadAfter'));
        }

        $this->setAttribute('data-project', $this->getSite()->getProject()->getName());
        $this->setAttribute('data-lang', $this->getSite()->getProject()->getLang());
        $this->setAttribute('data-siteid', $this->getSite()->getId());
        $this->setAttribute('data-autoload', $this->getAttribute('autoload') ? 1 : 0);
        $this->setAttribute('data-autoloadAfter', $this->getAttribute('autoloadAfter'));

        $manufacturers = '';
        $more = false;

        $manufacturerUsers = ManufacturersHandler::getManufacturerUsers(null, 0, true);
        $count = count($manufacturerUsers);

        try {
            if (isset($_REQUEST['sheet'])) {
                $begin = ((int)$_REQUEST['sheet'] - 1) * $this->getMax();
                $start = $this->getNext($begin, $count);
            } else {
                $start = $this->getStart($count);
            }

            $manufacturers = $start['html'];
            $more = $start['more'];
        } catch (QUI\Permissions\Exception $Exception) {
            QUI\System\Log::addNotice(
                $Exception->getMessage(),
                $Exception->getContext()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException(
                $Exception,
                QUI\System\Log::LEVEL_NOTICE
            );
        }

        $Pagination = new QUI\Controls\Navigating\Pagination([
            'count' => $count,
            'Site' => $this->getSite(),
            'showLimit' => false,
            'limit' => $this->getMax(),
            'useAjax' => false,
        ]);

        $Pagination->loadFromRequest();

        $Engine->assign([
            'this' => $this,
            'Pagination' => $Pagination,
            'count' => $count,
            'manufacturers' => $manufacturers,
            'children' => $this->getSite()->getNavigation(),
            'more' => $more,
            'Site' => $this->getSite(),
            'placeholder' => $this->getProject()->getMedia()->getPlaceholder()
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/ManufacturerList.html');
    }

    /**
     * Return the first articles as html array
     *
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     */
    public function getStart(bool|int $count = false): array
    {
        return $this->renderData(0, $this->getMax(), $count);
    }

    /**
     * Return the next articles as html array
     *
     * @param boolean|integer $start - (optional) start position
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     */
    public function getNext(bool|int $start = false, bool|int $count = false): array
    {
        return $this->renderData($start, $this->getMax(), $count);
    }

    /**
     * Render the products data
     *
     * @param boolean|integer $start - (optional) start position
     * @param boolean|integer $max - (optional) max children
     * @param boolean|integer $count - (optional) count of the children
     * @return array [html, count, more]
     */
    protected function renderData(bool|int $start, bool|int $max, bool|int $count = false): array
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (!$start) {
            $start = 0;
        }

        $more = true;
        $manufacturerUserIds = [];
        $Users = QUI::getUsers();

        try {
            $userIds = ManufacturersHandler::getManufacturerUserIds(true);

            if (!empty($userIds)) {
                $result = QUI::getDataBase()->fetch([
                    'select' => ['id'],
                    'from' => $Users::table(),
                    'where' => [
                        'id' => [
                            'type' => 'IN',
                            'value' => $userIds
                        ]
                    ],
                    'order' => 'username ASC',
                    'limit' => $start . ',' . $max
                ]);

                foreach ($result as $row) {
                    $manufacturerUserIds[] = $row['id'];
                }

                if ($count === false) {
                    $count = count($userIds);
                }
            }
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_NOTICE);
            $count = 0;
        }

        if ($count === false) {
            $count = 0;
        }

        // sort alphabetically
        usort($manufacturerUserIds, function ($userIdA, $userIdB) {
            /**
             * @var int $userIdA
             * @var int $userIdB
             */
            return strnatcmp(
                ManufacturersHandler::getManufacturerTitle($userIdA),
                ManufacturersHandler::getManufacturerTitle($userIdB)
            );
        });

        if ($start + $max >= $count) {
            $more = false;
        }

        $Engine->assign([
            'this' => $this,
            'manufacturerUsers' => $manufacturerUserIds,
            'count' => $count,
            'more' => $more
        ]);

        $this->addCSSFile(dirname(__FILE__) . '/ManufacturerList.Gallery.css');

        return [
            'html' => $Engine->fetch(dirname(__FILE__) . '/ManufacturerList.Gallery.html'),
            'count' => $count,
            'more' => $more
        ];
    }

    /**
     * Return the max children per row
     *
     * @return int
     */
    protected function getMax(): int
    {
        // settings
        if ($this->getAttribute('productLoadNumber')) {
            return $this->getAttribute('productLoadNumber');
        }

        switch ($this->getAttribute('view')) {
            case 'list':
                return 10;

            case 'detail':
                return 5;
        }

        // default
        return 9;
    }

    /**
     * @return QUI\Interfaces\Projects\Site
     * @throws QUI\Exception
     */
    protected function getSite(): QUI\Interfaces\Projects\Site
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return QUI::getRewrite()->getSite();
    }
}
