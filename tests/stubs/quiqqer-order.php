<?php

namespace QUI\ERP\Order;

interface OrderArticle
{
    public function getId(): int;

    public function getQuantity(): int|float|bool;
}

abstract class AbstractOrder
{
    /**
     * @return iterable<OrderArticle>
     */
    abstract public function getArticles(): iterable;
}
