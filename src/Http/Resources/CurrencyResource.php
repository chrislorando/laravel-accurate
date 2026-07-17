<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

class CurrencyResource extends Resource
{
    protected string $resourceName = 'currency';

    /**
     * Get exchange rate for a currency on a specific date.
     * GET api/currency/exchange-rate.do?currencyCode=...&transDate=...
     */
    public function exchangeRate(string $currencyCode, ?string $transDate = null): array
    {
        $params = ['currencyCode' => $currencyCode];
        if ($transDate !== null) {
            $params['transDate'] = $transDate;
        }

        return $this->api->get("api/{$this->resourceName}/exchange-rate.do", $params);
    }

    /**
     * Get fiscal rate for a currency on a specific date.
     * GET api/currency/fiscal-rate.do?currencyCode=...&transDate=...
     */
    public function fiscalRate(string $currencyCode, ?string $transDate = null): array
    {
        $params = ['currencyCode' => $currencyCode];
        if ($transDate !== null) {
            $params['transDate'] = $transDate;
        }

        return $this->api->get("api/{$this->resourceName}/fiscal-rate.do", $params);
    }
}
