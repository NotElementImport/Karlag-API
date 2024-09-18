<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroup extends Model
{
    use HasFactory;

    protected $table = 'price_groups';

    protected $appends = ['title'];

    protected $fillable = [
        'title_ru',
        'title_kk',
        'delete',
        'order_index'
    ];

    public $timestamps = false;

    // Custom Attributes:

    protected function getTitleAttribute() {
        return $this->{ "title_".Language::capture() } 
            ?? $this->attributes()['title_ru'];
    }

    // Relations:

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'price_group_id')
            ->where(['prices.delete' => 0]);
    }
}
