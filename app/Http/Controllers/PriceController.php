<?php

namespace App\Http\Controllers;

use App\Models\Global\Language;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Price;
use App\Models\PriceGroup;
use App\Models\PriceSearch;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Validator;
use Cache;
use Str;

class PriceController extends Controller
{
    public function index(Request $request)
    {
        $items = PriceSearch::search($request->all());

        return Response::okJSON([ 
            'items' => $items->items(),
            'meta' => [
                'size'     => $items->total(),
                'lastpage' => $items->lastPage(),
                'perpage'  => $items->perPage(),
                'page'     => $items->currentPage()
            ]
        ]);
    }

    public function cached() 
    {
        $lang = Language::capture();

        $items = Cache::rememberForever("price-all-$lang", function () {
            return PriceGroup::where('delete', '0')
                ->orderBy('price_groups.order_index','asc')
                ->with('prices')
                ->get()
                ->makeHidden(['id', 'delete', 'order_index', 'title_kk', 'title_ru', 'title_en'])
                ->map(function ($item) {
                    $cortage = [];
                    foreach ($item->prices as &$price) {
                        $price->makeHidden(['price_group_id', 'title_ru', 'title_en', 'title_kk', 'created_at', 'updated_at', 'delete', 'index_order', 'id', 'author', 'comment']);
                        $cortage[Str::slug($price->title_ru)] = $price;
                    }
                    $item->setAttribute('childs', $cortage);
                    $item->makeHidden([ 'prices' ]);
                    return $item;
                })
                ->toArray();
        });

        return Response::okJSON($items);
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'group_id' => 'required',
                'title_ru' => 'required',
                'price' => 'required',
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        $price = new Price([
            'price_group_id' => $request->group_id,
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'author_id' => $request->user()->id,
            'price' => $request->price,
            'discount' => $request->get('discount', 0),
            'tags' => Tags::toString($request->get('tags', [])),
            'index_order' => $request->get('index_order', 0),
            'comment' => $request->get('comment', ''),
            'delete' => 0
        ]);

        Cache::forget('price-all-kk');
        Cache::forget('price-all-ru');

        return $price->save()
            ? Response::okJSON(['id' => $price->id])
            : Response::internalServerError('Ops something wrong while saving');
    }

    public function update(Request $request, string $id)
    {
        /** @var Price */
        $price = Price::where("id", $id)->first()
              ?? Response::notFound("Price $id not found");

        $price->fill( $request->post() );

        if($request->has('tags'))
            $price->setAttribute('tags', Tags::toString($request->tags));

        Cache::forget('price-all-kk');
        Cache::forget('price-all-ru');

        return $price->save()
            ? Response::accepted("Updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(string $id)
    {
        $item = Price::where('id', $id)->first()
            ?? abort(404, "Price $id not found");

        $item->delete = 1;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price $id delete");
    }

    public function revert(string $id)
    {
        $item = Price::where('id', $id)->first()
            ?? abort(404, "Price $id not found");

        $item->delete = 0;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price $id revert");
    }
}
