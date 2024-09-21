<?php

namespace App\Http\Controllers;

use App\Models\FileSystem;
use App\Models\Global\QueryFilter;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Post;
use App\Models\PostSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Str;
use Validator;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $isAuthorized = auth('sanctum')->check();
        $items = PostSearch::search($request->all(), $isAuthorized);
    
        $filteredItems =
            !$isAuthorized
                ? array_map(
                    function ($item) {
                        if(isset($item->image))
                            $item->image->makeHidden(['id', 'place']) ;

                        return $item->makeHidden([ 'id', 'title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'updated_at', 'image_id' ]);
                    },
                    $items->items()
                ) // Site
                : $items->items()
                  // Admin
        ;

        return response()->json([ 
            'items' => $filteredItems,
            'meta' => [
                'size'     => $items->total(),
                'lastpage' => $items->lastPage(),
                'perpage'  => $items->perPage(),
                'page'     => $items->currentPage()
            ]
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)->with('image')->first()
            ??  abort(404, "Post $slug not found");

        $post->makeHidden(['id']);

        if(!auth('sanctum')->check()) {
            $post->makeHidden(['title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'slug', 'updated_at']);
        }

        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'content_ru' => 'required',
                'tags' => 'required|array'
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $slug = Str::slug($request->title_ru);

        // Photo:
        $fileManager = FileSystem::new($request);

        // Create Model:
        /** @var Post */
        $post = new Post([
            'author_id' => $request->user()->id,
            'slug' => $slug,
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'content_ru' => $request->content_ru,
            'content_kk' => $request->get('content_kk'),
            'content_en' => $request->get('content_en'),
            'tags' => Tags::toString($request->tags),
            'image_id' => isset($_FILES['photo']) 
                ? $fileManager->uploadImage('photo', "post-$slug") 
                : 0 // aka null
        ]);

        if(!$post->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('post-all');
        return Response::created('Created');
    }

    public function update(Request $request, string $slug)
    {
        /** @var Post */
        $post = Post::where("slug", $slug)->first();

        if(is_null($post)) {
            return Response::notFound("Post $slug not found");
        }

        if($request->has('title_ru')) {
            $post->title_ru = $request->title_ru;
            $post->slug = Str::slug($post->title);
        }
        if($request->has('title_kk')) {
            $post->title_kk = $request->title_kk;
        }
        if($request->has('title_en')) {
            $post->title_en = $request->title_en;
        }

        if($request->has('content_ru')) {
            $post->content_ru = $request->content_ru;
        }
        if($request->has('content_kk')) {
            $post->content_kk = $request->content_kk;
        }
        if($request->has('content_en')) {
            $post->content_en = $request->content_en;
        }

        if($request->has('tags')) {
            $post->tags =json_encode(array_map(fn ($item) => "[$item]", $request->tags), JSON_UNESCAPED_UNICODE);
        }

        if(isset($_FILES['photo'])) {
            $fileManager = FileSystem::new($request);
            $post->image_id = $fileManager->uploadImage('photo', "post-$slug");
        }

        if(!$post->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('post-all');
        return Response::accepted("Updated");
    }

    public function destroy(int $id)
    {
        $post = Post::where('id', '=', $id)->first();

        if(is_null($post)) {
            return Response::notFound("Post $id not found");
        }

        $post->delete = 1;
        $post->save();

        Cache::forget('post-all');
        return Response::accepted("Ok, post $id delete");
    }

    public function revert(int $id)
    {
        $post = Post::where('id', '=', $id)->first();

        if(is_null($post)) {
            return Response::notFound("Post $id not found");
        }

        $post->delete = 0;
        $post->save();

        Cache::forget('post-all');
        return Response::accepted("Ok, post $id revert");
    }
}
