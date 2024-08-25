<?php

namespace App\Models;

use App\Models\Global\Tags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'tags',
        'author_id',
        'delete',
        'imgsrc'
    ];

    protected $hidden = [
        'author_id'
    ];

    public static function preparePost(&$post) {
        $post->tags = Tags::fromString($post->tags);

        if(!is_null($post->author)) {
            $post->author->makeHidden(['id']);
        }

        return $post;
    }

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'name', 'email']);
    }
}
