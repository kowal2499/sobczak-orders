<?php

namespace App\Utilities;

class Slugger
{
    public static function slugify(string $text): string
    {
        // Transliteracja znaków UTF-8 na ASCII (np. polskie znaki)
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        // Zamień wszystko co nie jest literą, cyfrą lub myślnikiem na myślnik
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower($slug));
        // Usuń wielokrotne myślniki i myślniki na początku/końcu
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');

        return $slug ?: 'file';
    }
}
