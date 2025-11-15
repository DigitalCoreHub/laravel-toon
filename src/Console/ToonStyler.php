<?php

namespace DigitalCoreHub\Toon\Console;

use Symfony\Component\Console\Output\OutputInterface;

class ToonStyler
{
    /**
     * Colorize TOON output for console.
     */
    public static function colorize(string $toon, ?OutputInterface $output = null): string
    {
        if (! $output || ! $output->isDecorated()) {
            return $toon;
        }

        $lines = explode("\n", $toon);
        $colorized = [];

        foreach ($lines as $line) {
            $colorized[] = self::colorizeLine($line, $output);
        }

        return implode("\n", $colorized);
    }

    /**
     * Colorize a single line.
     */
    protected static function colorizeLine(string $line, OutputInterface $output): string
    {
        // Keys line (ends with semicolon)
        if (str_ends_with(trim($line), ';')) {
            return self::colorizeKeys($line, $output);
        }

        // Array block header
        if (preg_match('/^(\s*)(\w+)\[(\d+)\]\s*\{$/', $line, $matches)) {
            $indent = $matches[1];
            $name = $matches[2];
            $count = $matches[3];

            return $indent.
                '<fg=yellow>'.$name.'</>'.
                '<fg=blue>['.$count.']</>'.
                '<fg=cyan>{</>';
        }

        // Closing brace
        if (preg_match('/^(\s*)\}$/', $line, $matches)) {
            return $matches[1].'<fg=cyan>}</>';
        }

        // Value line (contains commas, not keys)
        if (str_contains($line, ',') && ! str_ends_with(trim($line), ';')) {
            return self::colorizeValues($line, $output);
        }

        return $line;
    }

    /**
     * Colorize keys line.
     */
    protected static function colorizeKeys(string $line, OutputInterface $output): string
    {
        $parts = explode(';', $line, 2);
        $keysPart = $parts[0];
        $semicolon = $parts[1] ?? '';

        $keys = array_map('trim', explode(',', $keysPart));
        $coloredKeys = array_map(fn ($key) => '<fg=yellow>'.trim($key).'</>', $keys);

        return implode('<fg=gray>,</> ', $coloredKeys).'<fg=cyan>;</>'.$semicolon;
    }

    /**
     * Colorize values line.
     */
    protected static function colorizeValues(string $line, OutputInterface $output): string
    {
        $values = array_map('trim', explode(',', $line));
        $coloredValues = [];

        foreach ($values as $value) {
            $coloredValues[] = self::colorizeValue($value, $output);
        }

        return implode('<fg=gray>,</> ', $coloredValues);
    }

    /**
     * Colorize a single value.
     */
    protected static function colorizeValue(string $value, OutputInterface $output): string
    {
        $trimmed = trim($value);

        // Boolean
        if ($trimmed === 'true') {
            return '<fg=magenta>true</>';
        }
        if ($trimmed === 'false') {
            return '<fg=magenta>false</>';
        }

        // Null
        if ($trimmed === 'null') {
            return '<fg=gray>null</>';
        }

        // Number
        if (is_numeric($trimmed)) {
            return '<fg=blue>'.$trimmed.'</>';
        }

        // String (remove quotes if present)
        if (preg_match('/^"(.*)"$/', $trimmed, $matches)) {
            return '<fg=green>"'.$matches[1].'"</>';
        }

        // String without quotes
        return '<fg=green>'.$trimmed.'</>';
    }
}
