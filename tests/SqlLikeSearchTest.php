<?php


namespace Kyslik\LaravelFilterable\Test;

use Kyslik\LaravelFilterable\Exceptions\UnfilterableAttributeException;

class SqlLikeSearchTest extends ExtendedFilterTestCase
{

    /**
     * @test
     */
    function it_can_perform_numeric_comparisons()
    {
        $this->assertQuery('select * from "vehicles" where "year" < \'2020\'', [
            $this->sqlSearchQueryParamName => 'year < 2020'
        ]);

        $this->assertQuery('select * from "vehicles" where "year" != \'2020\'', [
            $this->sqlSearchQueryParamName => 'year != 2020'
        ]);
    }

    /**
     * @test
     */
    function it_can_filter_by_numeric_ranges()
    {
        $this->assertQuery('select * from "vehicles" where "year" between \'2012\' and \'2018\'', [
            $this->sqlSearchQueryParamName => 'year:2012 TO 2018'
        ]);

        $this->assertQuery('select * from "vehicles" where "year" between \'2010\' and \'2020\'', [
            $this->sqlSearchQueryParamName => 'year:2020 TO 2010'
        ]);

        $this->assertQuery('select * from "vehicles" where ("price" between \'1000\' and \'2000\' or "price" between \'5000\' and \'10000\')', [
            $this->sqlSearchQueryParamName => '(price:1000 TO 2000 or price:5000 to 10000)'
        ]);
    }

    /**
     * @test
     */
    function it_should_throw_an_exception_if_requested_attribute_in_not_filterable()
    {
        $this->applyFilter([
            $this->sqlSearchQueryParamName => 'make:"Nissan"'
        ]);

        $this->expectException(UnfilterableAttributeException::class);

        $this->applyFilter([
            $this->sqlSearchQueryParamName => 'NOT invalid:"some value"'
        ]);
    }

    /**
     * @test
     */
    function it_can_perform_faceted_search()
    {
        $this->assertQuery('select * from "vehicles" where "make" in (\'Nissan\', \'Toyota\')', [
            $this->sqlSearchQueryParamName => 'make:("Nissan","Toyota")'
        ]);

        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' or "color" = \'Red\'', [
            $this->sqlSearchQueryParamName => 'make:"Nissan" OR color:"Red"'
        ]);

        $this->assertQuery('select * from "vehicles" where "make" in (\'Nissan\', \'Toyota\') and "color" = \'Red\'', [
            $this->sqlSearchQueryParamName => 'make:("Nissan","Toyota") and color:"Red"'
        ]);

        $this->assertQuery('select * from "vehicles" where "make" in (\'Nissan\', \'Toyota\') and "price" <= \'50000\'', [
            $this->sqlSearchQueryParamName => 'make:("Nissan","Toyota") and price <= 50000'
        ]);
    }

    /**
     * @test
     */
    function it_can_check_for_null_values()
    {
        $this->assertQuery('select * from "vehicles" where "price" is not null or "make" is null', [
            $this->sqlSearchQueryParamName => 'NOT price:NULL or make:NULL'
        ]);

        $this->assertQuery('select * from "vehicles" where "price" is not null or "make" = \'null\'', [
            $this->sqlSearchQueryParamName => 'NOT price:NULL or make:null'
        ]);
    }

    /**
     * @test
     */
    function it_can_processed_grouped_conditions()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' and ("color" = \'red\' or "trim" = \'xl\')', [
            $this->sqlSearchQueryParamName => 'make:"Nissan" and (color:red or trim:xl)'
        ]);

        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' and ("color" = \'red\' or "trim" = \'xl\')', [
            $this->sqlSearchQueryParamName => 'make:"Nissan" and (color:red or trim:xl)'
        ]);
    }

    /**
     * @test
     */
    function it_allows_defining_retrievable_attributes()
    {
        $this->assertQuery('select "make", "model" from "vehicles" where "year" < \'2020\'', [
            $this->sqlSearchQueryParamName => 'year < 2020',
            $this->attributesToRetrieveParamName => 'make,model'
        ]);
    }

    /**
     * @test
     */
    function it_can_sort_the_results_using_provided_sorting()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' order by "make" desc, "model" asc', [
            $this->sqlSearchQueryParamName => 'make:Nissan',
            $this->sortByParamName => 'make|model,asc'
        ]);
    }

    /**
     * @test
     */
    function it_can_perform_conditional_sorting()
    {
        $this->assertQuery('select * from "vehicles" where "make" = \'Nissan\' order by "make" desc, case when(model=\'Nissan\') then 1 else 0 end desc', [
            $this->sqlSearchQueryParamName => 'make:Nissan',
            $this->sortByParamName => 'make|model=Nissan,desc'
        ]);
    }

}
