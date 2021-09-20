<?php

use \QUI\ERP\Products;
use \QUI\ERP\Products\Controls\Category\ProductList;

use \QUI\System\Log;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

if (!isset($_REQUEST['_url'])) {
    $_REQUEST['_url'] = '';
}

$_REQUEST['_url'] = \ltrim($_REQUEST['_url'], '/'); // nginx fix
$_REQUEST['_url'] = \urldecode($_REQUEST['_url']);

$siteUrl = $Site->getLocation();
$url     = $_REQUEST['_url'];
$url     = \pathinfo($url);

// fallback url for a product, with NO category
// this should never happen and is a configuration error
if (\strpos(QUI::getRequest()->getPathInfo(), '_p/') !== false) {
    $_REQUEST['_url'] = QUI::getRequest()->getPathInfo();

    if (\strlen(URL_DIR) == 1) {
        $_REQUEST['_url'] = \ltrim($_REQUEST['_url'], URL_DIR);
    } else {
        $from             = '/'.\preg_quote(URL_DIR, '/').'/';
        $_REQUEST['_url'] = \preg_replace($from, '', $_REQUEST['_url'], 1);
    }

    $siteUrl = '';

    $_REQUEST['_url'] = \urldecode($_REQUEST['_url']); // nginx fix
    $url              = $_REQUEST['_url'];
    $url              = \pathinfo($url);
}

// category menu
$searchParentCategorySite = function () use ($Site) {
    $Parent = true;

    while ($Parent) {
        if ($Site->getParent()
            && $Site->getParent()->getAttribute('type') != 'quiqqer/products:types/category'
        ) {
            return $Site;
        }

        $Site = $Site->getParent();

        if (!$Site) {
            break;
        }
    }

    return $Site;
};

$CategoryMenu = new QUI\ERP\Products\Controls\Category\Menu([
    'Site' => $searchParentCategorySite(),
]);

$Engine->assign([
    'showFilter'   => $Site->getAttribute('quiqqer.products.settings.showFilterLeft'),
    'CategoryMenu' => $CategoryMenu,
]);

if ($siteUrl != $_REQUEST['_url'] || isset($_GET['variant']) || isset($_GET['p'])) {
    /**
     * PRODUCT
     */
    $baseName = \str_replace(
        QUI\Rewrite::getDefaultSuffix(),
        '',
        $url['basename']
    );

    $parts = \explode(QUI\Rewrite::URL_PARAM_SEPARATOR, $baseName);
    $refNo = \array_pop($parts);

    if (!empty($_GET['p'])) {
        $refNo = (int)$_GET['p'];
    }

    if (!empty($_GET['variant'])) {
        $refNo = (int)$_GET['variant'];
    }

    $Product = null;
    $Output  = new QUI\Output();
    $Locale  = QUI::getLocale();

    // get by url field
    try {
        $categoryId = $Site->getAttribute('quiqqer.products.settings.categoryId');
        $Product    = Products\Handler\Products::getProductByUrl($refNo, $categoryId);
    } catch (QUI\Exception $Exception) {
        try {
            if (\is_numeric($refNo)) {
                $Product = Products\Handler\Products::getProduct($refNo);
            }
        } catch (QUI\Exception $Exception) {
            Log::addDebug('Products::getProductByUrl :: '.$Exception->getMessage());
        }
    }

    try {
        // get url by id
        if ($Product === null) {
            $refNo   = (int)$refNo;
            $Product = Products\Handler\Products::getProduct($refNo);
        }

        // render product
        $Product->getView();
        $productUrl = \urldecode($Product->getUrl($Project));

        // set canonical always to the parent
        if ($Product instanceof Products\Product\Types\VariantChild) {
            $Site->setAttribute('canonical', $Product->getParent()->getUrl($Project));
        }

        // if product url is with lang flag /en/
        if (\strpos($productUrl, '/', 1) === 3 && strpos($productUrl, '/_p/') === false) {
            $productUrl = \mb_substr($productUrl, 3);
        }

        // forwarding, if the product has a new URL
        // can happen if the product was previously in "all products".
        if ($productUrl != URL_DIR.$_REQUEST['_url']) {
            $urlencoded = \urlencode($productUrl);
            $urlencoded = \str_replace('%2F', '/', $urlencoded);

            $Redirect = new RedirectResponse($urlencoded);
            $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

            echo $Redirect->getContent();
            $Redirect->send();
            exit;
        }

        $CategoryMenu->setAttribute('disableCheckboxes', true);
        $CategoryMenu->setAttribute('breadcrumb', true);

        $Engine->assign([
            'Product'    => new Products\Controls\Products\Product([
                'Product' => $Product
            ]),
            'categoryId' => $Product->getCategory()->getId()
        ]);

        // set site data
        $Site->setAttribute('nocache', true);
        $Site->setAttribute('content-header', false);
        $Site->setAttribute('meta.seotitle', $Product->getTitle($Locale));
        $Site->setAttribute('meta.description', $Product->getDescription($Locale));
        $Site->setAttribute('quiqqer.meta.site.title', false);

        $Keywords = $Product->getField(Products\Handler\Fields::FIELD_KEYWORDS);
        $keywords = $Keywords->getValueByLocale($Locale);

        $Site->setAttribute('meta.keywords', $keywords);

        // language links
        $languages = $Project->getLanguages();

        foreach ($languages as $language) {
            try {
                $LanguageProject = QUI::getProject(
                    $Project->getName(),
                    $language
                );

                $Site->setAttribute(
                    $language.'-link',
                    $Product->getUrlRewrittenWithHost($LanguageProject)
                );
            } catch (QUI\Exception $Exception) {
                Log::writeDebugException($Exception);
            }
        }

        \define('QUIQQER_ERP_IS_PRODUCT', true);
    } catch (QUI\Permissions\Exception $Exception) {
        Log::writeDebugException($Exception);

        $url = $Output->getSiteUrl([
            'site' => $Site,
        ]);

        $Redirect = new RedirectResponse($url);
        $Redirect->setStatusCode(Response::HTTP_FORBIDDEN);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    } catch (QUI\Exception $Exception) {
        Log::writeException($Exception, Log::LEVEL_NOTICE);

        $url = $Output->getSiteUrl([
            'site' => $Site,
        ]);

        $Redirect = new RedirectResponse($url);
        $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

        QUI::getEvents()->fireEvent('errorHeaderShowBefore', [Response::HTTP_NOT_FOUND, $_REQUEST['_url']]);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    }
} else {
    /**
     * CATEGORY
     */
    $ProductList = new ProductList([
        'categoryId'           => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'hideEmptyProductList' => true,
        'categoryStartNumber'  => $Site->getAttribute('quiqqer.products.settings.categoryStartNumber'),
        'showCategories'       => $Site->getAttribute('quiqqer.products.settings.showCategories'),
        'categoryView'         => $Site->getAttribute('quiqqer.products.settings.categoryDisplay'),
        'categoryPos'          => $Site->getAttribute('quiqqer.products.settings.categoryPos'),
        'searchParams'         => Products\Search\Utils::getSearchParameterFromRequest(),
        'autoload'             => 1,
        'autoloadAfter'        => $Site->getAttribute('quiqqer.products.settings.autoloadAfter'),
        'productLoadNumber'    => $Site->getAttribute('quiqqer.products.settings.productLoadNumber'),
        'view'                 => Products\Search\Utils::getViewParameterFromRequest(),
    ]);

    $filterList = $ProductList->getFilter();
    $fields     = Products\Utils\Sortables::getSortableFieldsForSite($Site);

    foreach ($fields as $fieldId) {
        if (\strpos($fieldId, 'S') === 0) {
            $title = QUI::getLocale()->get('quiqqer/products', 'sortable.'.\mb_substr($fieldId, 1));

            $ProductList->addSort(
                $title.' '.QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                $fieldId.' ASC'
            );

            $ProductList->addSort(
                $title.' '.QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                $fieldId.' DESC'
            );

            continue;
        }

        if (\strpos($fieldId, 'F') === 0) {
            try {
                $fieldId = str_replace('F', '', $fieldId);

                $Field = Products\Handler\Fields::getField((int)$fieldId);
                $title = $Field->getTitle();

                $ProductList->addSort(
                    $title.' '.QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                    'F'.$fieldId.' ASC'
                );

                $ProductList->addSort(
                    $title.' '.QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                    'F'.$fieldId.' DESC'
                );
            } catch (QUI\Exception $Exception) {
            }
        }
    }

    if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
        $ProductList->setAttribute('showFilter', false);
    }

    if ($CategoryMenu->countChildren() || \count($filterList)) {
        $ProductList->setAttribute('forceMobileFilter', true);
    }

    $Engine->assign([
        'categoryId'   => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'ProductList'  => $ProductList,
        'CategoryMenu' => $CategoryMenu,
        'filter'       => $filterList,
    ]);
}
