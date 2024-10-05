<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortPrice extends Model
{
    use HasFactory;

    protected $table = 'short_prices';

    protected $appends = ['title'];

    protected $fillable = [
        'index_order',
        'adult',
        'student',
        'children',
        'pensioner',

        'title_ru',
        'title_kk',
        'title_en',

        'delete',
    ];

    protected function getTitleAttribute() {
        return $this->{ "title_".Language::capture() } 
            ?? $this->attributes['title_ru'];
    }
}
