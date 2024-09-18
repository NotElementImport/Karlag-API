<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroupSearch extends PriceGroup
{
    public static function search(array $params) {
        $search = static::select();

        if(isset($params['childs']))
            $search->with('prices');

        if(isset($params['delete']))
            $search->where('price_groups.delete', '=', $params['delete']);

        if(isset($params['title'])) {
            $search->whereLike('price_groups.title_ru', $params['title']);
            $search->whereLike('price_groups.title_kk', $params['title']);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'price_groups.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('price_groups.id', 'desc');

        return $search->paginate();
    }
}
