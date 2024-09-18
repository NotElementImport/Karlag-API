<?php

namespace App\Models;

use App\Models\Global\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Global\Tags;

/**
 * @property int $index_order
 * @property int $price
 * @property int $discount
 * @property string $tags
 * @property string $comment
 * @property int $author_id
 * @property int $delete
 */
class Price extends Model
{
    use HasFactory;

    protected $table = 'prices';

    protected $appends = ['final_price', 'title', 'tags'];

    protected $fillable = [
        'price_group_id',
        'index_order',
        'price',
        'discount',
        'title_ru',
        'title_kk',
        'tags',
        'comment',
        'author_id',
        'delete',
    ];

    protected $hidden = [
        'author_id'
    ];

    // Custom Attributes:

    protected function getFinalPriceAttribute() {
        return $this->price * (1 - $this->discount * 0.01);
    }

    protected function getTitleAttribute() {
        return $this->{ "title_".Language::capture() } 
            ?? $this->attributes['title_ru'];
    }

    protected function getTagsAttribute() {
        return json_decode($this->attributes['tags']);
    }

    // Relations:

    public function author() {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'name', 'email']);
    }

    public function group() {
        return $this->hasOne(PriceGroup::class, 'id', 'price_group_id');
    }
}
