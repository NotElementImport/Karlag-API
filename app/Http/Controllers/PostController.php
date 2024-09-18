<?php

namespace App\Http\Controllers;

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
                    fn($item) => $item->makeHidden([ 'id', 'title_ru', 'title_kk', 'content_ru', 'content_kk', 'delete', 'updated_at' ]),
                    $items->items()
                ) // Site
                : $items->items()
                  // Admin
        ;

        return response()->json([ 
            'items' => $filteredItems,
            'meta' => [
                'size'    => $items->total(),
                'perpage' => $items->perPage(),
                'page'    => $items->currentPage()
            ]
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)->first()
            ??  abort(404, "Post $slug not found");

        $post->makeHidden(['id']);

        if(!auth('sanctum')->check()) {
            $post->makeHidden(['title_ru', 'title_kk', 'content_ru', 'content_kk', 'delete', 'slug', 'updated_at']);
        }

        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validate = Validator::make(
            $request->all(),    
            [
                'title' => 'required',
                'content' => 'required',
                'tags' => 'required|array',
                'photo' => 'required|image'
            ]);

        if($validate->fails()) {
            return Response::badRequest($validate->errors()->toArray());
        }

        $slug = Str::slug($request->title);

        // Photo:
        $photo = $request->file('photo');
        $photo->move(public_path('images'), $slug.'.'.pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $filePath = 'images/'.$slug.'.'.pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

        // Create Model:
        /** @var Post */
        $post = new Post([
            'author_id' => $request->user()->id,
            'slug' => $slug,
            'title' => $request->title,
            'content' => $request->content,
            'tags' => Tags::toString($request->tags),
            'imgsrc' => asset($filePath)
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

        if($request->has('title')) {
            $post->title = $request->title;
            $post->slug = Str::slug($post->title);
        }

        if($request->has('content')) {
            $post->content =$request->content;
        }

        if($request->has('tags')) {
            $post->tags =json_encode(array_map(fn ($item) => "[$item]", $request->tags), JSON_UNESCAPED_UNICODE);
        }

        if($request->has('photo')) {
            $photo = $request->file('photo');
            $photo->move(public_path('images'), $post->slug.'.'.pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $post->imgsrc = asset('images/'.$post->slug.'.'.pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        }

        if(!$post->save()) {
            return Response::internalServerError("Ops something wrong while saving");
        }

        Cache::forget('post-all');
        return Response::accepted("Updated");
    }

    public function destroy(string $slug)
    {
        $post = Post::where('slug', $slug)->first();

        if(is_null($post)) {
            return Response::notFound("Post $slug not found");
        }

        $post->delete = 1;
        $post->save();

        Cache::forget('post-all');
        return Response::accepted("Ok, post $slug delete");
    }

    public function revert(string $slug)
    {
        $post = Post::where('slug', $slug)->first();

        if(is_null($post)) {
            return Response::notFound("Post $slug not found");
        }

        $post->delete = 0;
        $post->save();

        Cache::forget('post-all');
        return Response::accepted("Ok, post $slug revert");
    }
}
