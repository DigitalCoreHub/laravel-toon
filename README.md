# Laravel Toon

A lightweight Laravel package that converts standard JSON into **TOON** format - a human-readable, ultra-minimal, line-based data format.

[![Latest Version](https://img.shields.io/badge/version-0.4.0-blue.svg)](https://github.com/digitalcorehub/laravel-toon)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)

**🇹🇷 [Türkçe Dokümantasyon için tıklayın](README_TR.md)**

## Features

- ✅ Convert JSON to TOON format
- ✅ Ultra-minimal, human-readable output
- ✅ Preserves JSON key ordering
- ✅ Supports nested arrays and objects
- ✅ CLI command for file conversion
- ✅ Laravel Facade support
- ✅ Full test coverage

## Installation

Install the package via Composer:

```bash
composer require digitalcorehub/laravel-toon
```

The package will automatically register its service provider and facade.

## Requirements

- PHP 8.3 or higher
- Laravel 10.x, 11.x, or 12.x

## Usage

### Helper Functions

The package provides global helper functions for easy access:

```php
// Encode to TOON
$toon = toon_encode(['id' => 1, 'name' => 'Test']);
// or
$toon = toon_encode('{"id": 1, "name": "Test"}');

// Decode from TOON
$array = toon_decode("id, name;\n1, Test");
```

### Using the Facade

```php
use DigitalCoreHub\Toon\Facades\Toon;

// Encode from array
$json = [
    'id' => 1,
    'name' => 'Test Product',
    'price' => 99.99
];

$toon = Toon::encode($json);
// Output:
// id, name, price;
// 1, Test Product, 99.99
```

### Fluent Interface

The package supports a fluent builder-style API:

```php
// From JSON string
$toon = Toon::fromJson('{"id": 1, "name": "Test"}')->encode();

// From array
$toon = Toon::fromArray(['id' => 1, 'name' => 'Test'])->encode();

// From TOON string
$array = Toon::fromToon("id, name;\n1, Test")->decode();
```

The fluent interface is especially useful for method chaining and readability.

### Encode from JSON String

```php
$jsonString = '{"id": 1, "name": "Test Product", "price": 99.99}';
$toon = Toon::encode($jsonString);
```

### Arrays with Objects

```php
$json = [
    'reviews' => [
        [
            'id' => 1,
            'customer' => 'John Doe',
            'rating' => 5
        ],
        [
            'id' => 2,
            'customer' => 'Jane Smith',
            'rating' => 4
        ]
    ]
];

$toon = Toon::encode($json);
// Output:
// reviews[2]{
//   id, customer, rating;
//   1, John Doe, 5
//   2, Jane Smith, 4
// }
```

### Nested Structures

```php
$json = [
    'product' => 'Laptop',
    'specs' => [
        'cpu' => 'Intel i7',
        'ram' => '16GB'
    ],
    'reviews' => [
        ['id' => 1, 'rating' => 5],
        ['id' => 2, 'rating' => 4]
    ]
];

$toon = Toon::encode($json);
```

### Blade Directive

Use the `@toon()` directive in your Blade templates to display TOON output:

```blade
@toon($data)
```

The directive automatically:
- Encodes the data to TOON format
- Wraps it in a `<pre>` tag
- Escapes HTML for safe output

**Example:**

```blade
<!-- In your Blade template -->
<div class="toon-output">
    @toon(['id' => 1, 'name' => 'Test Product', 'price' => 99.99])
</div>
```

**Output:**
```html
<div class="toon-output">
    <pre>id, name, price;
1, Test Product, 99.99</pre>
</div>
```

### Logging Support

Log data in TOON format using the `Log::toon()` macro:

```php
use Illuminate\Support\Facades\Log;

$data = ['id' => 1, 'name' => 'Test'];
Log::toon($data); // Logs at 'info' level

// Specify log level
Log::toon($data, 'debug');

// Specify channel
Log::toon($data, 'info', 'daily');
```

The macro encodes your data to TOON format and logs it through Laravel's logging system.

### Console Styling

Get colored TOON output for console/terminal:

```php
use DigitalCoreHub\Toon\Facades\Toon;

$data = ['id' => 1, 'name' => 'Test', 'active' => true];
$colored = Toon::console($data, $output); // $output is optional OutputInterface

// In Artisan commands
$this->line(Toon::console($data, $this->output));
```

**Syntax Highlighting:**
- Keys: Yellow
- Strings: Green
- Numbers: Blue
- Booleans: Magenta
- Brackets: Cyan

### Laravel Debugbar Integration

If you have [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) installed, the package automatically registers a TOON panel that shows:

- Recent encode/decode operations
- Performance timing (duration in milliseconds)
- Metadata (key count, row count, line count)
- Input/output preview

The integration is **automatic** - no configuration needed. If Debugbar is not installed, the package works normally without it.

**Note:** Debugbar integration is optional and does not affect package functionality if Debugbar is not installed.

### Decode TOON to Array

```php
use DigitalCoreHub\Toon\Facades\Toon;

// Decode from TOON string
$toon = "reviews[1]{
  id, customer, rating, comment, verified;
  101, Alex Rivera, 5, Excellent!, true
}";

$array = Toon::decode($toon);
// Returns:
// [
//     [
//         'id' => 101,
//         'customer' => 'Alex Rivera',
//         'rating' => 5,
//         'comment' => 'Excellent!',
//         'verified' => true
//     ]
// ]
```

### Decode Multiple Rows

```php
$toon = "reviews[2]{
  id, customer, rating;
  1, Alice, 5
  2, Bob, 4
}";

$array = Toon::decode($toon);
// Returns array with 2 review items
```

### Decode Nested Structures

```php
$toon = "product, reviews;
Laptop
reviews[2]{
  id, customer, rating;
  1, Alice, 5
  2, Bob, 4
}";

$array = Toon::decode($toon);
// Returns:
// [
//     'product' => 'Laptop',
//     'reviews' => [
//         ['id' => 1, 'customer' => 'Alice', 'rating' => 5],
//         ['id' => 2, 'customer' => 'Bob', 'rating' => 4]
//     ]
// ]
```

### Error Handling

The decode method throws `InvalidToonFormatException` for invalid TOON formats:

```php
use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
use DigitalCoreHub\Toon\Facades\Toon;

try {
    $array = Toon::decode($toon);
} catch (InvalidToonFormatException $e) {
    // Handle invalid TOON format
    echo "Error: " . $e->getMessage();
}
```

Common errors include:
- Missing semicolons in keys line (with line numbers)
- Mismatched key/value counts (with line numbers)
- Unclosed brackets `{` or `}` (with descriptive messages)
- Invalid array block formats

**Example Error Messages:**

```php
// Before: "Mismatched key/value count"
// After: "Key count (4) does not match value count (3) at line 5."

// Before: "Keys line must end with semicolon"
// After: "Missing semicolon in header block at line 2. Found: id, name, price"
```

### Using Dependency Injection

```php
use DigitalCoreHub\Toon\Toon;

class ProductController extends Controller
{
    public function __construct(
        private Toon $toon
    ) {}

    public function export()
    {
        $data = Product::all()->toArray();
        return $this->toon->encode($data);
    }
}
```

## CLI Commands

### Encode: JSON → TOON

Convert JSON files to TOON format using the Artisan command:

```bash
php artisan toon:encode input.json output.toon
```

**Options:**
- `--preview` or `-p`: Show colored preview of the output

**Example:**

```bash
# Convert a JSON file
php artisan toon:encode storage/data.json storage/data.toon

# With colored preview
php artisan toon:encode storage/data.json storage/data.toon --preview

# The command will:
# - Read JSON from input.json
# - Convert to TOON format
# - Save to output.toon
# - Show colored preview if --preview flag is used
```

### Decode: TOON → JSON

Convert TOON files to JSON format using the Artisan command:

```bash
php artisan toon:decode input.toon output.json
```

**Options:**
- `--preview` or `-p`: Show colored preview of the input

**Example:**

```bash
# Convert a TOON file
php artisan toon:decode storage/data.toon storage/data.json

# The command will:
# - Read TOON from input.toon
# - Convert to JSON format (pretty printed)
# - Save to output.json
# - Display meaningful errors on invalid input
```

**Error Handling:**

If the TOON file has invalid format, the command will display an error message:

```bash
$ php artisan toon:decode invalid.toon output.json
Invalid TOON format: Keys line must end with semicolon
```

## TOON Format Rules

The TOON format follows these rules:

1. **Objects**: Keys are listed on the first line, followed by values on the next line
   ```
   id, name, price;
   1, Product Name, 99.99
   ```

2. **Arrays**: Display with size indicator `arrayName[count]{...}`
   ```
   reviews[2]{
     id, customer, rating;
     1, John, 5
     2, Jane, 4
   }
   ```

3. **Minimal Syntax**: Removes unnecessary `{}`, `[]`, commas, and quotes where possible

4. **Order Preservation**: Maintains the original JSON key ordering

5. **Nested Support**: Fully supports nested arrays and objects

## Configuration

### Publishing the Configuration File

To customize the package settings, you need to publish the configuration file to your Laravel application:

```bash
php artisan vendor:publish --tag=toon-config
```

This command will create a `config/toon.php` file in your Laravel project's `config` directory.

### Configuration File Location

After publishing, the configuration file will be located at:
```
config/toon.php
```

### Configuration Options

The published configuration file contains the following options:

```php
return [
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
```

### Using Configuration Values

You can access configuration values in your code:

```php
use Illuminate\Support\Facades\Config;

$indentSize = config('toon.indent_size');
$preserveOrder = config('toon.preserve_order');
```

**Note:** The configuration file is optional. If you don't publish it, the package will use default values.

## Testing

Run the test suite:

```bash
composer test
# or
vendor/bin/phpunit
```

## Examples

### Example 1: Simple Object

**Input (JSON):**
```json
{
  "id": 1,
  "name": "Laptop",
  "price": 1299.99
}
```

**Output (TOON):**
```
id, name, price;
1, Laptop, 1299.99
```

### Example 2: Array of Objects

**Input (JSON):**
```json
[
  {
    "id": 1,
    "customer": "Alice",
    "rating": 5
  }
]
```

**Output (TOON):**
```
array[1]{
  id, customer, rating;
  1, Alice, 5
}
```

### Example 3: Complex Nested Structure

**Input (JSON):**
```json
{
  "product": "Smartphone",
  "reviews": [
    {"id": 1, "customer": "Bob", "rating": 5},
    {"id": 2, "customer": "Charlie", "rating": 4}
  ]
}
```

**Output (TOON):**
```
product, reviews;
Smartphone
reviews[2]{
  id, customer, rating;
  1, Bob, 5
  2, Charlie, 4
}
```

## Version

Current version: **v0.4.0**

This version includes:
- ✅ JSON → TOON encoding
- ✅ TOON → JSON decoding
- ✅ CLI commands (encode & decode) with colored preview
- ✅ Global helper functions (`toon_encode`, `toon_decode`)
- ✅ Fluent interface (`fromJson`, `fromArray`, `fromToon`)
- ✅ Blade directive `@toon()` for easy template integration
- ✅ Laravel Debugbar integration (auto-detected)
- ✅ Log::toon() macro for logging support
- ✅ Console styling with syntax highlighting
- ✅ Configurable formatting (indentation, separators, line breaks)
- ✅ Improved exception messages with line numbers
- ✅ Facade and DI support
- ✅ Comprehensive test coverage
- ✅ Error handling with custom exceptions

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

Developed by [DigitalCoreHub](https://github.com/digitalcorehub)

---

**Made with ❤️ for the Laravel community**
