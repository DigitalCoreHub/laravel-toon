<?php

namespace DigitalCoreHub\Toon;

use DigitalCoreHub\Toon\Console\ToonStyler;
use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
use DigitalCoreHub\Toon\Lazy\LazyEncoder;
use DigitalCoreHub\Toon\Streaming\StreamEncoder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Toon
{
    /**
     * Create a new builder instance for fluent interface.
     */
    public function fromJson(string $json): ToonBuilder
    {
        return (new ToonBuilder($this))->fromJson($json);
    }

    /**
     * Create a new builder instance for fluent interface.
     */
    public function fromArray(array $array): ToonBuilder
    {
        return (new ToonBuilder($this))->fromArray($array);
    }

    /**
     * Create a new builder instance for fluent interface.
     */
    public function fromToon(string $toon): ToonBuilder
    {
        return (new ToonBuilder($this))->fromToon($toon);
    }

    /**
     * Get configuration value.
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            return config("toon.{$key}", $default);
        }

        $configFile = __DIR__.'/../config/toon.php';
        if (file_exists($configFile)) {
            $config = require $configFile;

            return $config[$key] ?? $default;
        }

        return $default;
    }

    /**
     * Encode JSON data (array or JSON string) to TOON format.
     */
    public function encode(array|string $json): string
    {
        $startTime = microtime(true);

        // If input is a JSON string, decode it first
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON string provided');
            }
            $json = $decoded;
        }

        $result = $this->encodeValue($json, 0);
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Track in Debugbar if available
        $this->trackEncode($json, $result, $duration);

        return $result;
    }

    /**
     * Decode TOON format string to PHP array.
     *
     * @throws InvalidToonFormatException
     */
    public function decode(string $toon): array
    {
        $startTime = microtime(true);
        $toon = trim($toon);

        if (empty($toon)) {
            throw new InvalidToonFormatException('Empty TOON string provided');
        }

        $lines = $this->splitToonLines($toon);

        if (empty($lines)) {
            throw new InvalidToonFormatException('No valid TOON content found');
        }

        $result = $this->parseToon($lines, 0);
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Track in Debugbar if available
        $this->trackDecode($toon, $result['data'], $duration);

        return $result['data'];
    }

    /**
     * Track encode operation in Debugbar.
     */
    protected function trackEncode(mixed $data, string $toon, float $duration): void
    {
        if (! $this->hasDebugbar()) {
            return;
        }

        $collector = app('debugbar')->getCollector('toon');
        if ($collector) {
            $collector->addEncode($data, $toon, $duration);
        }
    }

    /**
     * Track decode operation in Debugbar.
     */
    protected function trackDecode(string $toon, array $result, float $duration): void
    {
        if (! $this->hasDebugbar()) {
            return;
        }

        $collector = app('debugbar')->getCollector('toon');
        if ($collector) {
            $collector->addDecode($toon, $result, $duration);
        }
    }

    /**
     * Check if Debugbar is available.
     */
    protected function hasDebugbar(): bool
    {
        return class_exists(\Barryvdh\Debugbar\LaravelDebugbar::class) &&
               app()->bound('debugbar') &&
               app('debugbar')->hasCollector('toon');
    }

    /**
     * Encode and format for console output with syntax highlighting.
     */
    public function console(array|string $data, ?OutputInterface $output = null): string
    {
        $toon = $this->encode($data);

        return ToonStyler::colorize($toon, $output);
    }

    /**
     * Encode JSON file to TOON format using streaming (memory-efficient for large files).
     */
    public function encodeStream(string $inputPath, string $outputPath): void
    {
        $encoder = new StreamEncoder($this);
        $encoder->encodeStream($inputPath, $outputPath);
    }

    /**
     * Create a lazy encoder that produces TOON output line by line.
     */
    public function lazy(array|string $data): LazyEncoder
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON string provided');
            }
            $data = $decoded;
        }

        return new LazyEncoder($this, $data);
    }

    /**
     * Decode TOON file using streaming (experimental foundation).
     */
    public function decodeStream(string $inputPath): \Generator
    {
        // Experimental: Just tokenize line by line, full parsing in v0.6
        $handle = fopen($inputPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Could not open file: {$inputPath}");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                yield trim($line);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Split TOON string into lines, preserving structure.
     */
    protected function splitToonLines(string $toon): array
    {
        $lines = preg_split('/\r?\n/', $toon);

        return array_filter(array_map('trim', $lines), fn ($line) => $line !== '');
    }

    /**
     * Parse TOON lines starting from given index.
     *
     * @return array ['data' => mixed, 'nextIndex' => int]
     *
     * @throws InvalidToonFormatException
     */
    protected function parseToon(array $lines, int $startIndex): array
    {
        if ($startIndex >= count($lines)) {
            throw new InvalidToonFormatException('Unexpected end of TOON content');
        }

        $line = $lines[$startIndex];
        $currentIndex = $startIndex;

        // Check if it's an array block: name[count]{
        if (preg_match('/^(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
            return $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int) $matches[2]);
        }

        // Check if it's a simple object: key1, key2, key3;
        if (str_ends_with($line, ';')) {
            return $this->parseObject($lines, $currentIndex);
        }

        // If line contains comma but no semicolon, it might be malformed keys line
        if (str_contains($line, ',')) {
            $lineNumber = $startIndex + 1;
            throw new InvalidToonFormatException("Missing semicolon in header block at line {$lineNumber}. Found: {$line}");
        }

        // Try to parse as a single value
        $value = $this->parseValue($line);

        return ['data' => $value, 'nextIndex' => $currentIndex + 1];
    }

    /**
     * Parse an array block: name[count]{ ... }
     *
     * @throws InvalidToonFormatException
     */
    protected function parseArrayBlock(array $lines, int $startIndex, string $arrayName, int $expectedCount): array
    {
        $currentIndex = $startIndex + 1;
        $result = [];

        // Check if next line is keys line
        if ($currentIndex >= count($lines)) {
            throw new InvalidToonFormatException("Missing content in array block '{$arrayName}'");
        }

        $keysLine = $lines[$currentIndex];

        if (! str_ends_with($keysLine, ';')) {
            // No keys line, parse as array of primitives or nested structures
            $bracketCount = 1;
            $items = [];

            while ($currentIndex < count($lines) && $bracketCount > 0) {
                $line = $lines[$currentIndex];

                if (str_contains($line, '{')) {
                    $bracketCount += substr_count($line, '{');
                }
                if (str_contains($line, '}')) {
                    $bracketCount -= substr_count($line, '}');
                }

                if ($bracketCount === 0) {
                    // Closing brace
                    break;
                }

                // Parse item
                if (preg_match('/^(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
                    // Nested array block
                    $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int) $matches[2]);
                    $items[] = $nested['data'];
                    $currentIndex = $nested['nextIndex'];
                } elseif (trim($line) !== '{' && trim($line) !== '}') {
                    // Primitive or object
                    $parsed = $this->parseToon($lines, $currentIndex);
                    $items[] = $parsed['data'];
                    $currentIndex = $parsed['nextIndex'];
                } else {
                    $currentIndex++;
                }
            }

            if ($bracketCount !== 0) {
                throw new InvalidToonFormatException("Unclosed brackets in array block '{$arrayName}'. Expected closing '}' but reached end of content.");
            }

            return ['data' => $items, 'nextIndex' => $currentIndex + 1];
        }

        // Parse keys line
        $lineNumber = $currentIndex + 1;
        $keys = $this->parseKeysLine($keysLine, $lineNumber);
        $currentIndex++;

        // Parse value rows
        $bracketCount = 1;

        while ($currentIndex < count($lines) && $bracketCount > 0) {
            $line = $lines[$currentIndex];

            if (str_contains($line, '{')) {
                $bracketCount += substr_count($line, '{');
            }
            if (str_contains($line, '}')) {
                $bracketCount -= substr_count($line, '}');
            }

            if ($bracketCount === 0) {
                // Closing brace
                break;
            }

            // Check if it's a nested array block
            if (preg_match('/^(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
                $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int) $matches[2]);
                // This is a value in the parent object, we need to handle it differently
                // For now, skip nested blocks in value rows
                $currentIndex = $nested['nextIndex'];

                continue;
            }

            // Parse value row
            $values = $this->parseValueRow($line, count($keys));

            if (count($values) !== count($keys)) {
                $lineNumber = $currentIndex + 1;
                throw new InvalidToonFormatException(
                    'Key count ('.count($keys).') does not match value count ('.count($values).") at line {$lineNumber} in array block '{$arrayName}'."
                );
            }

            $item = [];
            foreach ($keys as $index => $key) {
                $item[$key] = $values[$index];
            }

            $result[] = $item;
            $currentIndex++;
        }

        if ($bracketCount !== 0) {
            throw new InvalidToonFormatException("Unclosed array block '{$arrayName}'");
        }

        if (count($result) !== $expectedCount) {
            throw new InvalidToonFormatException(
                "Array count mismatch in '{$arrayName}'. ".
                "Expected {$expectedCount} items, got ".count($result)
            );
        }

        return ['data' => $result, 'nextIndex' => $currentIndex + 1];
    }

    /**
     * Parse an object: key1, key2, key3; followed by values.
     *
     * @throws InvalidToonFormatException
     */
    protected function parseObject(array $lines, int $startIndex): array
    {
        $keysLine = $lines[$startIndex];
        $lineNumber = $startIndex + 1;
        $keys = $this->parseKeysLine($keysLine, $lineNumber);

        $currentIndex = $startIndex + 1;
        $result = [];
        $keyIndex = 0;

        while ($currentIndex < count($lines) && $keyIndex < count($keys)) {
            $line = $lines[$currentIndex];

            // Check if it's a nested array block
            if (preg_match('/^(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
                // This should match the current key
                if ($matches[1] === $keys[$keyIndex]) {
                    $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int) $matches[2]);
                    $result[$keys[$keyIndex]] = $nested['data'];
                    $currentIndex = $nested['nextIndex'];
                    $keyIndex++;

                    continue;
                }
            }

            // Check if it's a simple value or value row
            if ($keyIndex === 0 && count($keys) === 1) {
                // Single key, single value
                $value = $this->parseValue($line);
                $result[$keys[$keyIndex]] = $value;
                $keyIndex++;
                $currentIndex++;
            } elseif ($keyIndex === 0) {
                // Multiple keys, try to parse as value row
                $values = $this->parseValueRow($line, count($keys));

                if (count($values) === count($keys)) {
                    // It's a value row
                    foreach ($keys as $index => $key) {
                        $result[$key] = $values[$index];
                    }
                    $keyIndex = count($keys);
                    $currentIndex++;
                } else {
                    // Single value for first key
                    $result[$keys[$keyIndex]] = $this->parseValue($line);
                    $keyIndex++;
                    $currentIndex++;
                }
            } else {
                // More values for remaining keys
                if ($keyIndex < count($keys)) {
                    $result[$keys[$keyIndex]] = $this->parseValue($line);
                    $keyIndex++;
                    $currentIndex++;
                } else {
                    break;
                }
            }
        }

        if ($keyIndex < count($keys)) {
            $lineNumber = $currentIndex > 0 ? $currentIndex + 1 : $startIndex + 2;
            throw new InvalidToonFormatException(
                'Key count ('.count($keys).") does not match value count ({$keyIndex}) at line {$lineNumber}."
            );
        }

        return ['data' => $result, 'nextIndex' => $currentIndex];
    }

    /**
     * Parse keys line: "key1, key2, key3;"
     *
     * @throws InvalidToonFormatException
     */
    protected function parseKeysLine(string $line, ?int $lineNumber = null): array
    {
        if (! str_ends_with($line, ';')) {
            $lineInfo = $lineNumber ? " at line {$lineNumber}" : '';
            throw new InvalidToonFormatException("Missing semicolon in header block{$lineInfo}. Found: {$line}");
        }

        $line = rtrim($line, ';');
        $keys = array_map('trim', explode(',', $line));
        $keys = array_filter($keys, fn ($key) => $key !== '');

        if (empty($keys)) {
            throw new InvalidToonFormatException('Empty keys line');
        }

        return array_values($keys);
    }

    /**
     * Parse value row: "value1, value2, value3"
     */
    protected function parseValueRow(string $line, int $expectedCount): array
    {
        $values = [];
        $current = '';
        $inQuotes = false;
        $escapeNext = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($escapeNext) {
                $current .= $char;
                $escapeNext = false;

                continue;
            }

            if ($char === '\\') {
                $escapeNext = true;

                continue;
            }

            if ($char === '"') {
                $inQuotes = ! $inQuotes;

                continue;
            }

            if ($char === ',' && ! $inQuotes) {
                $values[] = $this->parseValue(trim($current));
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $values[] = $this->parseValue(trim($current));
        }

        return $values;
    }

    /**
     * Parse a single value (string, number, boolean, null).
     */
    protected function parseValue(string $value): mixed
    {
        $value = trim($value);

        // Null
        if ($value === 'null') {
            return null;
        }

        // Boolean
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // String (remove quotes if present)
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return stripslashes($matches[1]);
        }

        return $value;
    }

    /**
     * Encode a value to TOON format.
     *
     * @param  string|null  $arrayKeyName  Optional key name for arrays (when array is a value of an object key)
     */
    protected function encodeValue(mixed $value, int $indentLevel = 0, ?string $arrayKeyName = null): string
    {
        $indent = str_repeat('  ', $indentLevel);

        if (is_array($value)) {
            // Check if it's an associative array (object) or indexed array
            if ($this->isAssociativeArray($value)) {
                return $this->encodeObject($value, $indentLevel);
            } else {
                return $this->encodeArray($value, $indentLevel, $arrayKeyName);
            }
        }

        // Primitive values
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        // String value - escape if needed
        return $this->escapeString($value);
    }

    /**
     * Check if array is associative.
     */
    protected function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Encode an associative array (object) to TOON format.
     */
    protected function encodeObject(array $object, int $indentLevel = 0): string
    {
        $compact = $this->config('compact', false);
        $indentSize = $compact ? 0 : $this->config('indentation', 4);
        $keySeparator = $compact ? ',' : $this->config('key_separator', ', ');
        $lineBreak = $this->config('line_break', PHP_EOL);
        $indent = $compact ? '' : str_repeat(' ', $indentLevel * $indentSize);
        $lines = [];

        // Get all keys in order
        $keys = array_keys($object);
        $keysLine = implode($keySeparator, $keys).';';
        $lines[] = $indent.$keysLine;

        // Encode values - check if any value is an array that needs special handling
        $hasArrayValues = false;
        foreach ($keys as $key) {
            if (is_array($object[$key]) && ! $this->isAssociativeArray($object[$key])) {
                $hasArrayValues = true;
                break;
            }
        }

        if ($hasArrayValues) {
            // If we have array values, output each value in key order
            // Simple values can be on one line, arrays get their own blocks
            $valueLines = [];
            $hasSimpleValues = false;

            foreach ($keys as $key) {
                $value = $object[$key];
                if (is_array($value) && ! $this->isAssociativeArray($value)) {
                    // Array value - encode with key name as separate block
                    $arrayLines = $this->encodeArray($value, $indentLevel, $key);
                    $valueLines[] = $arrayLines;
                } else {
                    // Simple value - collect for single line output
                    $valueLines[] = $this->encodeValue($value, $indentLevel);
                    $hasSimpleValues = true;
                }
            }

            // If we have simple values, try to put them on one line
            // But we need to maintain order, so if arrays are interspersed, we can't
            // For now, output each value on its own line to preserve order
            foreach ($valueLines as $valueLine) {
                if (str_contains($valueLine, "\n")) {
                    // It's an array block (multi-line)
                    $lines[] = $valueLine;
                } else {
                    // It's a simple value
                    $lines[] = $indent.$valueLine;
                }
            }
        } else {
            // All simple values - encode on one line
            $values = [];
            foreach ($keys as $key) {
                $values[] = $this->encodeValue($object[$key], $indentLevel);
            }
            $valuesLine = implode($keySeparator, $values);
            $lines[] = $indent.$valuesLine;
        }

        return implode($lineBreak, $lines);
    }

    /**
     * Encode an indexed array to TOON format.
     *
     * @param  string|null  $arrayKeyName  Optional key name for arrays (when array is a value of an object key)
     */
    protected function encodeArray(array $array, int $indentLevel = 0, ?string $arrayKeyName = null): string
    {
        $compact = $this->config('compact', false);
        $indentSize = $compact ? 0 : $this->config('indentation', 4);
        $keySeparator = $compact ? ',' : $this->config('key_separator', ', ');
        $lineBreak = $this->config('line_break', PHP_EOL);
        $indent = $compact ? '' : str_repeat(' ', $indentLevel * $indentSize);
        $count = count($array);
        $lines = [];

        // Array header with size indicator
        $arrayName = $arrayKeyName ?? 'array';
        $lines[] = $indent.$arrayName.'['.$count.']{';

        // If array contains objects, extract keys from first object and show them on first line
        if (! empty($array) && is_array($array[0]) && $this->isAssociativeArray($array[0])) {
            // Get keys from first object (assuming all objects have same structure)
            $firstObjectKeys = array_keys($array[0]);
            $keysLine = implode($keySeparator, $firstObjectKeys).';';
            $innerIndent = $compact ? '' : str_repeat(' ', $indentSize);
            $lines[] = $indent.$innerIndent.$keysLine;

            // Encode each object's values on separate lines
            foreach ($array as $item) {
                if (is_array($item) && $this->isAssociativeArray($item)) {
                    $values = [];
                    foreach ($firstObjectKeys as $key) {
                        $value = $item[$key] ?? null;
                        $values[] = $this->encodeValue($value, $indentLevel);
                    }
                    $valuesLine = implode($keySeparator, $values);
                    $innerIndent = $compact ? '' : str_repeat(' ', $indentSize);
                    $lines[] = $indent.$innerIndent.$valuesLine;
                }
            }
        } else {
            // Process each item (primitives or nested structures)
            foreach ($array as $item) {
                if (is_array($item)) {
                    // Nested array/object
                    if ($this->isAssociativeArray($item)) {
                        // It's an object
                        $itemLines = $this->encodeObject($item, $indentLevel + 1);
                        $lines[] = $itemLines;
                    } else {
                        // It's a nested array
                        $itemLines = $this->encodeArray($item, $indentLevel + 1);
                        $lines[] = $itemLines;
                    }
                } else {
                    // Primitive value
                    $innerIndent = $compact ? '' : str_repeat(' ', $indentSize);
                    $lines[] = $indent.$innerIndent.$this->encodeValue($item, $indentLevel);
                }
            }
        }

        $lines[] = $indent.'}';

        return implode($lineBreak, $lines);
    }

    /**
     * Escape string value if needed.
     */
    protected function escapeString(string $value): string
    {
        // Per requirements, we remove unnecessary quotes
        // Only add quotes if absolutely necessary (contains special chars that would break parsing)
        // Commas, semicolons, braces, and brackets require quotes
        // Spaces alone don't require quotes in TOON format
        if (preg_match('/[,;{}\[\]]/', $value)) {
            return '"'.addslashes($value).'"';
        }

        return $value;
    }

    /**
     * Store TOON data to Laravel Storage.
     *
     * @param  string  $path  The file path (relative to disk root or directory)
     * @param  array|string|object  $data  The data to encode and store
     * @param  string|null  $disk  The storage disk name (null uses default_disk from config)
     * @return string The full path where the file was saved
     */
    public function store(string $path, array|string|object $data, ?string $disk = null): string
    {
        // Get disk from config if not provided
        $disk = $disk ?? $this->config('storage.default_disk', 'local');
        $defaultDirectory = $this->config('storage.default_directory', 'toon');

        // Ensure path has .toon extension if not present
        if (! str_ends_with($path, '.toon')) {
            $path .= '.toon';
        }

        // Prepend default directory if path doesn't start with /
        if (! str_starts_with($path, '/') && $defaultDirectory) {
            $path = rtrim($defaultDirectory, '/').'/'.ltrim($path, '/');
        }

        // Convert object to array if needed
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Encode data to TOON
        $toonContent = $this->encode($data);

        // Ensure directory exists
        $directory = dirname($path);
        if ($directory !== '.' && $directory !== '/') {
            Storage::disk($disk)->makeDirectory($directory);
        }

        // Store the file
        Storage::disk($disk)->put($path, $toonContent);

        return $path;
    }

    /**
     * Download TOON data as a file response.
     *
     * @param  string  $filename  The filename for the download
     * @param  array|string|object  $data  The data to encode and download
     */
    public function download(string $filename, array|string|object $data): StreamedResponse
    {
        // Ensure filename has .toon extension
        if (! str_ends_with($filename, '.toon')) {
            $filename .= '.toon';
        }

        // Convert object to array if needed
        if (is_object($data)) {
            $data = (array) $data;
        }

        // Encode data to TOON
        $toonContent = $this->encode($data);

        return response()->streamDownload(function () use ($toonContent) {
            echo $toonContent;
        }, $filename, [
            'Content-Type' => 'text/toon',
        ]);
    }
}
