<?php

namespace QUITests\ERP\Products\Fixtures;

use QUI\ERP\Products\Product\ProductList;
use QUI\ERP\Products\Utils\Calc;

class RecordingCalc extends Calc
{
    public int $calls = 0;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(TestUser $User, private readonly array $data)
    {
        parent::__construct($User);
    }

    public function calcProductList(ProductList $List, callable|bool $callback = false): ProductList
    {
        $this->calls++;

        if (is_callable($callback)) {
            $callback($this->data);
        }

        return $List;
    }
}
