<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

class ItemResource extends Resource
{
    protected string $resourceName = 'item';

    /**
     * Get HPP (nearest cost) on a specific date.
     * GET api/item/get-nearest-cost.do?itemNo=...&transDate=...
     */
    public function getNearestCost(string $itemNo, ?string $transDate = null): array
    {
        $params = ['itemNo' => $itemNo];
        if ($transDate !== null) {
            $params['transDate'] = $transDate;
        }

        return $this->api->get("api/{$this->resourceName}/get-nearest-cost.do", $params);
    }

    /**
     * Get available stock quantity for an item.
     * GET api/item/get-stock.do?no=...&warehouseName=...
     */
    public function getStock(string $no, ?string $warehouseName = null): array
    {
        $params = ['no' => $no];
        if ($warehouseName !== null) {
            $params['warehouseName'] = $warehouseName;
        }

        return $this->api->get("api/{$this->resourceName}/get-stock.do", $params);
    }

    /**
     * List stock availability with optional filters.
     * GET api/item/list-stock.do
     */
    public function listStock(array $params = []): array
    {
        return $this->api->get("api/{$this->resourceName}/list-stock.do", $params);
    }

    /**
     * Search items by name or serial number.
     * GET api/item/search-by-item-or-sn.do?keywords=...
     */
    public function searchByItemOrSn(string $keywords, array $params = []): array
    {
        $params['keywords'] = $keywords;

        return $this->api->get("api/{$this->resourceName}/search-by-item-or-sn.do", $params);
    }

    /**
     * Search item by code or UPC/barcode.
     * GET api/item/search-by-no-upc.do?keywords=...
     */
    public function searchByNoUpc(string $keywords): array
    {
        return $this->api->get("api/{$this->resourceName}/search-by-no-upc.do", [
            'keywords' => $keywords,
        ]);
    }

    /**
     * Get stock mutation history (last 7 days).
     * GET api/item/stock-mutation-history.do
     */
    public function stockMutationHistory(array $params = []): array
    {
        return $this->api->get("api/{$this->resourceName}/stock-mutation-history.do", $params);
    }

    /**
     * Get last purchase price from a vendor.
     * GET api/item/vendor-price.do
     */
    public function vendorPrice(array $params): array
    {
        return $this->api->get("api/{$this->resourceName}/vendor-price.do", $params);
    }
}
