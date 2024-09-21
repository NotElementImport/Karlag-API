<?php

namespace App\Models\Global;

use Illuminate\Http\Request;

class Language
{
    public static function capture() {
        $lang = Request::capture()->header('Accept-Language', 'ru');
        return $lang;
    }
}