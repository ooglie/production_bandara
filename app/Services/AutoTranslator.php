<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AutoTranslator
{

    public function translate($text, $target)
    {
        $response = Http::post(
            "https://translation.googleapis.com/language/translate/v2",
            [
                "q" => $text,
                "target" => $target,
                "key" => config('services.google_translate.key')
            ]
        );

        return $response['data']['translations'][0]['translatedText'] ?? $text;
    }

}