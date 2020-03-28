<?php


namespace Kyslik\LaravelFilterable\Test;


use Kyslik\LaravelFilterable\Test\Stubs\Extended\VehicleFilter;
use Kyslik\LaravelFilterable\Test\Stubs\Extended\VehicleModel;

abstract class ExtendedFilterTestCase extends GenericTestCase
{

    /**
     * @var string
     */
    protected $facetedSearchQueryParamName;

    /**
     * @var string
     */
    protected $sqlSearchQueryParamName;

    /**
     * @var string
     */
    protected $attributesToRetrieveParamName;

    /**
     * @var string
     */
    protected $sortByParamName;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('filterable.prefix', '');
        $this->sqlSearchQueryParamName = config('filterable.extended_filter.filters_param');
        $this->facetedSearchQueryParamName = config('filterable.extended_filter.facet_filters_param');
        $this->attributesToRetrieveParamName = config('filterable.extended_filter.attributes_to_retrieve_param');
        $this->sortByParamName = config('filterable.extended_filter.sort_by_param');
    }

    /**
     * @param string $expectedQuery
     * @param array $params
     */
    protected function assertQuery(string $expectedQuery, array $params)
    {
        $this->builder->setModel(new VehicleModel());
        $filter = $this->buildFilter(VehicleFilter::class, http_build_query($params));
        $this->assertEquals($expectedQuery, $this->dumpQuery($filter->apply($this->builder)));
        $this->resetBuilder();
    }

    /**
     * @param array $params
     */
    protected function applyFilter(array $params)
    {
        $this->builder->setModel(new VehicleModel());
        $filter = $this->buildFilter(VehicleFilter::class, http_build_query($params));
        $filter->apply($this->builder);
        $this->resetBuilder();
    }

}
