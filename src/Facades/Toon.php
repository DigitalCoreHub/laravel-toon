<?php

namespace DigitalCoreHub\Toon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encode(array|string $json)
 * @method static array decode(string $toon)
 * @method static \DigitalCoreHub\Toon\ToonBuilder fromJson(string $json)
 * @method static \DigitalCoreHub\Toon\ToonBuilder fromArray(array $array)
 * @method static \DigitalCoreHub\Toon\ToonBuilder fromToon(string $toon)
 *
 * @see \DigitalCoreHub\Toon\Toon
 */
class Toon extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'toon';
    }
}
