<?php

namespace App\Models\Global;

use Illuminate\Http\Request;

class Language
{
    private static $_capturedLang = null;

    public static function capture() {
        if(is_null(Language::$_capturedLang)) {
            $lang = Request::capture()->header('Accept-Language', 'ru');

            if(str_contains($lang, '-'))
                $lang = strtolower(explode('-', $lang)[0]);

            if($lang != 'ru' || $lang != 'en' || $lang != 'kk')
                $lang = 'ru';

            Language::$_capturedLang = $lang;
        }

        return Language::$_capturedLang;
    }
}