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
    
        // Hidden some items if not admin (Thin mode)
        $filteredItems = !$isAuthorized
            // Site
            ? array_map(function ($item) {
                if(isset($item->image))
                    $item->image->makeHidden(['id', 'place']) ;

                return $item->makeHidden([ 'id', 'title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'updated_at', 'image_id' ]);
            }, $items->items())
            // Admin
            : $items->items();

        return Response::okJSON([ 
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
             ?? Response::notFound("Record $slug not found", true);

        $post->makeHidden(['id']);

        !auth('sanctum')->check()
            ?: $post->makeHidden(['title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'slug', 'updated_at']);

        return Response::okJSON($post);
    }

    public function store(Request $request)
    {
        // Validate:

        $validate = Validator::make(
            $request->all(),    
            [
                'title_ru' => 'required',
                'content_ru' => 'required',
                'tags' => 'required|array'
            ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        if(isset($_FILES['photo']))
            FileSystem::validateFile('photo', 'image/', '10M');

        // Custom Attributes:

        $slug = Str::slug($request->title_ru);

        // Files:

        $fileManager = FileSystem::new($request);

        // Create Model:

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

        return $post->save()
            ? Response::created("Created")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function update(Request $request, string $slug)
    {
        /** @var Post */
        $post = Post::where("slug", $slug)->first()
             ?? Response::notFound("Record $slug not found", true);

        $post->fill( $request->all() );

        $post->slug = Str::slug($request->title_ru);

        if($request->has('tags'))
            $post->setAttribute('tags', Tags::toString($request->input('tags')));

        if(isset($_FILES['photo'])) {
            FileSystem::validateFile('photo', 'image/', '10M');
            $fileManager = FileSystem::new($request);
            $post->image_id = $fileManager->uploadImage('photo', "post-$slug");
        }

        return $post->save()
            ? Response::accepted("Updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(int $id)
    {
        $post = Post::where('id', '=', $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $post->delete = 1;

        return $post->save()
            ? Response::accepted("Record $id delete")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function revert(int $id)
    {
        $post = Post::where('id', '=', $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $post->delete = 0;

        return $post->save()
            ? Response::accepted("Record $id reverted")
            : Response::internalServerError("Ops something wrong while saving");
    }
}
