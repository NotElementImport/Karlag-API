<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class PriceSearch extends Price
{
    public static function search(array $params) {
        $search = static::select()
            ->with('group')
            ->with('author');

        if(isset($params['price']))
            $search->where('prices.price', '=', $params['price']);
        if(isset($params['discount']))
            $search->where('prices.discount', '=', $params['discount']);
        if(isset($params['comment']))
            $search->where('prices.comment', '=', $params['comment']);
        if(isset($params['author_id']))
            $search->where('prices.author_id', '=', $params['author_id']);
        if(isset($params['delete']))
            $search->where('prices.delete', '=', $params['delete']);
        if(isset($params['price_group_id']))
            $search->where('prices.price_group_id', '=', $params['price_group_id']);

        if(isset($params['title'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('prices.title_ru', $params['title'])
                      ->orWhereLike('prices.title_kk', $params['title'])
                      ->orWhereLike('prices.title_en', $params['title']);
            });
        }

        if(isset($params['created_at'])) {
            $date = date_create($params['created_at']);
            $search->whereBetween(
                DB::raw('DATE(prices.created_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['updated_at'])) {
            $date = date_create($params['updated_at']);
            $search->whereBetween(
                DB::raw('DATE(prices.updated_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'prices.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('prices.id', 'desc');

        return $search->paginate();
    }
}
