<?php

namespace App\Models;

use App\Models\Global\Language;
use App\Models\Global\Tags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $appends = [ 'tags', 'title', 'content' ];

    protected $fillable = [
        'title_ru',
        'title_kk',
        'title_en',
        'content_ru',
        'content_kk',
        'content_en',
        'slug',
        'tags',
        'author_id',
        'delete',
        'image_id'
    ];

    protected $hidden = [
        'author_id'
    ];

    // Custom Attributes:

    public function getTagsAttribute(&$post) {
        return json_decode($this->attributes['tags']);
    }

    public function getTitleAttribute(&$post) {
        return $this->{ "title_".Language::capture() } 
            ?? $this->attributes['title_ru'];
    }

    public function getContentAttribute(&$post) {
        return $this->{ "content_".Language::capture() } 
            ?? $this->attributes['content_ru'];
    }

    // Relations:

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'name', 'email']);
    }

    public function image()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }
}
