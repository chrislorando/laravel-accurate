<?php

namespace ChrisLorando\LaravelAccurate\Http\Resources;

class QueryBuilder
{
    protected array $fields = [];

    protected array $filters = [];

    protected ?string $sortField = null;

    protected ?string $sortDirection = null;

    protected ?int $pageSize = null;

    protected ?int $pageNumber = null;

    public function __construct(
        protected Resource $resource,
    ) {}

    /**
     * Select specific fields to return (fields).
     */
    public function select(string ...$fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add a filter condition.
     *
     * Generates two Accurate API params: filter.{field}.op + filter.{field}.val
     *
     * Shorthand mapping:
     *   '>'  → GREATER_THAN      '>=' → GREATER_EQUAL_THAN
     *   '<'  → LESS_THAN         '<=' → LESS_EQUAL_THAN
     *   '!=' → NOT_EQUAL         'like'/'contain' → CONTAIN
     *   'between' → BETWEEN      'not_between' → NOT_BETWEEN
     *   'empty' → EMPTY          'not_empty' → NOT_EMPTY
     *   default → EQUAL (exact match)
     *
     * Calling patterns:
     *   where('name', 'test')                  → filter.name.op=EQUAL, filter.name.val=test
     *   where('name', 'like', 'test')          → filter.name.op=CONTAIN, filter.name.val=test
     *   where('price', '>', 100)               → filter.price.op=GREATER_THAN, filter.price.val=100
     *   where('price', 'between', [1, 100])    → filter.price.op=BETWEEN, filter.price.val[0]=1, filter.price.val[1]=100
     *   where('name', 'empty')                 → filter.name.op=EMPTY (no val)
     */
    public function where(string $field, string $operator, string|int|float|array|null $value = null): static
    {
        if ($value === null) {
            $resolved = $this->resolveOperator($operator);

            if (in_array($resolved, ['EMPTY', 'NOT_EMPTY'], true)) {
                $this->filters[$field] = ['op' => $resolved];
            } else {
                $this->filters[$field] = ['op' => 'EQUAL', 'val' => $operator];
            }
        } elseif (is_array($value)) {
            $this->filters[$field] = ['op' => $this->resolveOperator($operator), 'val' => $value];
        } else {
            $this->filters[$field] = ['op' => $this->resolveOperator($operator), 'val' => $value];
        }

        return $this;
    }

    /**
     * Map shorthand operators to Accurate API filter operators.
     */
    protected function resolveOperator(string $shorthand): string
    {
        return match ($shorthand) {
            '>', 'gt' => 'GREATER_THAN',
            '>=', 'gte' => 'GREATER_EQUAL_THAN',
            '<', 'lt' => 'LESS_THAN',
            '<=', 'lte' => 'LESS_EQUAL_THAN',
            '!=', '<>' => 'NOT_EQUAL',
            '=', '==' => 'EQUAL',
            'like', 'LIKE' => 'CONTAIN',
            'contain' => 'CONTAIN',
            'between' => 'BETWEEN',
            'not_between' => 'NOT_BETWEEN',
            'empty' => 'EMPTY',
            'not_empty' => 'NOT_EMPTY',
            default => $shorthand, // Already an Accurate operator
        };
    }

    /**
     * Set sort order (sp.sort).
     */
    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->sortField = $field;
        $this->sortDirection = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    /**
     * Set page size / limit (sp.pageSize).
     */
    public function limit(int $limit): static
    {
        $this->pageSize = $limit;

        return $this;
    }

    /**
     * Set page number (sp.page).
     */
    public function page(int $page): static
    {
        $this->pageNumber = $page;

        return $this;
    }

    /**
     * Execute the query and return results.
     */
    public function get(): array
    {
        return $this->resource->list($this->toParams());
    }

    /**
     * Get the first result only.
     */
    public function first(): ?array
    {
        $result = $this->limit(1)->get();

        return $result['d'][0] ?? null;
    }

    /**
     * Paginate and return data with metadata.
     */
    public function paginate(): array
    {
        $result = $this->get();

        return [
            'data' => $result['d'] ?? [],
            'sp' => $result['sp'] ?? [
                'page' => $this->pageNumber ?? 1,
                'pageSize' => $this->pageSize ?? 0,
                'total' => 0,
            ],
        ];
    }

    /**
     * Build the query parameter array for the Accurate API.
     */
    public function toParams(): array
    {
        $params = [];

        if ($this->fields !== []) {
            $params['fields'] = implode(',', $this->fields);
        }

        foreach ($this->filters as $field => $filter) {
            $params["filter.{$field}.op"] = $filter['op'];

            if (isset($filter['val'])) {
                if (is_array($filter['val'])) {
                    foreach ($filter['val'] as $i => $v) {
                        $params["filter.{$field}.val[{$i}]"] = (string) $v;
                    }
                } else {
                    $params["filter.{$field}.val"] = (string) $filter['val'];
                }
            }
        }

        if ($this->sortField !== null) {
            $params['sp.sort'] = "{$this->sortField}|{$this->sortDirection}";
        }

        if ($this->pageSize !== null) {
            $params['sp.pageSize'] = (string) $this->pageSize;
        }

        if ($this->pageNumber !== null) {
            $params['sp.page'] = (string) $this->pageNumber;
        }

        return $params;
    }
}
