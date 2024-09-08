<?php

namespace App\Http\Controllers;

use App\Models\Global\Language;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Price;
use App\Models\PriceGroup;
use Illuminate\Http\Request;
use Validator;
use Cache;

class PriceController extends Controller
{
    public function index()
    {
        // User mode:
        if(!auth('sanctum')->check()) {
            $lang = Language::capture();

            $items = Cache::rememberForever("price-all-$lang", function () {
                return PriceGroup::where('delete', '0')
                    ->orderBy('price_groups.order_index','asc')
                    ->with('prices')
                    ->get()
                    ->makeHidden(['id', 'delete', 'order_index', 'title_kk', 'title_ru'])
                    ->map(function ($item) {
                        foreach ($item->prices as &$price) {
                            $price = Price::preparePrice($price);
                            $price->makeHidden(['price_group_id', 'title_ru', 'title_kk', 'created_at', 'updated_at', 'delete', 'index_order', 'id', 'author', 'comment']);
                        }
                        return $item;
                    })
                    ->toArray();
            });
    
            return Response::json($items);
        }

        // Admin Mode:
        $items = Price::select('*')
            ->orderBy('created_at','desc')
            ->with('author')
            ->get()
            ->map(fn ($item) => Price::preparePrice($item));

        return Response::json($items);
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'group_id' => 'required',
                'title_ru' => 'required',
                'title_kk' => 'required',
                'price' => 'required',
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $post = new Price([
            'price_group_id' => $request->group_id,
            'title_ru' => $request->title_ru,
            'title_kk' => $request->title_kk,
            'author_id' => $request->user()->id,
            'price' => $request->price,
            'discount' => $request->get('discount', 0),
            'tags' => Tags::toString($request->get('tags', [])),
            'index_order' => $request->get('index_order', 0),
            'comment' => $request->get('comment', ''),
            'delete' => 0
        ]);

        if(!$post->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('price-all-kk');
        Cache::forget('price-all-ru');

        return Response::created('Created');
    }

    public function update(Request $request, string $id)
    {
        $item = Price::where("id", $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price $id not found");
        }

        if($request->has('group_id')) {
            $item->price_group_id = $request->group_id;
        }

        if($request->has('title_ru')) {
            $item->title_ru = $request->title_ru;
        }
        if($request->has('title_kk')) {
            $item->title_ru = $request->title_kk;
        }

        if($request->has('price')) {
            $item->price = $request->price;
        }

        if($request->has('discount')) {
            $item->discount = $request->discount;
        }

        if($request->has('tags')) {
            $item->tags = Tags::toString($request->tags);
        }

        if($request->has('index_order')) {
            $item->index_order = $request->index_order;
        }

        if($request->has('comment')) {
            $item->comment = $request->comment;
        }

        if(!$item->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('price-all-kk');
        Cache::forget('price-all-ru');

        return Response::accepted("Updated");
    }

    public function destroy(string $id)
    {
        $item = Price::where('id', $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price $id not found");
        }

        $item->delete = 1;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price $id delete");
    }

    public function revert(string $id)
    {
        $item = Price::where('id', $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price $id not found");
        }

        $item->delete = 0;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price $id revert");
    }
}
