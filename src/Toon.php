<?php

namespace DigitalCoreHub\Toon;

use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;

class Toon
{
    /**
     * Encode JSON data (array or JSON string) to TOON format.
     *
     * @param array|string $json
     * @return string
     */
    public function encode(array|string $json): string
    {
        // If input is a JSON string, decode it first
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON string provided');
            }
            $json = $decoded;
        }

        return $this->encodeValue($json, 0);
    }

    /**
     * Decode TOON format string to PHP array.
     *
     * @param string $toon
     * @return array
     * @throws InvalidToonFormatException
     */
    public function decode(string $toon): array
    {
        $toon = trim($toon);

        if (empty($toon)) {
            throw new InvalidToonFormatException('Empty TOON string provided');
        }

        $lines = $this->splitToonLines($toon);

        if (empty($lines)) {
            throw new InvalidToonFormatException('No valid TOON content found');
        }

        $result = $this->parseToon($lines, 0);

        return $result['data'];
    }

    /**
     * Split TOON string into lines, preserving structure.
     *
     * @param string $toon
     * @return array
     */
    protected function splitToonLines(string $toon): array
    {
        $lines = preg_split('/\r?\n/', $toon);
        return array_filter(array_map('trim', $lines), fn($line) => $line !== '');
    }

    /**
     * Parse TOON lines starting from given index.
     *
     * @param array $lines
     * @param int $startIndex
     * @return array ['data' => mixed, 'nextIndex' => int]
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
            return $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int)$matches[2]);
        }

        // Check if it's a simple object: key1, key2, key3;
        if (str_ends_with($line, ';')) {
            return $this->parseObject($lines, $currentIndex);
        }

        // If line contains comma but no semicolon, it might be malformed keys line
        if (str_contains($line, ',') && !str_ends_with($line, ';')) {
            throw new InvalidToonFormatException("Keys line must end with semicolon: {$line}");
        }

        // Try to parse as a single value
        $value = $this->parseValue($line);
        return ['data' => $value, 'nextIndex' => $currentIndex + 1];
    }

    /**
     * Parse an array block: name[count]{ ... }
     *
     * @param array $lines
     * @param int $startIndex
     * @param string $arrayName
     * @param int $expectedCount
     * @return array
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

        if (!str_ends_with($keysLine, ';')) {
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
                    $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int)$matches[2]);
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
                throw new InvalidToonFormatException("Unclosed array block '{$arrayName}'");
            }

            return ['data' => $items, 'nextIndex' => $currentIndex + 1];
        }

        // Parse keys line
        $keys = $this->parseKeysLine($keysLine);
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
                $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int)$matches[2]);
                // This is a value in the parent object, we need to handle it differently
                // For now, skip nested blocks in value rows
                $currentIndex = $nested['nextIndex'];
                continue;
            }

            // Parse value row
            $values = $this->parseValueRow($line, count($keys));

            if (count($values) !== count($keys)) {
                throw new InvalidToonFormatException(
                    "Mismatched key/value count in array block '{$arrayName}'. " .
                    "Expected " . count($keys) . " values, got " . count($values)
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
                "Array count mismatch in '{$arrayName}'. " .
                "Expected {$expectedCount} items, got " . count($result)
            );
        }

        return ['data' => $result, 'nextIndex' => $currentIndex + 1];
    }

    /**
     * Parse an object: key1, key2, key3; followed by values.
     *
     * @param array $lines
     * @param int $startIndex
     * @return array
     * @throws InvalidToonFormatException
     */
    protected function parseObject(array $lines, int $startIndex): array
    {
        $keysLine = $lines[$startIndex];
        $keys = $this->parseKeysLine($keysLine);

        $currentIndex = $startIndex + 1;
        $result = [];
        $keyIndex = 0;

        while ($currentIndex < count($lines) && $keyIndex < count($keys)) {
            $line = $lines[$currentIndex];

            // Check if it's a nested array block
            if (preg_match('/^(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
                // This should match the current key
                if ($matches[1] === $keys[$keyIndex]) {
                    $nested = $this->parseArrayBlock($lines, $currentIndex, $matches[1], (int)$matches[2]);
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
            throw new InvalidToonFormatException(
                "Missing values for object keys. " .
                "Expected " . count($keys) . " values, got " . $keyIndex
            );
        }

        return ['data' => $result, 'nextIndex' => $currentIndex];
    }

    /**
     * Parse keys line: "key1, key2, key3;"
     *
     * @param string $line
     * @return array
     * @throws InvalidToonFormatException
     */
    protected function parseKeysLine(string $line): array
    {
        if (!str_ends_with($line, ';')) {
            throw new InvalidToonFormatException("Keys line must end with semicolon: {$line}");
        }

        $line = rtrim($line, ';');
        $keys = array_map('trim', explode(',', $line));
        $keys = array_filter($keys, fn($key) => $key !== '');

        if (empty($keys)) {
            throw new InvalidToonFormatException('Empty keys line');
        }

        return array_values($keys);
    }

    /**
     * Parse value row: "value1, value2, value3"
     *
     * @param string $line
     * @param int $expectedCount
     * @return array
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
                $inQuotes = !$inQuotes;
                continue;
            }

            if ($char === ',' && !$inQuotes) {
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
     *
     * @param string $value
     * @return mixed
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
            return str_contains($value, '.') ? (float)$value : (int)$value;
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
     * @param mixed $value
     * @param int $indentLevel
     * @param string|null $arrayKeyName Optional key name for arrays (when array is a value of an object key)
     * @return string
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
     *
     * @param array $array
     * @return bool
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
     *
     * @param array $object
     * @param int $indentLevel
     * @return string
     */
    protected function encodeObject(array $object, int $indentLevel = 0): string
    {
        $indent = str_repeat('  ', $indentLevel);
        $lines = [];

        // Get all keys in order
        $keys = array_keys($object);
        $keysLine = implode(', ', $keys) . ';';
        $lines[] = $indent . $keysLine;

        // Encode values - check if any value is an array that needs special handling
        $hasArrayValues = false;
        foreach ($keys as $key) {
            if (is_array($object[$key]) && !$this->isAssociativeArray($object[$key])) {
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
                if (is_array($value) && !$this->isAssociativeArray($value)) {
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
                    $lines[] = $indent . $valueLine;
                }
            }
        } else {
            // All simple values - encode on one line
            $values = [];
            foreach ($keys as $key) {
                $values[] = $this->encodeValue($object[$key], $indentLevel);
            }
            $valuesLine = implode(', ', $values);
            $lines[] = $indent . $valuesLine;
        }

        return implode("\n", $lines);
    }

    /**
     * Encode an indexed array to TOON format.
     *
     * @param array $array
     * @param int $indentLevel
     * @param string|null $arrayKeyName Optional key name for arrays (when array is a value of an object key)
     * @return string
     */
    protected function encodeArray(array $array, int $indentLevel = 0, ?string $arrayKeyName = null): string
    {
        $indent = str_repeat('  ', $indentLevel);
        $count = count($array);
        $lines = [];

        // Array header with size indicator
        $arrayName = $arrayKeyName ?? 'array';
        $lines[] = $indent . $arrayName . '[' . $count . ']{';

        // If array contains objects, extract keys from first object and show them on first line
        if (!empty($array) && is_array($array[0]) && $this->isAssociativeArray($array[0])) {
            // Get keys from first object (assuming all objects have same structure)
            $firstObjectKeys = array_keys($array[0]);
            $keysLine = implode(', ', $firstObjectKeys) . ';';
            $lines[] = $indent . '  ' . $keysLine;

            // Encode each object's values on separate lines
            foreach ($array as $item) {
                if (is_array($item) && $this->isAssociativeArray($item)) {
                    $values = [];
                    foreach ($firstObjectKeys as $key) {
                        $value = $item[$key] ?? null;
                        $values[] = $this->encodeValue($value, $indentLevel);
                    }
                    $valuesLine = implode(', ', $values);
                    $lines[] = $indent . '  ' . $valuesLine;
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
                    $lines[] = $indent . '  ' . $this->encodeValue($item, $indentLevel);
                }
            }
        }

        $lines[] = $indent . '}';

        return implode("\n", $lines);
    }

    /**
     * Escape string value if needed.
     *
     * @param string $value
     * @return string
     */
    protected function escapeString(string $value): string
    {
        // Per requirements, we remove unnecessary quotes
        // Only add quotes if absolutely necessary (contains special chars that would break parsing)
        // Commas, semicolons, braces, and brackets require quotes
        // Spaces alone don't require quotes in TOON format
        if (preg_match('/[,;{}\[\]]/', $value)) {
            return '"' . addslashes($value) . '"';
        }

        return $value;
    }
}

