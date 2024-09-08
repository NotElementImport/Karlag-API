<?php

namespace App\Http\Controllers;

use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Price;
use App\Models\PriceGroup;
use Illuminate\Http\Request;
use Validator;
use Cache;

class PriceGroupController extends Controller
{
    public function index()
    {
        // Admin Mode:
        $items = PriceGroup::select('*')
            ->get();

        return Response::json($items);
    }

    public function batchStore(Request $request) {
        // Create Price Group
        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'title_kk' => 'required',
                'prices' => 'required|array'
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $group = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->title_kk,
            'order_index' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        if(!$group->save()) {
            return Response::internalServerError("Ops something wrong while saving price group");
        }

        // Create Prices
        foreach($request->prices as $item) {
            $validate = Validator::make(
                $item,    
                [
                    'title_ru' => 'required',
                    'title_kk' => 'required',
                    'price' => 'required',
                ]);
    
            if($validate->fails()) {
                return Response::badRequest($validate->errors()->toArray());
            }
    
            $price = new Price([
                'price_group_id' => $group->id,
                'title_ru' => $item['title_ru'],
                'title_kk' => $item['title_kk'],
                'author_id' => $request->user()->id,
                'price' => $item['price'],
                'discount' => $item['discount'] ?? 0,
                'tags' => array_key_exists('tags', $item) ? Tags::toString($item['tags']) : '[]',
                'index_order' => $item['index_order'] ?? 0,
                'comment' => $item['comment'] ?? '',
                'delete' => 0
            ]);
    
            if(!$price->save()) {
                return Response::internalServerError("Ops something wrong while saving price");
            }
        }

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return Response::created('Created');
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'title_kk' => 'required',
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $post = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->title_kk,
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
        $item = PriceGroup::where("id", $id)->first();

        if(is_null($item)) {
            return Response::notFound("Price $id not found");
        }

        if($request->has('title_ru')) {
            $item->title_ru = $request->title_ru;
        }
        if($request->has('title_kk')) {
            $item->title_ru = $request->title_kk;
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
