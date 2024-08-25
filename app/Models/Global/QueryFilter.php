<?php

namespace App\Models\Global;
use Illuminate\Database\Eloquent\Builder;

class QueryFilter
{
    /** @param Builder $query */
    public static function applySort($request, Builder $query, $defaultName = 'id', $defaultValue = 'desc') {
        if($request->has('sort')) {
            $sort = $request->get('sort');

            if(!is_array($sort)) {
                abort(400, 'Sort object in not Array');
            }

            $firstKey = array_key_first($sort);
            $query->orderBy($firstKey, $sort[$firstKey]);

            return;
        }

        $query->orderBy($defaultName, $defaultValue);
    }

    public static function apply($request, $query, $name, $as = 'number', $alias = null) {
        if (is_null($alias))
            $alias = $name;

        if($request->has($name)) {
            $value = $request->get($name);

            switch($as) {
                case 'text':
                    $query->whereLike($name, '%'.$value.'%');
                    break;
                case 'date': 
                    $query->whereDate($name, $value);
                    break;
                case 'less':
                    $query->where($name, '<', $value);
                    break;
                case 'greater':
                    $query->where($name, '>', $value);
                    break;
                case 'tag':
                    foreach($value as $itemArray) {
                        $query->whereLike($name, '%'.$itemArray.'%');
                    }
                    break;
                default:
                    $query->where($name, $value);
                    break;
            }
            return;
        }
        if($request->has('start_'.$name)) {
            switch($as) {
                case 'range-date': 
                    $query->whereDate($name, '>', $request->get('start_'.$name));
                    break;
            }
        }
        if($request->has('end_'.$name)) {
            switch($as) {
                case 'range-date': 
                    $query->whereDate($name, '<', $request->get('end_'.$name));
                    break;
            }
        }
    }
}