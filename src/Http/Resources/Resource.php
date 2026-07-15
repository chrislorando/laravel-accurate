<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

use ChrisLorando\LaravelAccurate\Http\ApiClient;

abstract class Resource
{
    /** Accurate API resource name, e.g. "item", "customer", "sales_invoice". */
    protected string $resourceName;

    public function __construct(
        protected ApiClient $api,
    ) {}

    /**
     * List resources: GET api/{resource}/list.do
     */
    public function list(array $params = []): array
    {
        return $this->api->get("api/{$this->resourceName}/list.do", $params);
    }

    /**
     * Get a single resource: GET api/{resource}/detail.do?id={id}
     */
    public function detail(string $id): array
    {
        return $this->api->get("api/{$this->resourceName}/detail.do", [
            'id' => $id,
        ]);
    }

    /**
     * Start a fluent query on this resource.
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Create or update: POST api/{resource}/save.do
     */
    public function save(array $data): array
    {
        return $this->api->post("api/{$this->resourceName}/save.do", $data);
    }

    /**
     * Delete a resource: DELETE api/{resource}/delete.do
     */
    public function delete(string $id): array
    {
        return $this->api->delete("api/{$this->resourceName}/delete.do", [
            'id' => $id,
        ]);
    }

    /**
     * Bulk create/update (max 100): POST api/{resource}/bulk-save.do
     *
     * Accurate accepts the entries as JSON under the "data" key, e.g.:
     *   { "data": [ {"name": "A"}, {"name": "B"} ] }
     */
    public function bulkSave(array $data): array
    {
        return $this->api->postJson(
            "api/{$this->resourceName}/bulk-save.do",
            ['data' => array_values($data)]
        );
    }
}
