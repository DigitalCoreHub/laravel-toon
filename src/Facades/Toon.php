<?php

namespace DigitalCoreHub\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encode(array|string $json)
 * @method static array decode(string $toon)
 *
 * @see \DigitalCoreHub\Toon\Toon
 */
class Toon extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'toon';
    }
}

