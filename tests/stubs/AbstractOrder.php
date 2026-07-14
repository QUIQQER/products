<?php

namespace QUI\ERP\Order;

abstract class AbstractOrder
{
    /**
     * @return iterable<OrderArticle>
     */
    abstract public function getArticles(): iterable;

    abstract public function getDeliveryAddress(): OrderDeliveryAddress;
}
