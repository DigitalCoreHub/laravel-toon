<?php

namespace DigitalCoreHub\Toon\Blade;

use DigitalCoreHub\Toon\Facades\Toon;

class ToonDirective
{
    /**
     * Compile the @toon directive.
     */
    public static function compile(string $expression): string
    {
        return "<?php echo '<pre>' . e(\\DigitalCoreHub\\Toon\\Facades\\Toon::encode({$expression})) . '</pre>'; ?>";
    }
}
