<?php

namespace App\Http\Controllers;

use App\Models\Events;
use App\Models\FileSystem;
use App\Models\Global\Response;
use App\Models\Global\Tags;
use App\Models\Repressed;
use App\Models\RepressedSearch;
use Illuminate\Http\Request;
use Str;
use Validator;

class RepressedController extends Controller
{
    public function index(Request $request)
    {
        $isAuthorized = auth('sanctum')->check();

        $items = RepressedSearch::search($request->all(), $isAuthorized);
    
        $filteredItems = !$isAuthorized // Check auth in Request
            // Site    
            ? array_map(
                fn ($item) => $item->makeHidden([ 'id', 'content_ru', 'content_kk', 'content_en', 'delete', 'updated_at' ]),
                $items->items()
            ) 
            // Admin
            : $items->items();

        return Response::okJSON([ 
            'items' => $filteredItems,
            'meta'  => [
                'size'     => $items->total(),
                'lastpage' => $items->lastPage(),
                'perpage'  => $items->perPage(),
                'page'     => $items->currentPage()
            ]
        ]);
    }

    public function show(string $slug)
    {
        $post = Repressed::where('slug', '=', $slug)->first()
             ?? Response::notFound("Record $slug not found", true);

        $post->makeHidden(['id']);

        if(!auth('sanctum')->check())
            $post->makeHidden(['content_ru', 'content_kk', 'content_en', 'delete', 'slug', 'updated_at']);

        return Response::okJSON($post);
    }

    public function store(Request $request)
    {
        // Validate

        $validate = Validator::make(
            $request->all(),    
            [ 'fio' => 'required' ]);

        if($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        // Custom Attribute Builder

        $slug = Str::slug($request->fio);
        
        $birthday = $request->get('birthday_year');
        if(isset($birthday))
            $slug .= "-$birthday";

        // Create Record

        $post = new Repressed([
            'author_id' => $request->user()->id,
            'slug' => $slug,
            'fio'  => $request->fio,
            'content_ru' => $request->get('content_ru', ''),
            'content_kk' => $request->get('content_kk'),
            'content_en' => $request->get('content_en'),
            'birthday_year' => $birthday,
            'death_year'    => $request->get('death_year'),
            'delete' => 0
        ]);

        return $post->save()
            ? Response::created('Created') // OK
            : Response::internalServerError("Ops something wrong while saving"); // ERROR
    }

    public function update(Request $request, string $slug)
    {
        /** @var Repressed */
        $post = Repressed::where("slug", '=', $slug)->first()
             ?? Response::notFound("Record $slug not found", true);

        $post->fill( $request->all() );

        $post->slug = Str::slug($post->fio);

        if($post->birthday_year) {
            $birthday = $post->birthday_year;
            $post->slug .= "-$birthday";
        }

        return $post->save()
            ? Response::accepted("Updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(int $id)
    {
        $post = Repressed::where('id', '=', $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $post->delete = 1;

        return $post->save()
            ? Response::accepted("Record $id delete")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function revert(int $id)
    {
        $post = Repressed::where('id', '=', $id)->first()
             ?? Response::notFound("Record $id not found", true);

        $post->delete = 0;

        return $post->save()
            ? Response::accepted("Record $id revert")
            : Response::internalServerError("Ops something wrong while saving");
    }
}
