# Laravel Toon

A lightweight Laravel package that converts standard JSON into **TOON** format - a human-readable, ultra-minimal, line-based data format.

[![Latest Version](https://img.shields.io/badge/version-0.2.0-blue.svg)](https://github.com/digitalcorehub/laravel-toon)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)

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
- Missing semicolons in keys line
- Mismatched key/value counts
- Unclosed brackets `{` or `}`
- Invalid array block formats

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

**Example:**

```bash
# Convert a JSON file
php artisan toon:encode storage/data.json storage/data.toon

# The command will:
# - Read JSON from input.json
# - Convert to TOON format
# - Save to output.toon
```

### Decode: TOON → JSON

Convert TOON files to JSON format using the Artisan command:

```bash
php artisan toon:decode input.toon output.json
```

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
    | Indent Size
    |--------------------------------------------------------------------------
    |
    | The number of spaces used for indentation in the TOON output.
    |
    */
    'indent_size' => 2,

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

Current version: **v0.2.0**

This version includes:
- ✅ JSON → TOON encoding
- ✅ TOON → JSON decoding
- ✅ CLI commands (encode & decode)
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
