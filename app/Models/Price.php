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

    protected $appends = ['final_price', 'title'];

    protected $fillable = [
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
        'author_id', 'title_ru', 'title_kk'
    ];

    protected function getFinalPriceAttribute() {
        return $this->price * (1 - $this->discount * 0.01);
    }

    protected function getTitleAttribute() {
        $lang = Language::capture();
        return $this->{ "title_$lang" };
    }

    public static function preparePrice(&$price) {
        $price->tags = Tags::fromString($price->tags);

        if(!is_null($price->author)) {
            $price->author->makeHidden(['id']);
        }

        return $price;
    }

    public function author() {
        return $this->hasOne(User::class, 'id', 'author_id')->select(['id', 'name', 'email']);
    }
}
