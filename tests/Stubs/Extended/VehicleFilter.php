<?php


namespace Kyslik\LaravelFilterable\Test\Stubs\Extended;


use Kyslik\LaravelFilterable\Extended\Filter;

class VehicleFilter extends Filter
{

    protected $filterables = [
        'year',
        'make',
        'model',
        'trim',
        'color',
        'price',
    ];

}
