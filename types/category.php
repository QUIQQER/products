<?php

/**
 * This file contains the category site type
 *
 * @var QUI\Projects\Project $Project
 * @var QUI\Projects\Site $Site
 * @var QUI\Interfaces\Template\EngineInterface $Engine
 * @var QUI\Template $Template
 **/

use QUI\ERP\Products;
use QUI\ERP\Products\Controls\Category\ProductList;
use QUI\ERP\Products\Handler\Fields;
use QUI\System\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

if (!isset($_REQUEST['_url'])) {
    $_REQUEST['_url'] = '';
}

$_REQUEST['_url'] = ltrim($_REQUEST['_url'], '/'); // nginx fix
$_REQUEST['_url'] = urldecode($_REQUEST['_url']);

$siteUrl = $Site->getLocation();
$url = $_REQUEST['_url'];
$url = pathinfo($url);

// fallback url for a product, with NO category
// this should never happen and is a configuration error
if (str_contains(QUI::getRequest()->getPathInfo(), '_p/')) {
    $_REQUEST['_url'] = QUI::getRequest()->getPathInfo();

    if (strlen(URL_DIR) == 1) {
        $_REQUEST['_url'] = ltrim($_REQUEST['_url'], URL_DIR);
    } else {
        $from = '/' . preg_quote(URL_DIR, '/') . '/';
        $_REQUEST['_url'] = preg_replace($from, '', $_REQUEST['_url'], 1);
    }

    $siteUrl = '';

    $_REQUEST['_url'] = urldecode($_REQUEST['_url']); // nginx fix
    $url = $_REQUEST['_url'];
    $url = pathinfo($url);
}

// category menu
$searchParentCategorySite = function () use ($Site) {
    $Parent = true;

    while ($Parent) {
        if (
            $Site->getParent()
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
    'showFilter' => $Site->getAttribute('quiqqer.products.settings.showFilterLeft'),
    'CategoryMenu' => $CategoryMenu,
]);

if ($siteUrl != $_REQUEST['_url'] || isset($_GET['variant']) || isset($_GET['p'])) {
    /**
     * PRODUCT
     */
    $baseName = str_replace(
        QUI\Rewrite::getDefaultSuffix(),
        '',
        $url['basename']
    );

    $parts = explode(QUI\Rewrite::URL_PARAM_SEPARATOR, $baseName);
    $refNo = array_pop($parts);

    if (!empty($_GET['p'])) {
        $refNo = (int)$_GET['p'];
    }

    if (!empty($_GET['variant'])) {
        $refNo = (int)$_GET['variant'];
    }

    $Product = null;
    $Output = new QUI\Output();
    $Locale = QUI::getLocale();

    // get by url field
    try {
        $categoryId = (int)$Site->getAttribute('quiqqer.products.settings.categoryId');
        $Product = Products\Handler\Products::getProductByUrl($refNo, $categoryId);
    } catch (QUI\Exception $Exception) {
        try {
            if (is_numeric($refNo)) {
                $Product = Products\Handler\Products::getProduct($refNo);
            }
        } catch (QUI\Exception $Exception) {
            Log::addDebug('Products::getProductByUrl :: ' . $Exception->getMessage());
        }
    }

    try {
        // get url by id
        if ($Product === null) {
            $refNo = (int)$refNo;
            $Product = Products\Handler\Products::getProduct($refNo);
        }

        // render product
        $Product->getView();
        $productUrl = urldecode($Product->getUrl($Project));

        // set canonical always to the parent
        if ($Product instanceof Products\Product\Types\VariantChild) {
            $Site->setAttribute('canonical', $Product->getParent()->getUrl($Project));
        }

        // if product url is with lang flag /en/
        if (strpos($productUrl, '/', 1) === 3 && !str_contains($productUrl, '/_p/')) {
            $productUrl = mb_substr($productUrl, 3);
        }

        // forwarding, if the product has a new URL
        // can happen if the product was previously in "all products".
        if ($productUrl != URL_DIR . $_REQUEST['_url']) {
            $urlencoded = urlencode($productUrl);
            $urlencoded = str_replace('%2F', '/', $urlencoded);

            $Redirect = new RedirectResponse($urlencoded);
            $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

            echo $Redirect->getContent();
            $Redirect->send();
            exit;
        }

        $CategoryMenu->setAttribute('disableCheckboxes', true);
        $CategoryMenu->setAttribute('breadcrumb', true);

        $Engine->assign([
            'Product' => new Products\Controls\Products\Product([
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
        $Site->setAttribute('quiqqer.socialshare.image', $Product->getImage()->getUrl(true));
        $Site->setAttribute('quiqqer.socialshare.type', 'product');
        $Site->setAttribute('quiqqer.socialshare.url', $Product->getUrlRewrittenWithHost());

        // description
        $description = $Product->getDescription($Locale);

        if (empty($description)) {
            $description = $Product->getContent($Locale);
            $description = strip_tags($description);
            $description = trim($description);
            $description = str_replace("\n", '', $description);
            $description = str_replace("&nbsp;", ' ', $description);
            $description = mb_substr($description, 0, 150);
        }

        if (!$Site->getAttribute('meta.description')) {
            $Site->setAttribute('meta.description', $description);
        }

        $Site->setAttribute('quiqqer.socialshare.description', $description);

        // check seo title
        $seoTitle = $Product->getField(Fields::FIELD_SEO_TITLE)->getValueByLocale(QUI::getLocale());
        $seoDescription = $Product->getField(Fields::FIELD_SEO_DESCRIPTION)->getValueByLocale(QUI::getLocale());

        if ($Product instanceof Products\Product\Types\VariantParent) {
            try {
                $DefaultChild = $Product->getDefaultVariant();
                $seoTitle = $DefaultChild->getField(Fields::FIELD_SEO_TITLE)
                    ->getValueByLocale(QUI::getLocale());

                $seoDescription = $DefaultChild->getField(Fields::FIELD_SEO_DESCRIPTION)
                    ->getValueByLocale(QUI::getLocale());
            } catch (QUI\Exception) {
            }
        }

        if (!empty($seoTitle)) {
            $Site->setAttribute('meta.seotitle', $seoTitle);
        }

        if (!empty($seoDescription)) {
            $Site->setAttribute('meta.description', $seoDescription);
        }


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
                    $language . '-link',
                    $Product->getUrlRewrittenWithHost($LanguageProject)
                );
            } catch (QUI\Exception $Exception) {
                Log::writeDebugException($Exception);
            }
        }

        define('QUIQQER_ERP_IS_PRODUCT', true);
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

    switch ($Site->getAttribute('quiqqer.products.settings.categoryProductSearchType')) {
        case 'must_have_all_categories':
            $categoryProductSearchType = 'AND';
            break;

        default:
        case 'must_have_only_one_category':
            $categoryProductSearchType = 'OR';
            break;
    }

    $ProductList = new ProductList([
        'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'hideEmptyProductList' => true,
        'categoryStartNumber' => $Site->getAttribute('quiqqer.products.settings.categoryStartNumber'),
        'showCategories' => $Site->getAttribute('quiqqer.products.settings.showCategories'),
        'categoryView' => $Site->getAttribute('quiqqer.products.settings.categoryDisplay'),
        'categoryPos' => $Site->getAttribute('quiqqer.products.settings.categoryPos'),
        'categoryProductSearchType' => $categoryProductSearchType,
        'searchParams' => Products\Search\Utils::getSearchParameterFromRequest(),
        'autoload' => 1,
        'autoloadAfter' => $Site->getAttribute('quiqqer.products.settings.autoloadAfter'),
        'productLoadNumber' => $Site->getAttribute('quiqqer.products.settings.productLoadNumber'),
        'openProductMode' => $Site->getAttribute('quiqqer.products.settings.openProductMode'),
        'view' => Products\Search\Utils::getViewParameterFromRequest(),
    ]);

    $filterList = $ProductList->getFilter();
    $fields = Products\Utils\Sortables::getSortableFieldsForSite($Site);

    foreach ($fields as $fieldId) {
        if (str_starts_with($fieldId, 'S')) {
            $title = QUI::getLocale()->get('quiqqer/products', 'sortable.' . mb_substr($fieldId, 1));

            $ProductList->addSort(
                $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                $fieldId . ' ASC'
            );

            $ProductList->addSort(
                $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                $fieldId . ' DESC'
            );

            continue;
        }

        if (str_starts_with($fieldId, 'F')) {
            try {
                $fieldId = str_replace('F', '', $fieldId);

                $Field = Products\Handler\Fields::getField((int)$fieldId);
                $title = $Field->getTitle();

                $ProductList->addSort(
                    $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortASC'),
                    'F' . $fieldId . ' ASC'
                );

                $ProductList->addSort(
                    $title . ' ' . QUI::getLocale()->get('quiqqer/products', 'sortDESC'),
                    'F' . $fieldId . ' DESC'
                );
            } catch (QUI\Exception $Exception) {
            }
        }
    }

    if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
        $ProductList->setAttribute('showFilter', false);
    }

    if ($CategoryMenu->countChildren() || count($filterList)) {
        $ProductList->setAttribute('forceMobileFilter', true);
    }

    $hasFilter = (QUI::getRequest()->get('search')
        || QUI::getRequest()->get('f')
        || QUI::getRequest()->get('t')
        || QUI::getRequest()->get('sortBy')
        || QUI::getRequest()->get('sortOn')
    );

    if ($hasFilter) {
        $Engine->getCanonical()->considerGetParameterOn();
    }

    if ($hasFilter && !$ProductList->count()) {
        // keine produkte → weiterleitung zu main
        $Redirect = new RedirectResponse($Site->getUrlRewritten());
        $Redirect->setStatusCode(Response::HTTP_SEE_OTHER);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    }

    $Engine->assign([
        'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'ProductList' => $ProductList,
        'CategoryMenu' => $CategoryMenu,
        'filter' => $filterList
    ]);
}
