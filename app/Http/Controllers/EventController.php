<?php

namespace App\Http\Controllers;

use App\Models\Events;
use App\Models\EventsSearch;
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

class EventController extends Controller
{
    public function index(Request $request)
    {
        $isAuthorized = auth('sanctum')->check();
        $items = EventsSearch::search($request->all(), $isAuthorized);

        // Hidden some items if not admin (Thin mode)
        $filteredItems = !$isAuthorized
            // Site
            ? array_map(function ($item) {
                if (isset($item->image))
                    $item->image->makeHidden(['id', 'place']);

                return $item->makeHidden(['id', 'title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'updated_at', 'image_id']);
            }, $items->items())
            // Admin
            : $items->items();

        return Response::okJSON([
            'items' => $filteredItems,
            'meta' => [
                'size' => $items->total(),
                'lastpage' => $items->lastPage(),
                'perpage' => $items->perPage(),
                'page' => $items->currentPage()
            ]
        ]);
    }

    public function show(string $slug)
    {
        $event = Events::where('slug', $slug)->with('image')->first()
            ?? Response::notFound("Record $slug not found", true);

        $event->makeHidden(['id']);

        // Thin mode:
        if (!auth('sanctum')->check())
            $event->makeHidden(['title_ru', 'title_kk', 'title_en', 'content_ru', 'content_kk', 'content_en', 'delete', 'slug', 'updated_at']);

        return Response::okJSON($event);
    }

    public function store(Request $request)
    {
        // Validate:

        $validate = Validator::make(
            $request->all(),
            [
                'title_ru' => 'required',
                'start_at' => 'required',
                'content_ru' => 'required'
            ]
        );

        if ($validate->fails())
            return Response::badRequest($validate->errors()->toArray());

        if (isset($_FILES['photo']))
            FileSystem::validateFile('photo', 'image/', '10M');

        // Custom Attributes:

        $slug = now()->format("Y-m-d") . '-' . Str::slug($request->title_ru);

        // Files:

        $fileManager = FileSystem::new($request);

        // Create Model:
        $event = new Events([
            'author_id' => $request->user()->id,
            'slug' => $slug,
            'start_at' => $request->start_at,
            'title_ru' => $request->title_ru,
            'title_kk' => $request->get('title_kk'),
            'title_en' => $request->get('title_en'),
            'content_ru' => $request->content_ru,
            'content_kk' => $request->get('content_kk'),
            'content_en' => $request->get('content_en'),
            'delete' => 0,

            'tags' => Tags::toString($request->get('tags', [])),

            'image_id' => isset($_FILES['photo'])
                ? $fileManager->uploadImage('photo', "post-$slug")
                : 0 // aka null
        ]);

        return $event->save()
            ? Response::created('Created')
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function update(Request $request, string $slug)
    {
        /** @var Events */
        $event = Events::where("slug", $slug)->first()
            ?? Response::notFound("Record $slug not found", true);

        $event->fill($request->all());

        // Custom Attributes:

        $event->slug = date('Y-m-d', strtotime($event->created_at)) . 
            '-' . 
            Str::slug($request->title_ru);

        if ($request->has('tags'))
            $event->setAttribute('tags', Tags::toString($request->input('tags')));

        if (isset($_FILES['photo'])) {
            FileSystem::validateFile('photo', 'image/', '10M');
            $fileManager = FileSystem::new($request);
            $event->image_id = $fileManager->uploadImage('photo', "post-$slug");
        }

        return $event->save()
            ? Response::accepted("Updated")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function destroy(int $id)
    {
        $event = Events::where("id", "=", $id)->first()
            ?? Response::notFound("Record $id not found", true);

        $event->delete = 1;

        return $event->save()
            ? Response::accepted("Record $id delete")
            : Response::internalServerError("Ops something wrong while saving");
    }

    public function revert(int $id)
    {
        $event = Events::where("id", "=", $id)->first()
            ?? Response::notFound("Record $id not found", true);

        $event->delete = 0;

        return $event->save()
            ? Response::accepted("Record $id reverted")
            : Response::internalServerError("Ops something wrong while saving");
    }
}
