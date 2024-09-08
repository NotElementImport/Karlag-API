<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroup extends Model
{
    use HasFactory;

    protected $appends = ['title'];

    protected $fillable = [
        'title_ru',
        'title_kk',
        'delete',
        'order_index'
    ];

    public $timestamps = false;

    protected function getTitleAttribute() {
        $lang = Language::capture();
        return $this->{ "title_$lang" };
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
