<?php

use DigitalCoreHub\Toon\Facades\Toon;

if (! function_exists('toon_encode')) {
    /**
     * Encode data to TOON format.
     *
     * @param  array|string  $data
     */
    function toon_encode($data): string
    {
        return Toon::encode($data);
    }
}

if (! function_exists('toon_decode')) {
    /**
     * Decode TOON format string to array.
     */
    function toon_decode(string $text): array
    {
        return Toon::decode($text);
    }
}
