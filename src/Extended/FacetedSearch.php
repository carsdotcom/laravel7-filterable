<?php


namespace Kyslik\LaravelFilterable\Extended;


use Illuminate\Support\Str;
use Kyslik\LaravelFilterable\Exceptions\UnfilterableAttributeException;

trait FacetedSearch
{

    /**
     * @var string
     */
    protected $facetFiltersParam;

    /*
    |--------------------------------------------------------------------------
    | Facet Filters
    |--------------------------------------------------------------------------
    | See this page for examples:
    | https://www.algolia.com/doc/api-reference/api-parameters/facetFilters/
    |
    */

    /**
     * @return static
     * @throws UnfilterableAttributeException
     */
    protected function applyFacetFilters(): self
    {
        $parsedFacetFilters = $this->getParsedFacetFilters();

        if (empty($parsedFacetFilters)) {
            return $this;
        }

        foreach ($parsedFacetFilters as $attributeName => $values) {
            $negativeValues = array_filter($values, static function ($value, $index) use ($attributeName, $parsedFacetFilters) {
                return Str::startsWith($value, '-');
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($negativeValues)) {
                $this->builder->whereNotIn($attributeName, array_map(static function ($value) {
                    return ltrim($value, '-');
                }, $negativeValues));
                $values = array_diff($values, $negativeValues);
            }

            if (empty($values)) {
                continue;
            }

            $methodName = count($values) > 1 ? 'whereIn' : 'where';

            if ($this->isJsonField($attributeName)) {
                $this->builder->whereRaw(sprintf('%s @> \'["%s"]\'', $attributeName, implode('", "', array_map('addslashes', $values))));
                continue;
            }

            $this->builder->$methodName($attributeName, $values);
        }

        return $this;
    }

    /**
     * EXAMPLE: [["make:Nissan", "make:Toyota"], "color:Red"]
     *          ["make:-Nissan", "make:Toyota"]
     *
     * @return array
     * @throws UnfilterableAttributeException
     */
    protected function getParsedFacetFilters(): array
    {
        $facetFilters = $this->request->get($this->facetFiltersParam);

        if (empty($facetFilters) || !is_string($facetFilters)) {
            return [];
        }

        preg_match_all('/"(?<attributeName>\w+):(?<value>.*?)"/', $facetFilters, $matches);

        $parsed = [];

        foreach ($matches['attributeName'] ?? [] as $index => $attributeName) {
            if (!isset($matches['value'][$index]) || $this->isFilterable($attributeName) === false) {
                continue;
            }

            $parsed[$attributeName][] = $matches['value'][$index];
        }

        return array_map([$this, 'transformFilter'], $parsed);
    }

}
