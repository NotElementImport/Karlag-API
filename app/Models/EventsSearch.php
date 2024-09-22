<?php

namespace App\Models;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventsSearch extends Events
{
    public static function search(array $params, bool $extended = false) {
        $search = static::select()
            ->with('image');

        if($extended)
            $search->with('author');

        if(isset($params['mode'])) {
            switch($params['mode']) {
                case "actual":
                    $search->where(DB::raw(value: 'DATE(`events`.`start_at`)'), '>=', Carbon::now());
                    break;
                case "history":
                    $search->where(DB::raw('DATE(`events`.`start_at`)'), '<', Carbon::now());
                    break;
            }
        }

        if(isset($params['id']))
            $search->where('events.id', '=', $params['id']);
        if(isset($params['author_id']))
            $search->where('events.author_id', '=', $params['author_id']);
        if($extended && isset($params['delete']))
            $search->where('events.delete', '=', $params['delete']);
        else
            $search->where('events.delete', '=', 0);

        if(isset($params['slug']))
            $search->whereLike('events.slug', "%$params[slug]%");

        if(isset($params['title'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('events.title_ru', "%$params[title]%")
                    ->orWhereLike('events.title_kk', "%$params[title]%")
                    ->orWhereLike('events.title_en', "%$params[title]%");
            });
        }
        if(isset($params['content'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('events.content_ru', "%$params[content]%")
                    ->orWhereLike('events.content_kk', "%$params[content]%")
                    ->orWhereLike('events.content_en', "%$params[content]%");
            });
        }

        if(isset($params['tag'])) {
            is_array($params['tag']) ?: abort(400, 'Tag must be array: tag[0], tag[1] ...');
            foreach($params['tag'] as &$item) {
                $search->whereLike('events.tags', "%$item%");
            }
        }

        if(isset($params['created_at'])) {
            $date = date_create($params['created_at']);
            $search->whereBetween(
                DB::raw('DATE(events.created_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['updated_at'])) {
            $date = date_create($params['updated_at']);
            $search->whereBetween(
                DB::raw('DATE(events.updated_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['start_at'])) {
            $date = date_create($params['start_at']);
            $search->whereBetween(
                DB::raw('DATE(events.start_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'events.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('events.created_at', 'desc');

        return $search->paginate($params['perpage'] ?? 15);
    }
}
