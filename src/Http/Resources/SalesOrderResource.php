<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

class SalesOrderResource extends Resource
{
    protected string $resourceName = 'sales-order';

    /**
     * Close (or reopen) a Sales Order manually by its SO number.
     * POST api/sales-order/manual-close-order.do
     *
     * @param  string  $number  The Sales Order number.
     * @param  bool  $orderClosed  True to close the order, false to reopen.
     */
    public function manualCloseOrder(string $number, bool $orderClosed = true): array
    {
        return $this->api->postJson(
            "api/{$this->resourceName}/manual-close-order.do",
            [
                'number' => $number,
                'orderClosed' => $orderClosed,
            ],
        );
    }
}
