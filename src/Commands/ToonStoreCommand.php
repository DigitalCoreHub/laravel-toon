<?php

namespace DigitalCoreHub\Toon\Commands;

use DigitalCoreHub\Toon\Facades\Toon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ToonStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toon:store {input : The input JSON file path} {output : The output TOON file path} {--disk= : The storage disk to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a JSON file to TOON format and store it using Laravel Storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');
        $disk = $this->option('disk');

        // Check if input file exists
        if (! File::exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");

            return Command::FAILURE;
        }

        // Read JSON from file
        $jsonContent = File::get($inputPath);

        try {
            // Decode to validate JSON
            $jsonData = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in input file: '.json_last_error_msg());

                return Command::FAILURE;
            }

            // Store using Toon::store()
            $savedPath = Toon::store($outputPath, $jsonData, $disk);

            $this->info('Successfully converted JSON to TOON format and stored!');
            $this->info("File saved to: {$savedPath}");
            if ($disk) {
                $this->info("Storage disk: {$disk}");
            } else {
                $this->info('Storage disk: '.config('toon.storage.default_disk', 'local'));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error converting and storing JSON to TOON: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}

