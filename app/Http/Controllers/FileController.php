<?php

namespace App\Http\Controllers;

use App\Models\FileSystem;
use App\Models\File;
use App\Models\Global\Response;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index(Request $request)
    {
        auth('sanctum')->check() 
            ?: Response::unauthorized('Unauthorized', true);
       
        $query = File::select();

        $query->orderBy('id', 'desc');

        if($request->has('name') && $name = $request->get('name'))
            $query->whereLike('src', "%$name%");
        if($request->has('place'))
            $query->where('place', '=', $request->get('place'));

        $items = $query->paginate(15);

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

    public function uploadImages(Request $request) {
        $fileManager = FileSystem::new($request);

        $fileManager->batchUploadImages();

        return Response::created('ok');
    }

    public function uploadDocumets(Request $request) {
        $fileManager = FileSystem::new($request);
        
        $fileManager->batchUploadDocuments();

        return Response::created('ok');
    }

    public function destroy(int $id) {
        $file = File::find($id)
            ?? Response::notFound('Record not found');

        if(!unlink(base_path("/public$file->src")))
            return Response::internalServerError('File not exist');

        $file->delete();
        return Response::ok('ok');
    }
}
