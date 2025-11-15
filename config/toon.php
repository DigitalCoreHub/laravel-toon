<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TOON Format Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the TOON format encoding and decoding.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Indentation
    |--------------------------------------------------------------------------
    |
    | The number of spaces used for indentation in the TOON output.
    |
    */
    'indentation' => 4,

    /*
    |--------------------------------------------------------------------------
    | Key Separator
    |--------------------------------------------------------------------------
    |
    | The separator used between keys in the TOON format.
    |
    */
    'key_separator' => ', ',

    /*
    |--------------------------------------------------------------------------
    | Line Break
    |--------------------------------------------------------------------------
    |
    | The line break character used in the TOON output.
    |
    */
    'line_break' => PHP_EOL,

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, decoding will throw exceptions for any formatting issues.
    | When disabled, it will attempt to parse more leniently.
    |
    */
    'strict_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Preserve Order
    |--------------------------------------------------------------------------
    |
    | Whether to preserve the original JSON key ordering in the output.
    |
    */
    'preserve_order' => true,
];
