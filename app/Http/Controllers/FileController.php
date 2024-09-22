<?php

namespace App\Http\Controllers;

use App\Models\FileSystem;
use App\Models\File;
use App\Models\Global\QueryFilter;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FileController extends Controller
{
    public function index(Request $request)
    {
        auth('sanctum')->check() 
            ?: abort(401, 'Unauthorized');
       
        $query = File::select();

        $query->orderBy('id', 'desc');

        if($request->has('name') && $name = $request->get('name'))
            $query->whereLike('src', "%$name%");
        if($request->has('place'))
            $query->where('place', '=', $request->get('place'));

        $items = $query->paginate('15');

        return responseJson([ 
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

        return response()->json(['message' => 'ok'], 201);
    }

    public function uploadDocumets(Request $request) {
        $fileManager = FileSystem::new($request);
        
        $fileManager->batchUploadDocuments();

        return response()->json(['message' => 'ok'], 201);
    }
}
