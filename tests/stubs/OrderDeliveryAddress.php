<?php

namespace QUI\ERP\Order;

interface OrderDeliveryAddress
{
    public function getUUID(): string;

    public function getCountry(): \QUI\Countries\Country;
}
