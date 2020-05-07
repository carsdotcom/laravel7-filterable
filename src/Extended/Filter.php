<?php


namespace Kyslik\LaravelFilterable\Extended;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Kyslik\LaravelFilterable\Exceptions\UnfilterableAttributeException;
use Kyslik\LaravelFilterable\Generic\Filter as GenericFilter;
use Kyslik\LaravelFilterable\Generic\Templater;

/**
 * Algolia-style filtering
 *
 * Class Filter
 * @package Kyslik\LaravelFilterable\Extended
 */
class Filter extends GenericFilter
{

    use Sortable,
        FacetedSearch,
        SqlLikeSearch;

    /**
     * @var string
     */
    protected $filtersParam;

    /**
     * @var array
     */
    protected $jsonAttributes = [];

    /**
     * @var array
     */
    protected $priceFields = [];

    /**
     * @var string
     */
    protected $attributesToRetrieveParam;

    /**
     * @var string
     */
    protected $appEnvironment;

    /**
     * Filter constructor.
     * @param Request $request
     * @param Templater $templater
     */
    public function __construct(Request $request, Templater $templater)
    {
        $this->setExtendedSettings();
        parent::__construct($request, $templater);
    }

    protected function setExtendedSettings()
    {
        $this->sortByParam = $this->sortByParam ?? config('filterable.extended_filter.sort_by_param');
        $this->filtersParam = $this->filtersParam ?? config('filterable.extended_filter.filters_param');
        $this->facetFiltersParam = $this->facetFiltersParam ?? config('filterable.extended_filter.facet_filters_param');
        $this->attributesToRetrieveParam = $this->attributesToRetrieveParam ?? config('filterable.extended_filter.attributes_to_retrieve_param');
        $this->appEnvironment = app()->environment();
    }

    /**
     * Firstly we set builder instance passed from Eloquent's Model scope,
     * secondly we apply custom filters if applicable,
     * thirdly we apply generic filters and
     * return builder instance back to scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Kyslik\LaravelFilterable\Exceptions\MissingBuilderInstance
     * @throws \Throwable
     */
    public function apply(Builder $builder): Builder
    {
        try {
            $queryBuilder = $this->setBuilder($builder)
                ->applyFacetFilters()
                ->applySqlStyleFilters()
                ->getBuilder();

            $this->selectRetrievableAttributes($queryBuilder);

            if (($sorting = $this->request->get($this->sortByParam, null)) !== null) {
                $this->applySorting($queryBuilder, $sorting);
            }

            return $queryBuilder;
        } catch (\Throwable $e) {
            if ($this->appEnvironment === 'local') {
                dd($e);
            }

            if ($this->appEnvironment === 'testing') {
                throw $e;
            }

            return $queryBuilder ?? $builder;
        }
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    protected function selectRetrievableAttributes(Builder $builder): Builder
    {
        if (!($retrievableAttributes = $this->request->get($this->attributesToRetrieveParam)) || empty($retrievableAttributes)) {
            return $builder;
        }

        if (is_string($retrievableAttributes)) {
            $retrievableAttributes = explode(',', $retrievableAttributes);
        }

        if (!is_array($retrievableAttributes)) {
            return $builder;
        }

        $retrievableAttributes = array_map('trim', $retrievableAttributes);

        if (in_array('*', $retrievableAttributes, true) !== false) {
            return $builder;
        }

        return $builder->select($retrievableAttributes);
    }

    /**
     * @param string $attributeName
     * @param bool $throwException
     * @return bool
     * @throws UnfilterableAttributeException
     */
    protected function isFilterable(string $attributeName, bool $throwException = true): bool
    {
        $bool = in_array($attributeName, $this->prefixedFilterables, true) !== false;

        if (!$throwException || $bool || app()->environment('production')) {
            return $bool;
        }

        throw new UnfilterableAttributeException(sprintf('"%s" attribute is not filterable', $attributeName));
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    protected function isJsonField(string $attributeName): bool
    {
        return in_array($attributeName, $this->jsonAttributes) !== false;
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    protected function isPriceField(string $attributeName): bool
    {
        return in_array($attributeName, $this->priceFields) !== false;
    }

    /**
     * @param array $filter
     * @return array|false
     */
    protected function transformFilter(array $filter)
    {
        return $filter;
    }

}
