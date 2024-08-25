<?php

namespace App\Http\Controllers;

use App\Models\Global\QueryFilter;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Str;
use Validator;

class PostController extends Controller
{
    public function index(Request $request)
    {
        // Main Page:
        if(sizeof($request->query) == 0) {
            $size = 6;

            $cachedPosts = Cache::rememberForever('post-all', function () use(&$size) {
                return Post::where('delete', '0')
                    ->orderBy('created_at','desc')
                    ->limit($size)
                    ->with('author')
                    ->get()
                    ->makeHidden(['id'])
                    ->map(fn ($post) => Post::preparePost($post))
                    ->toArray();
            });

            return Response::many($cachedPosts, sizeof($cachedPosts), $size, 1);
        }
        // Else :

        // Page with All:
        $posts = Post::select('*')
            ->with('author');

        // User mode: ? Not admin mode:
        if(!auth('sanctum')->check()) {
            $posts->where('delete', 0);
        }

        QueryFilter::apply($request,     $posts, 'title',      'text');
        QueryFilter::apply($request,     $posts, 'content',    'text');
        QueryFilter::apply($request,     $posts, 'tags',       'tag');
        QueryFilter::apply($request,     $posts, 'created_at', 'range-date');
        QueryFilter::applySort($request, $posts, 'created_at', 'desc');

        $posts = $posts->paginate(15);

        return Response::manyPaginate(
            $posts, 
            fn ($item) => Post::preparePost($item)->makeHidden(['id'])
        );
    }

    public function show(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->with('author')
            ->first();

        if(is_null($post)) {
            return Response::notFound("Post $slug not found");
        }

        $post->makeHidden(['id']);

        Post::preparePost($post);

        return Response::json($post);
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
