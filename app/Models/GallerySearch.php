<?php

namespace App\Models;

class GallerySearch extends Gallery
{
    public static function search(array $params) {
        $search = static::select()
            ->whereLike('files.place', 'gallery/%');

        if(isset($params['dir']))
            $search->whereLike('files.place', "%$params[dir]%");

        if(isset($params['q']))
            $search->whereLike('files.src', "%$params[q]%");

        $search->orderBy('files.id', 'desc');

        return $search->paginate();
    }
}
