<?php

if (!function_exists('country_flag_emoji')) {
    function country_flag_emoji(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        if (strlen($code) !== 2 || !ctype_alpha($code)) {
            return '';
        }

        $chr = function (int $cp): string {
            if (function_exists('mb_chr')) {
                return mb_chr($cp, 'UTF-8');
            }
            if (class_exists(\IntlChar::class)) {
                return \IntlChar::chr($cp);
            }
            return '';
        };

        $offset = 127397; // Regional Indicator Symbol offset
        return $chr(ord($code[0]) + $offset) . $chr(ord($code[1]) + $offset);
    }
}
