<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

class SalesInvoiceResource extends Resource
{
    protected string $resourceName = 'sales-invoice';

    /**
     * Create or edit a Sales Down Payment (Uang Muka Penjualan).
     * POST api/sales-invoice/create-down-payment.do
     *
     * @param  array  $data  Fields: customerNo (required), dpAmount (required),
     *                       branchName, currencyCode, description, documentCode,
     *                       fiscalRate, inclusiveTax, isTaxable, number,
     *                       paymentTermName, poNumber, rate, retailIdCard, etc.
     */
    public function createDownPayment(array $data): array
    {
        return $this->api->postJson(
            "api/{$this->resourceName}/create-down-payment.do",
            $data,
        );
    }

    /**
     * Show sales invoices filtered by customer, date range, item, etc.
     * GET api/sales-invoice/detail-invoice.do
     *
     * @param  string  $customerNo  Customer ID (required).
     * @param  array  $params  Optional: fromDate, toDate, itemNo, salesmanName, serialNumber.
     */
    public function detailInvoice(string $customerNo, array $params = []): array
    {
        return $this->api->get(
            "api/{$this->resourceName}/detail-invoice.do",
            array_merge(['customerNo' => $customerNo], $params),
        );
    }
}
