<?php

namespace App\Models;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShortPriceSearch extends ShortPrice
{
    public static function search(array $params, bool $thinMode = false) {
        $search = static::select();

        if(isset($params['id']))
            $search->where('short_prices.id', '=', $params['id']);

        if(!$thinMode && isset($params['delete']))
            $search->where('short_prices.delete', '=', $params['delete']);
        else if(!$thinMode)
            $search->where('short_prices.delete', '=', 0);

        if(isset($params['title'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('short_prices.title_ru', "%$params[title]%")
                    ->orWhereLike('short_prices.title_kk', "%$params[title]%")
                    ->orWhereLike('short_prices.title_en', "%$params[title]%");
            });
        }

        if(isset($params['price'])) {
            $search->where(function ($query) use(&$params) {
                $query->where('short_prices.adult', '=', "%$params[price]%")
                    ->orWhere('short_prices.student', '=', "%$params[price]%")
                    ->orWhere('short_prices.children', '=', "%$params[price]%")
                    ->orWhere('short_prices.pensioner', '=', "%$params[price]%");
            });
        }

        if(isset($params['created_at'])) {
            $date = date_create($params['created_at']);
            $search->whereBetween(
                DB::raw('DATE(short_prices.created_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['updated_at'])) {
            $date = date_create($params['updated_at']);
            $search->whereBetween(
                DB::raw('DATE(short_prices.updated_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'short_prices.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('short_prices.created_at', 'desc');

        return $search->paginate($params['perpage'] ?? 15);
    }
}
