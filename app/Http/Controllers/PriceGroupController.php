<?php

namespace App\Http\Controllers;

use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Price;
use App\Models\PriceGroup;
use App\Models\PriceGroupSearch;
use Illuminate\Http\Request;
use Str;
use Validator;
use Cache;

class PriceGroupController extends Controller
{
    public function index(Request $request)
    {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        $items = PriceGroupSearch::search($request->all());

        $prepared = array_map(
            function($item) {
                if(isset($item->prices)) {
                    $cortage = [];
                    foreach($item->prices as &$price) {
                        $cortage[Str::slug($price->title_ru)] = $price;
                    }
                    $item->setAttribute('childs', $cortage);
                    $item->makeHidden([ 'prices' ]);
                }
                return $item;
            },
            $items->items()
        );

        return response()->json([ 
            'items' => $prepared,
            'meta' => [
                'size'    => $items->total(),
                'perpage' => $items->perPage(),
                'page'    => $items->currentPage()
            ]
        ]);
    }

    public function batchStore(Request $request) {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        // Create Price Group
        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'prices' => 'required|array'
            ]);

        $validate->fails()
            ?: abort(400, $validate->errors()->toArray());

        $group = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'order_index' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        $group->save()
            ?: abort(500, 'Ops something wrong while saving price group');

        // Create Prices
        foreach($request->prices as $item) {
            $validate = Validator::make(
                $item,    
                [
                    'title_ru' => 'required',
                    'price' => 'required',
                ]);
    
            $validate->fails()
                ?: abort(400, $validate->errors()->toArray());
    
            $price = new Price([
                'price_group_id' => $group->id,
                'title_ru' => $item['title_ru'],
                'title_kk' => $item['title_kk'],
                'title_en' => $item['title_en'],
                'author_id' => $request->user()->id,
                'price' => $item['price'],
                'discount' => $item['discount'] ?? 0,
                'tags' => array_key_exists('tags', $item) ? Tags::toString($item['tags']) : '[]',
                'index_order' => $item['index_order'] ?? 0,
                'comment' => $item['comment'] ?? '',
                'delete' => 0
            ]);
    
            $price->save()
                ?: abort(500, 'Ops something wrong while saving price');
        }

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::created('Created');
    }

    public function store(Request $request)
    {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $post = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'order_index' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        if(!$post->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        return Response::created('Created');
    }

    public function update(Request $request, string $id)
    {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        $item = PriceGroup::where("id", $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price $id not found");
        }

        if($request->has('title_ru')) {
            $item->title_ru = $request->title_ru;
        }
        if($request->has('title_kk')) {
            $item->title_kk = $request->title_kk;
        }
        if($request->has('title_en')) {
            $item->title_kk = $request->title_en;
        }

        if($request->has('index_order')) {
            $item->index_order = $request->index_order;
        }

        if(!$item->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Updated");
    }

    public function destroy(string $id)
    {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        $item = PriceGroup::where('id', $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price group $id not found");
        }

        $item->delete = 1;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price group $id delete");
    }

    public function revert(string $id)
    {
        auth('sanctum')->check()
            ?: abort(401, 'Unathorized');

        $item = PriceGroup::where('id', $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price group $id not found");
        }

        $item->delete = 0;
        $item->save();

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::accepted("Ok, price group $id revert");
    }
}
