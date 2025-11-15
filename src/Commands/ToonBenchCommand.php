<?php

namespace DigitalCoreHub\Toon\Commands;

use DigitalCoreHub\Toon\Facades\Toon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToonBenchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toon:bench {file? : Path to JSON file to benchmark}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark TOON encode/decode performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');

        // Use default benchmark files if not provided
        if (! $file) {
            $benchDir = __DIR__.'/../../tests/bench';
            if (is_dir($benchDir)) {
                $files = glob($benchDir.'/*.json');
                if (! empty($files)) {
                    $file = $files[0]; // Use first available file
                }
            }
        }

        if (! $file || ! File::exists($file)) {
            $this->error('Benchmark file not found. Please provide a valid JSON file path.');
            $this->info('Example: php artisan toon:bench tests/bench/large.json');

            return Command::FAILURE;
        }

        $this->info("Benchmarking: {$file}");
        $this->newLine();

        // Read JSON
        $jsonContent = File::get($file);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: '.json_last_error_msg());

            return Command::FAILURE;
        }

        // Measure memory before
        $memoryBefore = memory_get_usage(true);

        // Benchmark ENCODE
        $encodeStart = microtime(true);
        $toonOutput = Toon::encode($jsonData);
        $encodeEnd = microtime(true);
        $encodeTime = ($encodeEnd - $encodeStart) * 1000; // Convert to milliseconds

        // Measure memory after encode
        $memoryAfterEncode = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        // Benchmark DECODE
        $decodeStart = microtime(true);
        $decodedData = Toon::decode($toonOutput);
        $decodeEnd = microtime(true);
        $decodeTime = ($decodeEnd - $decodeStart) * 1000; // Convert to milliseconds

        // Calculate statistics
        $rows = $this->countRows($jsonData);
        $keys = $this->countKeys($jsonData);
        $memoryUsed = ($memoryAfterEncode - $memoryBefore) / 1024 / 1024; // MB
        $memoryPeakMB = $memoryPeak / 1024 / 1024; // MB

        // Display results
        $this->table(
            ['Metric', 'Value'],
            [
                ['ENCODE', number_format($encodeTime, 2).' ms'],
                ['DECODE', number_format($decodeTime, 2).' ms'],
                ['Memory Peak', number_format($memoryPeakMB, 2).' MB'],
                ['Memory Used', number_format($memoryUsed, 2).' MB'],
                ['Rows', number_format($rows)],
                ['Keys', number_format($keys)],
                ['TOON Size', number_format(strlen($toonOutput)).' bytes'],
                ['JSON Size', number_format(strlen($jsonContent)).' bytes'],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Count total rows in data structure.
     */
    protected function countRows(mixed $data): int
    {
        if (! is_array($data)) {
            return 1;
        }

        $count = 0;
        foreach ($data as $value) {
            if (is_array($value) && ! $this->isAssociativeArray($value)) {
                $count += count($value);
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $count += $this->countRows($item);
                    }
                }
            } else {
                $count++;
            }
        }

        return max(1, $count);
    }

    /**
     * Count total unique keys in data structure.
     */
    protected function countKeys(mixed $data): int
    {
        if (! is_array($data)) {
            return 0;
        }

        $keys = [];
        $this->collectKeys($data, $keys);

        return count(array_unique($keys));
    }

    /**
     * Recursively collect all keys from data structure.
     */
    protected function collectKeys(mixed $data, array &$keys): void
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $keys[] = $key;
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $keys = array_merge($keys, array_keys($value));
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $this->collectKeys($item, $keys);
                        }
                    }
                }
            }
        }
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
}

