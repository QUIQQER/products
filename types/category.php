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

        $Engine->assign(array(
            'Product' => new Products\Controls\Products\Product(array(
                'Product' => $Product
            ))
        ));

        $Site->setAttribute('content-header', false);

    } catch (QUI\Exception $Exception) {
        Log::writeException($Exception, Log::LEVEL_NOTICE);

        $url = QUI::getRewrite()->getUrlFromSite(array(
            'site' => $Site
        ));

        $Redirect = new RedirectResponse($url);
        $Redirect->setStatusCode(Response::HTTP_NOT_FOUND);
        echo $Redirect->getContent();

        $Redirect->send();
    }

} else {
    $Search = new QUI\ERP\Products\Controls\Search\Search(array(
        'Site'      => $Site,
        'data-name' => 'category-search'
    ));

    $ProductList = new Products\Controls\Category\ProductList(array(
        'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId'),
        'Search'     => $Search
    ));

    $Engine->assign(array(
        'ProductList' => $ProductList,
        'Search'      => $Search
    ));
}
