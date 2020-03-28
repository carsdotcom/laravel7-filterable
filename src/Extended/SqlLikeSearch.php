<?php


namespace Kyslik\LaravelFilterable\Extended;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait SqlLikeSearch
{

    /*
    |--------------------------------------------------------------------------
    | Raw/SQL style filters
    |--------------------------------------------------------------------------
    | Support for SQL-like syntax, where you can use boolean operators and
    | parentheses to combine individual filters. Check out this page for
    | examples: https://www.algolia.com/doc/api-reference/api-parameters/filters/
    |
    | Usage: condition:("NEW","USED") translates to "where condition in (NEW, USED)"
    |        price:100 TO 200         translates to "where price between 100, 200"
    |        NOT make:"BMW" OR color:("RED", "BLUE")
    |                                 translates to "where make != 'BMW' or (color in ('RED', 'BLUE'))"
    |
    */

    /**
     * @return $this
     */
    protected function applySqlStyleFilters(): self
    {
        $parsedStatements = $this->getParsedSqlFilters();

        if (empty($parsedStatements[1])) {
            return $this;
        }

        foreach ($parsedStatements[1] as $index => $statement) {
            $booleanOperator = $parsedStatements[2][$index - 1] ?? null;
            if ($this->isCombinedStatement($statement)) {
                $this->applyCombinedSqlStatement($statement, $booleanOperator);
                continue;
            }

            $this->applySqlStatement($statement, $booleanOperator);
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getParsedSqlFilters(): ?array
    {
        $filters = $this->request->get($this->filtersParam);

        if (empty($filters) || !is_string($filters)) {
            return null;
        }

        $filters = str_replace([' and ', ' or '], [' AND ', ' OR '], $filters);

        $combinedStatements = $this->getParsedCombinedSqlStatements($filters);

        preg_match_all('/(.*?)(AND|OR|$)/', $filters, $matches);

        return array_map(static function (array $items, $index) use ($combinedStatements) {
                // Remove empty filters
                $items = array_filter(array_map('trim', $items));
                if ($index !== 1 || empty($combinedStatements)) {
                    return $items;
                }

                // Replace combined-conditions' hashes with the actual statements
                return array_map(static function ($statement) use ($combinedStatements) {
                    if (($hash = str_replace('combined:', '', $statement)) !== $statement) {
                        return $combinedStatements[$hash] ?? null;
                    }

                    return $statement;
                }, $items);

            }, $matches, array_keys($matches));
    }

    /**
     * @param string $filters
     * @return array
     */
    protected function getParsedCombinedSqlStatements(string &$filters): array
    {
        $combinedStatements = [];

        if (strpos($filters, '(') === false) {
            return $combinedStatements;
        }

        $filters = preg_replace_callback('/\((.*?)\)/', static function ($value) use (&$combinedStatements) {
            if (!Str::contains($value[1], [' OR ', ' AND '])) {
                return $value[0];
            }

            $hash = md5($value[1]);
            $combinedStatements[$hash] = $value[1];
            return "combined:$hash";
        }, $filters);

        return $combinedStatements;
    }

    /**
     * @param string $statement
     * @param string|null $boolOperator
     * @return $this
     */
    protected function applyCombinedSqlStatement(string $statement, string $boolOperator = null): self
    {
        $isOr = strtoupper($boolOperator ?? '') === 'OR';
        $eloquentMethodName = Str::camel(sprintf('%s where', $isOr ? 'or' : ''));

        $this->builder->{$eloquentMethodName}(function (Builder $builder) use ($statement) {
            preg_match_all('/(?<query>.*?)(?<boolOperator>(?:\s+)OR|AND|$)/i', $statement, $matches);
            if (empty($matches['query'])) {
                return;
            }

            foreach ($matches['query'] as $index => $subQuery) {
                if (($subQuery = trim($subQuery)) === '') {
                    continue;
                }

                $subQueryBoolOperator = $matches['boolOperator'][$index - 1] ?? null;

                if (is_string($subQueryBoolOperator)) {
                    $subQueryBoolOperator = trim($subQueryBoolOperator);
                }

                $this->applySqlStatement($subQuery, $subQueryBoolOperator, $builder);
            }

        });

        return $this;
    }

    /**
     * @param string $statement
     * @param string|null $boolOperator
     * @param Builder|null $builder
     * @return $this
     */
    protected function applySqlStatement(string $statement, string $boolOperator = null, Builder $builder = null): self
    {
        $builder = $builder ?? $this->builder;
        $isNegative = Str::startsWith($statement, $negativeKeywords = ['NOT', 'not']);
        $isOr = strtoupper(trim($boolOperator ?? '')) === 'OR';

        if ($isNegative) {
            $statement = trim(str_replace($negativeKeywords, '', $statement));
        }

        if (($filter = $this->isRangeStatement($statement, $isOr, $isNegative)) !== false) {
            $builder->{$filter['eloquentMethodName']}($filter['attributeName'], $filter['value']);
            return $this;
        }

        if (($filter = $this->isNumericStatement($statement, $isOr, $isNegative)) !== false) {
            $builder->{$filter['eloquentMethodName']}($filter['attributeName'], $filter['operator'], $filter['value']);
            return $this;
        }

        if (($filter = $this->isAttributeFilter($statement, $isOr, $isNegative)) !== false) {
            return $this->applySqlAttributeFilter($filter, $builder, $isOr, $isNegative);
        }

        return $this;
    }

    /**
     * @param array $filter
     * @param Builder $builder
     * @param bool $isOr
     * @param bool $isNegative
     * @return Filter
     */
    protected function applySqlAttributeFilter(array $filter, Builder $builder, bool $isOr, bool $isNegative): self
    {
        $isMultiValue = is_array($filter['value']);
        $isNull = in_array($filter['value'], [null, 'NULL'], true) !== false;
        $eloquentMethodName = $this->getEloquentMethodName($isMultiValue ? 'IN' : '=', $isOr, $isNegative, $filter['value']);

        if ($isNull) {
            $builder->{$eloquentMethodName}($filter['attributeName']);
            return $this;
        }

        if (!$isMultiValue) {
            $builder->{$eloquentMethodName}($filter['attributeName'], $filter['operator'] ?? null, $filter['value']);
        } else {
            $builder->{$eloquentMethodName}($filter['attributeName'], $filter['value']);
        }

        return $this;
    }

    /**
     * EXAMPLE: price:100 TO 200
     *
     * @param string $statement
     * @param bool $isOr
     * @param bool $isNegative
     * @return array|false
     */
    protected function isRangeStatement(string $statement, bool $isOr, bool $isNegative)
    {
        if (preg_match('/(?<attributeName>\w+):(?<min>\d+) TO (?<max>\d+)/i', $statement, $matches) !== 1) {
            return  false;
        }

        $realMin = min($matches['min'], $matches['max']);
        $realMax = max($matches['max'], $matches['min']);

        $matches['min'] = $realMin;
        $matches['max'] = $realMax;

        $matches['value'] = [
            'min' => $matches['min'],
            'max' => $matches['max']
        ];

        $matches['operator'] = 'between';

        $matches['eloquentMethodName'] = $this->getEloquentMethodName($matches['operator'], $isOr, $isNegative);

        if ($this->isFilterable($matches['attributeName']) === false) {
            return false;
        }

        return $this->transformFilter(array_filter($matches, static function ($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * EXAMPLE: price <= 200
     *
     * @param string $statement
     * @param bool $isOr
     * @param bool $isNegative
     * @return array|false
     */
    protected function isNumericStatement(string $statement, bool $isOr, bool $isNegative)
    {
        $regex = sprintf('/(?<attributeName>\w+)\s?(?<operator>(%s))\s?(?<value>\d+)/', implode('|', self::getNumericOperators()));

        if (preg_match($regex, $statement, $matches) !== 1) {
            return false;
        }

        $matches['eloquentMethodName'] = $this->getEloquentMethodName($matches['operator'], $isOr, $isNegative);

        if ($this->isFilterable($matches['attributeName']) === false) {
            return false;
        }

        return $this->transformFilter(array_filter($matches, static function ($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * EXAMPLE: condition:("NEW","USED")
     *          brand:Apple
     *
     * @param string $statement
     * @param bool $isOr
     * @param bool $isNegative
     * @return array|false
     */
    protected function isAttributeFilter(string $statement, bool $isOr, bool $isNegative)
    {
        if (preg_match('/(?<attributeName>\w+):(?<value>.*?)$/', $statement, $matches) !== 1) {
            return false;
        }

        $matches['value'] = trim($matches['value'], '"');

        if (strpos($matches['value'], '("') !== false) {
            preg_match_all('/"(.*?)"/', $matches['value'], $values);
            if (empty($values)) {
                return false;
            }

            $matches['value'] = count($values[1]) > 1 ? $values[1] : array_pop($values[1]);
        }

        if ($this->isFilterable($matches['attributeName']) === false) {
            return false;
        }

        $matches['operator'] = null;

        if (!is_array($matches['value'])) {
            $matches['operator'] = $isNegative ? '!=' : '=';
        }

        return $this->transformFilter(array_filter($matches, static function ($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * @return array
     */
    public static function getNumericOperators(): array
    {
        return ['=', '!=', '>', '>=', '<', '<='];
    }

    /**
     * @param string $statement
     * @return bool
     */
    protected function isCombinedStatement(string $statement): bool
    {
        return Str::contains(strtoupper($statement), [' OR ', ' AND ']) !== false;
    }

    /**
     * @param string $operator
     * @param bool $isOr
     * @param bool $isNegative
     * @param mixed|null $value
     * @return string
     */
    protected function getEloquentMethodName(string $operator, bool $isOr = false, bool $isNegative = false, $value = null): string
    {
        $eloquentBaseMethodName = Str::camel(sprintf('%s where %s %s',
            $isOr ? 'or' : '',
            $isNegative ? 'not' : '',
            $value === 'NULL' ? 'null' : ''
        ));

        switch (strtoupper($operator)) {
            case 'BETWEEN':
                return $eloquentBaseMethodName.'Between';

            case 'IN':
                return $eloquentBaseMethodName.'In';

            default:
                if ($eloquentBaseMethodName === 'whereNot') {
                    return 'where';
                }

                return $eloquentBaseMethodName;
        }
    }

}
