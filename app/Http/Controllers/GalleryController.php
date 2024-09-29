<?php

namespace App\Http\Controllers;

use App\Models\FileSystem;
use App\Models\Gallery;
use App\Models\GallerySearch;
use App\Models\Global\Response;
use File;
use Illuminate\Http\Request;
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

    public function store(Request $request) {
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
            $fileManager->uploadCustom($fileKey, $dir);
        }

        return Response::created('Files created');
    }

    public function destroy(Request $request) {
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

        return Response::accepted('Files deleted');
    }
}
