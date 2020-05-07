<?php


namespace Kyslik\LaravelFilterable\Extended;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait Sortable
{

    /**
     * @var string
     */
    protected $sortByParam;

    /**
     * Example: make=Toyota,asc|year|exterior_color!=Black
     *
     * @param Builder $queryBuilder
     * @param string $sorting
     */
    protected function applySorting(Builder $queryBuilder, string $sorting): void
    {
        if (!is_string($sorting)) {
            return;
        }

        $parts = explode('|', $sorting);

        $validOperators = ['=', '!=', '>', '<'];

        foreach ($parts as $part) {
            $sortDirection = Str::afterLast($part, ',');
            if ($sortDirection === $part) {
                $sortDirection = 'desc';
            }

            preg_match('/(?<column>\w+)(?<operator>(!=|=|<|>))?(?<value>.*?)?$/', $part, $matches);

            if (empty($matches['column'])) {
                continue;
            }

            if (empty($matches['operator']) || in_array($matches['operator'], $validOperators, true) === false) {
                if ($this->isPriceField($matches['column'])) {
                    $this->sortByPriceField($queryBuilder, $matches['column'], $sortDirection);
                    continue;
                }

                $queryBuilder->orderBy($matches['column'], $sortDirection);
                continue;
            }

            $matches['value'] = str_replace(",{$sortDirection}", '', $matches['value']);

            $orderBy = sprintf(
                'case when(%s%s%s) then 1 else 0 end %s',
                $matches['column'],
                $matches['operator'],
                is_numeric($matches['value']) ? $matches['value'] : sprintf('\'%s\'', $matches['value']),
                $sortDirection
            );

            $queryBuilder->orderByRaw($orderBy);
        }
    }

    /**
     * @param Builder $queryBuilder
     * @param string $columnName
     * @param string $sortDirection
     * @return Builder
     */
    protected function sortByPriceField(Builder $queryBuilder, string $columnName, string $sortDirection)
    {
        return $queryBuilder->orderByRaw(sprintf('case when(%1$s is null or %1$s = 0) then 1 else 0 end, %1$s %2$s', $columnName, $sortDirection));
    }

}
