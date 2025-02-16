<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repressed extends Model
{
    use HasFactory;

    protected $guarded = []; 

    protected $table = 'represseds';

    protected $appends = ['content'];

    protected $fillable = [
        'slug',
        'fio',
        'content_ru',
        'content_kk',
        'content_en',
        'birthday_year',
        'death_year',
        'author_id',
        'delete',
    ];

    protected $hidden = [
        'author_id'
    ];

    // Custom Attributes:

    public function getContentAttribute(&$post) {
        return $this->{ "content_".Language::capture() } 
            ?? $this->attributes['content_ru'];
    }

    // Relations:

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'name', 'email']);
    }
}
