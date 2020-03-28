<?php


namespace Kyslik\LaravelFilterable\Test;


class FacetedSearchTest extends ExtendedFilterTestCase
{

    /**
     * @test
     */
    function faceted_search_single_value()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\'', [
            $this->facetedSearchQueryParamName => '["make:Nissan"]',
        ]);
    }

    /**
     * @test
     */
    function faceted_search_single_attribute_with_multiple_values()
    {
        $this->assertQuery('select * from "vehicles" where "make" in (\'Nissan\', \'Toyota\')', [
            $this->facetedSearchQueryParamName => '["make:Nissan", "make:Toyota"]',
        ]);
    }

    /**
     * @test
     */
    function faceted_search_single_negative_value()
    {
        $this->assertQuery('select * from "vehicles" where "make" not in (\'Nissan\')', [
            $this->facetedSearchQueryParamName => '["make:-Nissan"]',
        ]);
    }

    /**
     * @test
     */
    function faceted_search_with_multi_attributes()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' and "model" = \'Altima\'', [
            $this->facetedSearchQueryParamName => '"make:Nissan","model:Altima"',
        ]);
    }

    /**
     * @test
     */
    function faceted_search_with_multi_attributes_and_negative_value()
    {
        $this->assertQuery('select * from "vehicles" where "make" in (\'Nissan\', \'Toyota\') and "model" not in (\'Altima\')', [
            $this->facetedSearchQueryParamName => '["make:Nissan", "make:Toyota"],"model:-Altima"',
        ]);
    }

    /**
     * @test
     */
    function it_allows_defining_retrievable_attributes()
    {
        $this->assertQuery('select "make", "model" from "vehicles" where "make" = \'Nissan\'', [
            $this->facetedSearchQueryParamName => '["make:Nissan"]',
            $this->attributesToRetrieveParamName => 'make,model'
        ]);
    }

    /**
     * @test
     */
    function it_can_sort_the_results_using_provided_sorting()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' order by "make" desc, "model" desc', [
            $this->facetedSearchQueryParamName => '["make:Nissan"]',
            $this->sortByParamName => 'make|model'
        ]);
    }

    /**
     * @test
     */
    function it_can_perform_conditional_sorting()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' order by "make" desc, case when(model=\'Nissan\') then 1 else 0 end asc', [
            $this->facetedSearchQueryParamName => '["make:Nissan"]',
            $this->sortByParamName => 'make|model=Nissan,asc'
        ]);
    }

}
