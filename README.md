# Laravel Toon

A lightweight Laravel package that converts standard JSON into **TOON** format - a human-readable, ultra-minimal, line-based data format.

[![Latest Version](https://img.shields.io/badge/version-0.6.0-blue.svg)](https://github.com/digitalcorehub/laravel-toon)
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

### Streaming Encoder

For large JSON files, use the streaming encoder to avoid loading everything into memory:

```php
use DigitalCoreHub\Toon\Facades\Toon;

// Encode large JSON file to TOON format
Toon::encodeStream('storage/large.json', 'storage/large.toon');

// Support for Laravel Storage disks
Toon::encodeStream('local:data.json', 'local:data.toon');
```

The streaming encoder:
- Reads JSON file efficiently
- Writes TOON output progressively
- Reduces memory usage for large files
- Supports both local paths and Laravel Storage disks

### Lazy Encoder

Get TOON output line by line using a generator:

```php
use DigitalCoreHub\Toon\Facades\Toon;

$data = ['id' => 1, 'name' => 'Test', 'items' => [1, 2, 3]];

// Generate lines one by one
foreach (Toon::lazy($data) as $line) {
    echo $line . "\n";
}

// Or write directly to file
Toon::lazy($data)->toFile('output.toon');

// Or get as array
$lines = Toon::lazy($data)->toArray();
```

Lazy encoder is perfect for:
- Large datasets
- Real-time output
- Memory-constrained environments
- Terminal/console output

### Compact Mode

Enable compact mode for smaller, faster output:

```php
// In config/toon.php
'compact' => true,
```

Compact mode:
- Removes extra whitespace
- Uses minimal separators (no spaces after commas)
- Produces smaller files
- Faster encoding/decoding

**Example:**

```php
config(['toon.compact' => true]);

$data = ['id' => 1, 'name' => 'Test'];
$toon = Toon::encode($data);
// Output: id,name;1,Test (no spaces)
```

### Benchmarking

Measure performance with the benchmark command:

```bash
php artisan toon:bench tests/bench/large.json
```

The benchmark shows:
- Encode speed (milliseconds)
- Decode speed (milliseconds)
- Memory usage (peak and used)
- Total rows processed
- Total keys processed
- File size comparison

**Example Output:**

```
ENCODE: 87.23 ms
DECODE: 114.56 ms
Memory Peak: 4.3 MB
Memory Used: 2.1 MB
Rows: 220
Keys: 14
TOON Size: 15,432 bytes
JSON Size: 18,765 bytes
```

### Performance Best Practices

1. **Use streaming for large files:**
   ```php
   Toon::encodeStream($input, $output); // Memory-efficient
   ```

2. **Enable compact mode in production:**
   ```php
   config(['toon.compact' => true]); // Smaller, faster
   ```

3. **Use lazy encoder for real-time output:**
   ```php
   foreach (Toon::lazy($data) as $line) {
       // Process line by line
   }
   ```

4. **Monitor performance:**
   ```bash
   php artisan toon:bench your-file.json
   ```

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

### Benchmark: Performance Testing

Measure TOON encode/decode performance:

```bash
php artisan toon:bench [file]
```

**Options:**
- `file`: Optional path to JSON file. If not provided, uses first file from `tests/bench/` directory.

**Example:**

```bash
# Benchmark a specific file
php artisan toon:bench storage/large.json

# Use default benchmark file
php artisan toon:bench
```

The benchmark command displays:
- Encode speed (milliseconds)
- Decode speed (milliseconds)
- Memory usage (peak and used)
- Total rows and keys processed
- File size comparison (TOON vs JSON)

### Store: Save TOON to Laravel Storage

Save TOON files using Laravel Storage:

```bash
php artisan toon:store input.json output.toon --disk=public
```

**Options:**
- `--disk`: The storage disk to use (default: from config `toon.storage.default_disk`)

**Example:**
```bash
# Store to default disk (local)
php artisan toon:store storage/data.json users.toon

# Store to public disk
php artisan toon:store storage/data.json users.toon --disk=public

# The command will:
# - Read JSON from input.json
# - Convert to TOON format
# - Store via Laravel Storage
# - Print saved file path
```

## File Storage & Download

### Storing TOON Files

Save TOON data to Laravel Storage:

```php
use DigitalCoreHub\Toon\Facades\Toon;

$data = ['id' => 1, 'name' => 'Test'];

// Store to default disk (from config)
$path = Toon::store('my-file', $data);
// Returns: "toon/my-file.toon"

// Store to specific disk
$path = Toon::store('my-file', $data, 'public');
// Returns: "toon/my-file.toon"

// Store with nested path (directory created automatically)
$path = Toon::store('exports/users', $data, 'local');
// Returns: "toon/exports/users.toon"
```

**Features:**
- Automatically adds `.toon` extension if missing
- Creates directories automatically
- Uses default directory from config (`toon.storage.default_directory`)
- Returns full saved file path

**Configuration:**
```php
// config/toon.php
'storage' => [
    'default_disk' => 'local',
    'default_directory' => 'toon',
],
```

### Downloading TOON Files

Download TOON data as a file response:

```php
use DigitalCoreHub\Toon\Facades\Toon;

// In a controller
Route::get('/export/users', function () {
    $users = User::all()->toArray();
    return Toon::download('users', $users);
});

// With custom filename
return Toon::download('export-2024-01-01', $data);
```

**Response Headers:**
- `Content-Type: text/toon`
- `Content-Disposition: attachment; filename="users.toon"`

The download method:
- Automatically adds `.toon` extension if missing
- Streams response efficiently
- Sets proper headers for file download

### API Response Macro

Return TOON format in API responses:

```php
use Illuminate\Support\Facades\Response;

// In a controller
public function index()
{
    $data = Product::all()->toArray();
    return response()->toon($data);
}
```

**Response Headers:**
- `Content-Type: text/toon`

**Example Route:**
```php
// routes/api.php
Route::get('/products', function () {
    return response()->toon(Product::all()->toArray());
});
```

The `response()->toon()` macro:
- Encodes data to TOON format
- Sets `Content-Type: text/toon` header
- Returns standard Laravel response

**File Structure:**
After storing files, they will be located at:
```
storage/app/toon/*.toon          (default disk: local)
storage/app/public/toon/*.toon     (disk: public)
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

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing TOON files using Laravel Storage.
    |
    */
    'storage' => [
        'default_disk' => 'local',
        'default_directory' => 'toon',
    ],
];
```

### Using Configuration Values

You can access configuration values in your code:

```php
use Illuminate\Support\Facades\Config;

$indentSize = config('toon.indentation');
$preserveOrder = config('toon.preserve_order');
$compact = config('toon.compact');
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

Current version: **v0.6.0**

This version includes:
- ✅ JSON → TOON encoding
- ✅ TOON → JSON decoding
- ✅ **File Storage** - Save TOON files using Laravel Storage (`store`)
- ✅ **Download Support** - Download TOON files as HTTP responses (`download`)
- ✅ **API Response Macro** - `response()->toon()` for API endpoints
- ✅ **Store Command** - `php artisan toon:store` for CLI file storage
- ✅ Streaming encoder for large files (`encodeStream`)
- ✅ Lazy encoder for line-by-line output (`lazy`)
- ✅ Benchmark command (`php artisan toon:bench`)
- ✅ Compact mode for smaller, faster output
- ✅ Experimental streaming decode (`decodeStream`)
- ✅ CLI commands (encode, decode, store) with colored preview
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
