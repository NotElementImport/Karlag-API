<?php

namespace App\Models\Global;

class Tags
{
    public static function toString(array $tags) {
        asort($tags);
        return json_encode(
            array_map(fn ($item) => "[$item]", $tags), 
            JSON_UNESCAPED_UNICODE
        );
    }

    public static function fromString(string $json) {
        return array_map(
            fn ($item) => substr($item, 1, -1), 
            json_decode(
                $json, 
                true, 
                32, 
                JSON_UNESCAPED_UNICODE
            )
        );
    }
}