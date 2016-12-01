<?php

use QUI\ERP\Products;
use QUI\System\Log;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

$siteUrl = $Site->getLocation();
$url     = $_REQUEST['_url'];
$url     = pathinfo($url);

// fallback url for a product, with NO category
// this should never happen and is a configuration error
if (strpos(QUI::getRequest()->getPathInfo(), '_p/') !== false) {
    $_REQUEST['_url'] = QUI::getRequest()->getPathInfo();

    if (strlen(URL_DIR) == 1) {
        $_REQUEST['_url'] = ltrim($_REQUEST['_url'], URL_DIR);
    } else {
        $from             = '/' . preg_quote(URL_DIR, '/') . '/';
        $_REQUEST['_url'] = preg_replace($from, '', $_REQUEST['_url'], 1);
    }

    $siteUrl = '';

    $url = $_REQUEST['_url'];
    $url = pathinfo($url);
}

// check product url
if ($siteUrl != $_REQUEST['_url']) {
    $baseName = str_replace(
        QUI\Rewrite::getDefaultSuffix(),
        '',
        $url['basename']
    );

    $parts = explode(QUI\Rewrite::URL_PARAM_SEPERATOR, $baseName);
    $refNo = array_pop($parts);
    $refNo = (int)$refNo;

    try {
        $Product = Products\Handler\Products::getProduct($refNo);
        $Product->getView();

        $productUrl = urldecode($Product->getUrl());

        // weiterleitung, falls das produkt eine neue URL hat
        // kann passieren, wenn das produkt vorher in "alle produkte" war

        if ($productUrl != URL_DIR . $_REQUEST['_url']) {
            $Redirect = new RedirectResponse($productUrl);
            $Redirect->setStatusCode(Response::HTTP_MOVED_PERMANENTLY);

            echo $Redirect->getContent();
            $Redirect->send();
            exit;
        }

        $Engine->assign(array(
            'Product' => new Products\Controls\Products\Product(array(
                'Product' => $Product
            ))
        ));

        $Site->setAttribute('content-header', false);

        define('QUIQQER_ERP_IS_PRODUCT', true);
    } catch (QUI\Permissions\Exception $Exception) {
        $url = QUI::getRewrite()->getUrlFromSite(array(
            'site' => $Site
        ));

        $Redirect = new RedirectResponse($url);
        $Redirect->setStatusCode(Response::HTTP_FORBIDDEN);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    } catch (QUI\Exception $Exception) {
        Log::writeException($Exception, Log::LEVEL_NOTICE);

        $url = QUI::getRewrite()->getUrlFromSite(array(
            'site' => $Site
        ));

        $Redirect = new RedirectResponse($url);
        $Redirect->setStatusCode(Response::HTTP_NOT_FOUND);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    }
} else {
    $searchParams = array();
    $search       = QUI::getRequest()->get('search');
    $fields       = QUI::getRequest()->get('f');
    $tags         = QUI::getRequest()->get('t');
    $sortBy       = QUI::getRequest()->get('sortBy');
    $sortOn       = QUI::getRequest()->get('sortOn');

    $view = QUI::getRequest()->get('v');

    $searchParams = array_filter(array(
        'freetext' => $search,
        'fields'   => $fields,
        'tags'     => $tags,
        'sortBy'   => $sortBy,
        'sortOn'   => $sortOn
    ));

    if (isset($searchParams['fields'])) {
        $searchParams['fields'] = json_decode($searchParams['fields'], true);

        if (is_null($searchParams['fields'])) {
            unset($searchParams['fields']);
        }
    }

    if (isset($searchParams['tags'])) {
        $searchParams['tags'] = explode(',', $searchParams['tags']);
    }

    $ProductList = new Products\Controls\Category\ProductList(array(
        'categoryId'           => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'hideEmptyProductList' => true,
        'categoryStartNumber'  => $Site->getAttribute('quiqqer.products.settings.categoryStartNumber'),
        'categoryView'         => $Site->getAttribute('quiqqer.products.settings.categoryDisplay'),
        'searchParams'         => $searchParams,
        'autoload'             => false
    ));

    $filterList = $ProductList->getFilter();

    foreach ($filterList as $filter) {
        if (!is_array($filter)) {
            /* @var $filter Products\Field\Field */
            $title = $filter->getTitle();
            $id    = $filter->getId();
        } else {
            $title = $filter['title'];
            $id    = $filter['id'];
        }

        $ProductList->addSort(
            $title . ' aufsteigend',
            'F' . $id . ' ASC'
        );

        $ProductList->addSort(
            $title . ' absteigend',
            'F' . $id . ' DESC'
        );
    }

    if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
        $ProductList->setAttribute('showFilter', false);
    }

    // search parent category site
    $searchParentCategorySite = function () use ($Site) {
        $Parent = true;

        while ($Parent) {
            if ($Site->getParent()->getAttribute('type') != 'quiqqer/products:types/category') {
                return $Site;
            }

            $Site = $Site->getParent();
        }

        return $Site;
    };

    $Engine->assign(array(
        'ProductList'        => $ProductList,
        'ParentCategorySite' => $searchParentCategorySite()
    ));
}
