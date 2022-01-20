<?php

namespace Iutrace\LaravelFilters\Traits;

use Illuminate\Support\Arr;

trait HasFilters
{
    protected static $customFilterFields = [];

    protected function finalField($field)
    {
        $finalField = $field;
        if (strstr($field, '.') !== false) { // . found
            $finalField = Arr::last(explode('.', $field));
        }

        return $finalField;
    }

    protected function applyFilters($query, array $filters)
    {
        $fields = $this->filterFields ?? [
            'id',
            'created_at',
        ];

        $relations = [];
        foreach ($fields as $field) {
            if (isset($filters[$field])) {
                $filter = $filters[$field];
                $finalField = $this->finalField($field);

                if (! is_array($filter)) {
                    $filter = ['=' => $filter];
                }

                if (isset(static::$customFilterFields[$field])) {
                    $customFilterFields = static::$customFilterFields;
                    $closure = function ($query) use ($finalField, $field, $filter, $customFilterFields) {
                        $customFilterFields[$field]($query, $finalField, $filter);
                    };
                } else {
                    $closure = function ($query) use ($finalField, $filter) {
                        foreach ($filter as $op => $value) {
                            $this->applyOperation($query, $finalField, $op, $value);
                        }
                    };
                }

                if (strstr($field, '.') !== false) { // . found, is relation
                    Arr::set($relations, $field, $closure);
                } else {
                    $closure($query);
                }
            }
        }

        $this->applyRelationsOperations($query, $relations);
    }

    protected function applyRelationsOperations($query, array $relations)
    {
        foreach ($relations as $relation => $value) {
            if (is_array($value)) {
                $query->whereHas($relation, function ($query) use ($value) {
                    $this->applyRelationsOperations($query, $value);
                });
            } else {
                $query->where($value);
            }
        }
    }

    protected static function customFilterField(string $name, \Closure $closure)
    {
        self::$customFilterFields[$name] = $closure;
    }

    protected function applyOperation($query, $field, $operation, $value)
    {
        switch ($operation) {
            case 'in':
                $query->whereIn($field, $value);

                break;
            case '!in':
                $query->whereNotIn($field, $value);

                break;
            default:
                $query->where($field, $operation, $value);

                break;
        }
    }

    protected function applySearch($query, $term)
    {
        if (empty($term)) {
            return;
        }

        preg_match_all('|\w+|', $term, $words);
        $words = $words[0];

        $fields = $this->searchFields ?? [
            'id',
        ];

        $relations = [];
        foreach ($fields as $field) {
            $finalField = $this->finalField($field);

            $closure = function ($query) use ($finalField, $words) {
                $query->where(function ($query) use ($finalField, $words) {
                    foreach ($words as $word) {
                        $query->orWhere($finalField, 'like', $word . '%')
                            ->orWhere($finalField, 'like', '% ' . $word . '%');
                    }
                });
            };

            if (strstr($field, '.') !== false) { // . found, is relation
                Arr::set($relations, $field, $closure);
            } else {
                $closure($query);
            }
        }

        $this->applyRelationsOperations($query, $relations);
    }
}
