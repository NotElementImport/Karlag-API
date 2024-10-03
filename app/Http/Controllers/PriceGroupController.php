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

        return Response::okJSON([ 
            'items' => $prepared,
            'meta' => [
                'size'     => $items->total(),
                'lastpage' => $items->lastPage(),
                'perpage'  => $items->perPage(),
                'page'     => $items->currentPage()
            ]
        ]);
    }

    public function show(int $id)
    {
        $group = PriceGroupSearch::where('id',  '=', $id)->with('prices')->first()
            ?? Response::notFound("Record $id not found", true);

        if(isset($item->prices)) {
            $cortage = [];
            foreach($group->prices as &$price) {
                $cortage[Str::slug($price->title_ru)] = $price;
            }
            $group->setAttribute('childs', $cortage);
            $group->makeHidden([ 'prices' ]);
        }

        return Response::okJSON($group);
    }

    public function batchStore(Request $request) 
    {
        // Create Price Group
        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'prices' => 'required|array'
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        $group = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'order_index' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        $group->save()
            ?: Response::internalServerError('Ops something wrong while saving price group', true);

        // Create Prices
        foreach($request->prices as $item) {
            $validate = Validator::make(
                $item,    
                [
                    'title_ru' => 'required',
                    'price' => 'required',
                ]);

            if($validate->fails())
                return Response::badRequest($validate->errors()->toArray());

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
                ?: Response::internalServerError('Ops something wrong while saving price', true);
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
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        $post = new PriceGroup([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'order_index' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        return $post->save()
            ? Response::created('Created')
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function update(Request $request, string $id)
    {
        $item = PriceGroup::where("id", $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $item->fill( $request->all() );

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return $item->save()
            ? Response::accepted("Updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(string $id)
    {
        $item = PriceGroup::where('id', $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $item->delete = 1;

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return $item->save()
            ? Response::accepted("Record $id deleted")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function revert(string $id)
    {
        $item = PriceGroup::where("id", "=", $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $item->delete = 0;

        Cache::forget('price-all-ru');
        Cache::forget('price-all-kk');

        return $item->save()
            ? Response::accepted("Record $id revert")
            : Response::internalServerError("Ops something wrong while saving");
    }
}
