<?php

namespace DigitalCoreHub\Toon\Streaming;

use DigitalCoreHub\Toon\Toon;
use Illuminate\Support\Facades\Storage;

class StreamEncoder
{
    protected Toon $toon;

    protected int $bufferSize = 8192; // 8KB chunks

    public function __construct(Toon $toon)
    {
        $this->toon = $toon;
    }

    /**
     * Encode JSON file to TOON format using streaming.
     *
     * Note: JSON must be fully read to decode, but output is written in chunks.
     */
    public function encodeStream(string $inputPath, string $outputPath): void
    {
        // Read entire JSON file (JSON requires full parsing)
        $jsonContent = $this->readInput($inputPath);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        // Open output for writing
        $outputHandle = $this->openOutput($outputPath);

        try {
            // Use lazy encoder to write line by line
            $lazyEncoder = $this->toon->lazy($jsonData);

            foreach ($lazyEncoder->generate() as $line) {
                fwrite($outputHandle, $line."\n");
            }
        } finally {
            if (is_resource($outputHandle)) {
                fclose($outputHandle);
            }
        }
    }

    /**
     * Read input file content.
     */
    protected function readInput(string $path): string
    {
        // Check if it's a Laravel Storage disk path
        if (str_contains($path, ':')) {
            [$disk, $filePath] = explode(':', $path, 2);
            return Storage::disk($disk)->get($filePath);
        }

        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Input file not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Could not read input file: {$path}");
        }

        return $content;
    }

    /**
     * Open input file handle.
     */
    protected function openInput(string $path)
    {
        // Check if it's a Laravel Storage disk path (e.g., "disk:path/to/file.json")
        if (str_contains($path, ':')) {
            [$disk, $filePath] = explode(':', $path, 2);
            $fullPath = Storage::disk($disk)->path($filePath);
        } else {
            $fullPath = $path;
        }

        if (! file_exists($fullPath)) {
            throw new \InvalidArgumentException("Input file not found: {$fullPath}");
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Could not open input file: {$fullPath}");
        }

        return $handle;
    }

    /**
     * Open output file handle.
     */
    protected function openOutput(string $path)
    {
        // Check if it's a Laravel Storage disk path
        if (str_contains($path, ':')) {
            [$disk, $filePath] = explode(':', $path, 2);
            $fullPath = Storage::disk($disk)->path($filePath);
            $directory = dirname($fullPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        } else {
            $fullPath = $path;
            $directory = dirname($fullPath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        $handle = fopen($fullPath, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Could not open output file: {$fullPath}");
        }

        return $handle;
    }

    /**
     * Try to decode JSON string, returning null if incomplete.
     */
    protected function tryDecodeJson(string $jsonString): ?array
    {
        $jsonString = trim($jsonString);
        if (empty($jsonString)) {
            return null;
        }

        $decoded = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If JSON is incomplete, return null
        return null;
    }
}

