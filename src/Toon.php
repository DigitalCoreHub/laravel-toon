<?php

namespace DigitalCoreHub\Toon;

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

