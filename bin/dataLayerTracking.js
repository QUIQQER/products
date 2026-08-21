window.whenQuiLoaded().then(function() {
    'use strict';

    if (typeof window.qTrack !== 'function') {
        return;
    }

    require([
        'qui/QUI',
        'Ajax'
    ], function(QUI, QUIAjax) {
        function hasItems(data) {
            return data && Array.isArray(data.items) && data.items.length > 0;
        }

        function trackData(eventName, Data) {
            Data.then(function(data) {
                if (hasItems(data)) {
                    window.qTrack('event', eventName, data);
                }
            }).catch(function(error) {
                console.error(error);
            });
        }

        function getProductData(productId) {
            return new Promise(function(resolve, reject) {
                QUIAjax.get(
                    'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductData',
                    resolve,
                    {
                        'package': 'quiqqer/products',
                        productId: productId,
                        onError: reject
                    }
                );
            });
        }

        function getItemListData(Parent) {
            let listId = QUIQQER_SITE.id;
            let listName = document.title;

            if (Parent && typeof Parent.getAttribute === 'function') {
                listId = Parent.getAttribute('itemListId') ||
                    Parent.getAttribute('categoryId') ||
                    listId;
                listName = Parent.getAttribute('itemListName') || listName;
            }

            return {
                item_list_id: String(listId),
                item_list_name: listName
            };
        }

        function trackProductView(productId) {
            trackData('view_item', getProductData(productId));
        }

        function trackProductSelection(Parent, productId) {
            const listData = getItemListData(Parent);

            trackData(
                'select_item',
                getProductData(productId).then(function(productData) {
                    if (!hasItems(productData)) {
                        return [];
                    }

                    return {
                        item_list_id: listData.item_list_id,
                        item_list_name: listData.item_list_name,
                        items: productData.items.map(function(item) {
                            return Object.assign({}, item, listData);
                        })
                    };
                })
            );
        }

        function trackProductListView(listData) {
            if (!listData || !Array.isArray(listData.productIds) || !listData.productIds.length) {
                return;
            }

            const itemListData = {
                item_list_id: String(listData.id),
                item_list_name: listData.name || document.title
            };
            const Data = new Promise(function(resolve, reject) {
                QUIAjax.get(
                    'package_quiqqer_products_ajax_products_frontend_dataLayer_getProductListData',
                    function(productData) {
                        if (!hasItems(productData)) {
                            resolve([]);
                            return;
                        }

                        resolve({
                            item_list_id: itemListData.item_list_id,
                            item_list_name: itemListData.item_list_name,
                            items: productData.items.map(function(item) {
                                return Object.assign({}, item, itemListData);
                            })
                        });
                    },
                    {
                        'package': 'quiqqer/products',
                        productIds: JSON.stringify(listData.productIds),
                        startIndex: parseInt(listData.startIndex) || 0,
                        onError: reject
                    }
                );
            });

            trackData('view_item_list', Data);
        }

        if (typeof window.QUIQQER_PRODUCT_ID !== 'undefined') {
            trackProductView(window.QUIQQER_PRODUCT_ID);
        }

        QUI.addEvent('onQuiqqerProductsSelectProduct', function(Parent, productId) {
            trackProductSelection(Parent, productId);
        });

        QUI.addEvent('onQuiqqerProductsProductView', function(Parent, productId) {
            trackProductView(productId);
        });

        QUI.addEvent('onQuiqqerProductsProductListView', function(Parent, listData) {
            trackProductListView(listData);
        });
    });
});
