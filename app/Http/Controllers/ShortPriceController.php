<?php

namespace App\Http\Controllers;

use App\Models\Global\Language;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Price;
use App\Models\PriceGroup;
use App\Models\PriceSearch;
use App\Models\ShortPrice;
use App\Models\ShortPriceSearch;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Validator;
use Cache;
use Str;

class ShortPriceController extends Controller
{
    public function index(Request $request)
    {
        $items = ShortPriceSearch::search($request->all());

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

        $items = Cache::rememberForever("short-price-all-$lang", function () {
            return ShortPrice::where('delete', '0')
                ->get()
                ->makeHidden(['id', 'delete', 'created_at', 'updated_at', 'index_order', 'title_kk', 'title_ru', 'title_en'])
                ->toArray();
        });

        return Response::okJSON($items);
    }

    public function show(int $id) 
    {
        return Response::okJSON(
            ShortPrice::find($id)
            ?? ['message' => "Record $id not found"]
        );
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

        $price = new ShortPrice([
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'adult' => $request->get('adult', 0),
            'student' => $request->get('student', 0),
            'children' => $request->get('children', 0),
            'pensioner' => $request->get('pensioner', 0),
            'index_order' => $request->get('index_order', 0),
            'delete' => 0
        ]);

        if($request->has('all')) {
            $allPrice = $request->input('all');
            $price->setAttribute('adult', $allPrice);
            $price->setAttribute('student', $allPrice);
            $price->setAttribute('children', $allPrice);
            $price->setAttribute('pensioner', $allPrice);
        }

        Cache::forget('short-price-all-kk');
        Cache::forget('short-price-all-ru');
        Cache::forget('short-price-all-en');

        return $price->save()
            ? Response::okJSON(['id' => $price->id])
            : Response::internalServerError('Ops something wrong while saving');
    }

    public function update(Request $request, string $id)
    {
        /** @var Price */
        $price = ShortPrice::where("id", $id)->first()
              ?? Response::notFound("Record $id not found");

        $price->fill( $request->post() );

        Cache::forget('short-price-all-kk');
        Cache::forget('short-price-all-ru');
        Cache::forget('short-price-all-en');

        return $price->save()
            ? Response::accepted("Record updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(string $id)
    {
        $item = ShortPrice::where('id', $id)->first()
            ?? abort(404, "Price $id not found");

        $item->delete = 1;

        Cache::forget('short-price-all-kk');
        Cache::forget('short-price-all-ru');
        Cache::forget('short-price-all-en');

        return $item->save()
            ? Response::accepted("Record deleted")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function revert(string $id)
    {
        $item = ShortPrice::where('id', $id)->first()
            ?? abort(404, "Price $id not found");

        $item->delete = 0;

        Cache::forget('short-price-all-kk');
        Cache::forget('short-price-all-ru');
        Cache::forget('short-price-all-en');

        return $item->save()
            ? Response::accepted("Record reverted")
            : Response::internalServerError("Ops something wrong while saving");
    }
}
