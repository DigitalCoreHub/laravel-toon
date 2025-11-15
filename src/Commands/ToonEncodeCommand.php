<?php

namespace DigitalCoreHub\Toon\Commands;

use DigitalCoreHub\Toon\Facades\Toon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToonEncodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toon:encode {input : The input JSON file path} {output : The output TOON file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a JSON file to TOON format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');

        // Check if input file exists
        if (!File::exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return Command::FAILURE;
        }

        // Read JSON from file
        $jsonContent = File::get($inputPath);

        try {
            // Decode to validate JSON
            $jsonData = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in input file: ' . json_last_error_msg());
                return Command::FAILURE;
            }

            // Convert to TOON
            $toonContent = Toon::encode($jsonData);

            // Save to output file
            File::put($outputPath, $toonContent);

            $this->info("Successfully converted JSON to TOON format!");
            $this->info("Output saved to: {$outputPath}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error converting JSON to TOON: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

