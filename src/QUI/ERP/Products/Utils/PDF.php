<?php

/**
 * This file contains QUI\ERP\Products\Utils\PDF
 */
namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class PDF
 * Helper for PDF generation - watchlist, accounting etc
 *
 * @package QUI\ERP\Products\Utils
 */
class PDF
{
    /**
     * Return the global product pdf header
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return string
     */
    public static function getHeader($Project = null)
    {
        return self::getEngine($Project)->fetch(OPT_DIR . 'quiqqer/products/template/pdf/header.html');
    }

    /**
     * Return the global product pdf footer
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return string
     */
    public static function getFooter($Project = null)
    {
        return self::getEngine($Project)->fetch(OPT_DIR . 'quiqqer/products/template/pdf/footer.html');
    }

    /**
     * Return template engine
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return QUI\Interfaces\Template\EngineInterface
     */
    protected static function getEngine($Project = null)
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (QUI\Projects\Project::class != get_class($Project)) {
            $Project = QUI::getRewrite()->getProject();
        }

        $Logo = $Project->getMedia()->getLogoImage();

        $Engine->assign(array(
            'Project' => $Project,
            'Logo'    => $Logo,
            'logo'    => $Logo->getFullPath()
        ));

        return $Engine;
    }
}
