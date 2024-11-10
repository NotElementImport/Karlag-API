<?php

namespace App\Models\Global;

use Illuminate\Http\Request;

class CacheControl
{
    public static function clear(string $key, int $repeat = 1) {
        for ($i = 0; $i < $repeat; $i++) {
            cache()->forget("$key-kk");
            cache()->forget("$key-ru");
            cache()->forget("$key-en");
        }
    }
}