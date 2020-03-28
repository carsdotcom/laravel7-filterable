<?php

return [

    'namespace'                 => 'Filters',

    // Default filter types
    // You may freely change operator keys '=', 't='... to suite your needs.
    'filter_types'              => [
        // accepts UNIX timestamp
        'timestamp' => [
            't!><' => ['case' => 'whereNotBetween', 'operator' => null, 'template' => 'timestamp-range'],
            't><'  => ['case' => 'whereBetween', 'operator' => null, 'template' => 'timestamp-range'],
            't>='  => ['case' => 'where', 'operator' => '>=', 'template' => 'timestamp'],
            't<='  => ['case' => 'where', 'operator' => '<=', 'template' => 'timestamp'],
            't>'   => ['case' => 'where', 'operator' => '>', 'template' => 'timestamp'],
            't<'   => ['case' => 'where', 'operator' => '<', 'template' => 'timestamp'],
            't!='  => ['case' => 'where', 'operator' => '!=', 'template' => 'timestamp'],
            't='   => ['case' => 'where', 'operator' => '=', 'template' => 'timestamp'],
        ],
        // accepts 1, 0, true, false, yes, no
        'boolean'   => [
            'b='  => ['case' => 'where', 'operator' => '=', 'template' => 'boolean'],
            'b!=' => ['case' => 'where', 'operator' => '!=', 'template' => 'boolean'],
        ],
        // accepts string or comma separated list
        'string'    => [
            '!><' => ['case' => 'whereNotBetween', 'operator' => null, 'template' => 'range'],
            '><'  => ['case' => 'whereBetween', 'operator' => null, 'template' => 'range'],
            '!~'  => ['case' => 'where', 'operator' => 'not like', 'template' => '%?%'],
            '~'   => ['case' => 'where', 'operator' => 'like', 'template' => '%?%'],
            '>='  => ['case' => 'where', 'operator' => '>=', 'template' => null],
            '<='  => ['case' => 'where', 'operator' => '<=', 'template' => null],
            '>'   => ['case' => 'where', 'operator' => '>', 'template' => null],
            '<'   => ['case' => 'where', 'operator' => '<', 'template' => null],
            '!='  => ['case' => 'where', 'operator' => '!=', 'template' => null],
            '='   => ['case' => 'where', 'operator' => '=', 'template' => null],
        ],
        // accepts comma separated list
        'in'        => [
            'i='  => ['case' => 'whereIn', 'operator' => null, 'template' => 'where-in'],
            'i=!' => ['case' => 'whereNotIn', 'operator' => null, 'template' => 'where-in'],
        ],
    ],

    // In case package does not match any of filters above it'll use '=' filter type.
    // This configuration can be overridden per filter.
    'default_type'              => '=',

    // Example: users?filter-id=>=1
    'prefix'                    => 'filter-',

    // When generating queries with multiple filters default_grouping_operator is used between the filters
    // Example: filter-id=1&filter-name=Joe will result in where id = 1 'default_grouping_operator' where name = Joe
    'default_grouping_operator' => 'and',

    'uri_grouping_operator' => 'grouping-operator',

    /*
    |--------------------------------------------------------------------------
    | Extended/Algolia-style filtering
    |--------------------------------------------------------------------------
    */

    'extended_filter' => [

        // The request parameter to use for sorting
        'sort_by_param' => 'sortBy',

        // The request parameter to use be used for sql-like filtering
        'filters_param' => 'filters',

        // The request parameter to use be used for filtering by facets
        'facet_filters_param' => 'facetFilters',


        // The request parameter to be used in the SELECT statement. By default we will return all columns
        'attributes_to_retrieve_param' => 'attributesToRetrieve',

        // The request parameter to be used for keyword searches
        'keyword_search_param' => 'keyword',

        // The database column name to use for keyword/fulltext searches
        'keyword_search_column' => 'keyword'
    ]
];
