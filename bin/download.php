<?php

/**
 * This file serves files from media folder product fields assigned to specific products.
 */

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);

if (
    empty($_REQUEST['fileId']) ||
    empty($_REQUEST['pid']) ||
    empty($_REQUEST['fieldId'])
) {
    exit;
}

require_once dirname(__FILE__, 4) . '/header.php';

try {
    $Product = \QUI\ERP\Products\Handler\Products::getProduct((int)$_REQUEST['pid']);

    if (!$Product->isActive()) {
        exit;
    }

    if (!$Product->hasField((int)$_REQUEST['fieldId'])) {
        exit;
    }

    $MediaFolderField = $Product->getField((int)$_REQUEST['fieldId']);

    if (!($MediaFolderField instanceof \QUI\ERP\Products\Field\Types\Folder)) {
        exit;
    }

    $QuiqqerMediaFolder = $MediaFolderField->getMediaFolder();

    if (!$QuiqqerMediaFolder) {
        exit;
    }

    $files = $QuiqqerMediaFolder->getFiles([
        'where' => [
            'id' => (int)$_REQUEST['fileId']
        ]
    ]);

    if (empty($files)) {
        exit;
    }

    /** @var \QUI\Projects\Media\File $File */
    $File = $files[0];

    if (!$File->hasPermission('quiqqer.projects.media.view')) {
        exit;
    }

    \QUI\Utils\System\File::send($File->getFullPath());
} catch (\Exception $Exception) {
    QUI\System\Log::addDebug($Exception->getMessage());
    exit;
}
