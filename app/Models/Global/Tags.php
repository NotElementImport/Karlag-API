<?php

namespace App\Models\Global;

class Tags
{
    public static function toString(array $tags) {
        asort($tags);
        return json_encode(
            array_map(fn ($item) => "$item", $tags), 
            JSON_UNESCAPED_UNICODE
        );
    }

    public static function fromString(string $json) {
        return json_decode($json, true, 32);
    }
}