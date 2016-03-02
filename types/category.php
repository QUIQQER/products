<?php

$siteUrl = $Site->getLocation();

$url = $_REQUEST['_url'];
$url = pathinfo($url);

// check machine url
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
        $Product = QUI\ERP\Products\Handler\Products::getProduct($refNo);

        $Engine->assign(array(
            'Product' => new QUI\ERP\Products\Controls\Products\Product(array(
                'Product' => $Product
            ))
        ));

    } catch (QUI\Exception $Exception) {
        QUI::getRewrite()->showErrorHeader(404);
    }

} else {
    $ProductList = new QUI\ERP\Products\Controls\Category\ProductList(array(
        'categoryId' => $Site->getAttribute('quiqqer.products.settings.categoryId')
    ));

    $Engine->assign(array(
        'ProductList' => $ProductList
    ));
}
