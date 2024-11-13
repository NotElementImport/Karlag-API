<?php

namespace App\Models;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RepressedSearch extends Repressed
{
    public static function  search(array $params, bool $extended = false) {
        $search = static::select();

        if($extended)
            $search->with('author');

        if(isset($params['id']))
            $search->where('represseds.id', '=', $params['id']);
        if(isset($params['author_id']))
            $search->where('represseds.author_id', '=', $params['author_id']);
        if($extended && isset($params['delete']))
            $search->where('represseds.delete', '=', $params['delete']);
        else if(!$extended)
            $search->where('represseds.delete', '=', 0);

        if(isset($params['slug']))
            $search->whereLike('represseds.slug', "%$params[slug]%");

        if(isset($params['fio'])) {
            $search->whereLike('represseds.fio', str_contains($params['fio'], '%') ? $params['fio'] : "%$params[fio]%");
        }
        if(isset($params['content'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('represseds.content_ru', "%$params[content]%")
                    ->orWhereLike('represseds.content_kk', "%$params[content]%")
                    ->orWhereLike('represseds.content_en', "%$params[content]%");
            });
        }

        if(isset($params['created_at'])) {
            $date = date_create($params['created_at']);
            $search->whereBetween(
                DB::raw('DATE(represseds.created_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['updated_at'])) {
            $date = date_create($params['updated_at']);
            $search->whereBetween(
                DB::raw('DATE(represseds.updated_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'represseds.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('represseds.created_at', 'desc');

        return $search->paginate($params['perpage'] ?? 15);
    }
}
