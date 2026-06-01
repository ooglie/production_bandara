<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AutoTranslationService
{
    public function configured(): bool
    {
        return env('AUTO_TRANSLATE_DRIVER') === 'google'
            && filled(env('GOOGLE_TRANSLATE_API_KEY'));
    }

    public function translateText(?string $text, string $targetLocale, string $sourceLocale = 'en'): ?string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        if ($targetLocale === $sourceLocale) {
            return $text;
        }

        if (!$this->configured()) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post(
                    'https://translation.googleapis.com/language/translate/v2?key=' . env('GOOGLE_TRANSLATE_API_KEY'),
                    [
                        'q' => $text,
                        'source' => $sourceLocale,
                        'target' => $targetLocale,
                        'format' => 'text',
                    ]
                );

            if (!$response->successful()) {
                return null;
            }

            $translated = data_get($response->json(), 'data.translations.0.translatedText');

            if (!is_string($translated) || trim($translated) === '') {
                return null;
            }

            return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}