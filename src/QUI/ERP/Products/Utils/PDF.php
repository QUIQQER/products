<?php

/**
 * This file contains QUI\ERP\Products\Utils\PDF
 */

namespace QUI\ERP\Products\Utils;

use QUI;

/**
 * Class PDF
 * Helper for PDF generation - watchlist, accounting etc
 */
class PDF
{
    /**
     * Return the global product pdf header
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return string
     * @throws QUI\Exception
     */
    public static function getHeader(null | QUI\Projects\Project $Project = null): string
    {
        $Engine = self::getEngine($Project);

        return $Engine->fetch(OPT_DIR . 'quiqqer/products/template/pdf/header.html');
    }

    /**
     * Return the global product pdf footer
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return string
     * @throws QUI\Exception
     */
    public static function getFooter(null | QUI\Projects\Project $Project = null): string
    {
        $Engine = self::getEngine($Project);

        $Engine->assign([
            'Locale' => QUI::getLocale()
        ]);

        return $Engine->fetch(OPT_DIR . 'quiqqer/products/template/pdf/footer.html');
    }

    /**
     * Return template engine
     *
     * @param QUI\Projects\Project|null $Project - optional, Project object
     * @return QUI\Interfaces\Template\EngineInterface
     * @throws QUI\Exception
     */
    protected static function getEngine(null | QUI\Projects\Project $Project = null): QUI\Interfaces\Template\EngineInterface
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        if (empty($Project) || QUI\Projects\Project::class != get_class($Project)) {
            $Project = QUI::getRewrite()->getProject();
        }

        if ($Project === null) {
            throw new QUI\Exception('Project is unavailable.');
        }

        $Logo = $Project->getMedia()->getLogoImage();

        if ($Logo === null) {
            throw new QUI\Exception('Project logo is unavailable.');
        }

        $Engine->assign([
            'Project' => $Project,
            'Logo' => $Logo,
            'logo' => $Logo->getFullPath()
        ]);

        return $Engine;
    }
}
