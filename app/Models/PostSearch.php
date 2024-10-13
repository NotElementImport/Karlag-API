<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;

class PostSearch extends Post
{
    public static function search(array $params, bool $extended = false) {
        $search = static::select()
            ->with('image');

        if($extended)
            $search->with('author');

        if(isset($params['id']))
            $search->where('posts.id', '=', $params['id']);
        if(isset($params['author_id']))
            $search->where('posts.author_id', '=', $params['author_id']);
        if($extended && isset($params['delete']))
            $search->where('posts.delete', '=', $params['delete']);
        else if(!$extended)
            $search->where('posts.delete', '=', 0);

        if(isset($params['slug']))
            $search->whereLike('posts.slug', "%$params[slug]%");

        if(isset($params['title'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('posts.title_ru', "%$params[title]%")
                      ->orWhereLike('posts.title_kk', "%$params[title]%")
                      ->orWhereLike('posts.title_en', "%$params[title]%");
            });
        }
        if(isset($params['content'])) {
            $search->where(function ($query) use(&$params) {
                $query->whereLike('posts.content_ru', "%$params[content]%")
                      ->orWhereLike('posts.content_kk', "%$params[content]%")
                      ->orWhereLike('posts.content_en', "%$params[content]%");
            });
        }

        if(isset($params['tag'])) {
            is_array($params['tag']) ?: abort(400, 'Tag must be array: tag[0], tag[1] ...');
            foreach($params['tag'] as &$item) {
                $search->whereLike('posts.tags', "%$item%");
            }
        }

        if(isset($params['created_at'])) {
            $date = date_create($params['created_at']);
            $search->whereBetween(
                DB::raw('DATE(posts.created_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }
        else if(isset($params['updated_at'])) {
            $date = date_create($params['updated_at']);
            $search->whereBetween(
                DB::raw('DATE(posts.updated_at)'), [
                $date->format('Y-m-d 00:00:00'),
                $date->format('Y-m-d 23:59:59')
            ]);
        }

        if(isset($params['sort']))
            $search->orderBy(
                'posts.'.str_replace('-', '', $params['sort']), 
                $params['sort'][0] == '-' ? 'desc' : 'asc'
            );
        else
            $search->orderBy('posts.created_at', 'desc');

        return $search->paginate($params['perpage'] ?? 15);
    }
}
