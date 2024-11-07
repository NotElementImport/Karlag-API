<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileSystem;
use App\Models\Gallery;
use App\Models\GallerySearch;
use App\Models\Global\Response;
use Cache;
use File;
use Validator;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $items = GallerySearch::search($request->all());

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

    public function dirs(Request $request) 
    {
        $items = Cache::rememberForever('gallery-dirs', function() {
            return Gallery::select(['place'])
                ->distinct()
                ->whereLike('place', 'gallery/%')
                ->get()
                ->map(function ($item) {
                    return str_replace('gallery/', '', $item->place);
                });
        });

        return Response::okJSON($items);
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'dir' => 'required|string',
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        $dir = "gallery/".$request->input('dir');

        $fileManager = FileSystem::new($request);

        foreach(array_keys($_FILES) as $fileKey) {
            FileSystem::validateFile($fileKey, 'image/', '25M');
            $fileManager->uploadCustom($fileKey, $dir);
        }

        Cache::forget('gallery-dirs');

        return Response::created('Files created');
    }

    public function destroy(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'id' => 'required|array',
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        foreach($request->input('id') as $id) {
            /** @var Gallery */
            $item = Gallery::where('id', '=', $id)->first()
                 ?? Response::notFound("Record $id not found", true);

            try {
                unlink(base_path("/public$item->src"));
            }
            catch(\Exception $e) {}

            if(!$item->delete())
                return Response::internalServerError('While file delete something wrong database');
        }

        Cache::forget('gallery-dirs');

        return Response::accepted('Files deleted');
    }
}
