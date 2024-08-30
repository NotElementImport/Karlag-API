<?php

namespace App\Models\Global;

class Response
{
    public static function manyPaginate(&$object, $map) {
        return response([ 
            'items' => array_map(fn ($item) => $map($item), $object->items()),
            'meta' => [
                'size'    => $object->total(),
                'perpage' => $object->perPage(),
                'page'    => $object->currentPage()
            ]
        ]);
    }

    public static function many(&$object, $size, $perPage, $page) {
        return response([ 
            'items' => is_array($object) ? $object : $object->toArray(),
            'meta' => [
                'size'    => $size,
                'perpage' => $perPage,
                'page'    => $page
            ]
        ]);
    }

    public static function json(&$object) {
        return response()->json(is_array($object) ? $object : $object->toArray());
    }

    public static function forbiden($message) {
        return response([ 'message' => $message ], 403);
    }

    public static function notFound($message) {
        return response([ 'message' => $message ], 404);
    }

    public static function unauthorized($message) {
        return response([ 'message' => $message ], 401);
    }

    public static function conflict($message) {
        return response([ 'message' => $message ], 409);
    }

    public static function badRequest($message) {
        return response([ 'message' => $message ], 400);
    }

    public static function notImplemented($message) {
        return response([ 'message' => $message ], 501);
    }

    public static function internalServerError($message) {
        return response([ 'message' => $message ], 500);
    }

    public static function unknownError($message) {
        return response([ 'message' => $message ], 520);
    }

    public static function ok($message) {
        return response([ 'message' => $message ], 200);
    }

    public static function created($message) {
        return response([ 'message' => $message ], 201);
    }

    public static function accepted($message) {
        return response([ 'message' => $message ], 202);
    }

    public static function empty() {
        return response([], 204);
    }
}