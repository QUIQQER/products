<?php

use QUI\ERP\Products;
use QUI\System\Log;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\HttpFoundation\Response;

$siteUrl = $Site->getLocation();
$url     = $_REQUEST['_url'];
$url     = pathinfo($url);

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
        'searchParams'         => $searchParams
    ));

    if ($Site->getAttribute('quiqqer.products.settings.showFilterLeft')) {
        $ProductList->setAttribute('showFilter', false);
    }

    $Engine->assign(array(
        'ProductList' => $ProductList
    ));
}
