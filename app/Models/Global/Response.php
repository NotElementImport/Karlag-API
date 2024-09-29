<?php

namespace App\Models\Global;

class Response
{
    public static function okJSON($object) {
        return responseJson(is_array($object) ? $object : $object->toArray());
    }

    public static function forbiden($message, $abort = false) {
        if($abort)
            abort(403, $message);
        return response([ 'message' => $message ], 403);
    }

    public static function notFound($message, $abort = false) {
        if($abort)
            abort(404, $message);
        return response([ 'message' => $message ], 404);
    }

    public static function unauthorized($message, $abort = false) {
        if($abort)
            abort(401, $message);
        return response([ 'message' => $message ], 401);
    }

    public static function conflict($message, $abort = false) {
        if($abort)
            abort(409, $message);
        return response([ 'message' => $message ], 409);
    }

    public static function badRequest($message, $abort = false) {
        if($abort)
            abort(400, $message);
        return response([ 'message' => $message ], 400);
    }

    public static function notImplemented($message, $abort = false) {
        if($abort)
            abort(501, $message);
        return response([ 'message' => $message ], 501);
    }

    public static function internalServerError($message, $abort = false) {
        if($abort)
            abort(500, $message);
        return response([ 'message' => $message ], 500);
    }

    public static function unknownError($message, $abort = false) {
        if($abort)
            abort(520, $message);
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