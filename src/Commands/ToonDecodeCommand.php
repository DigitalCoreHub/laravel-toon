<?php

namespace DigitalCoreHub\Toon\Commands;

use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
use DigitalCoreHub\Toon\Facades\Toon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToonDecodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toon:decode {input : The input TOON file path} {output : The output JSON file path} {--preview|p : Show colored preview of the input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert a TOON file to JSON format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');

        // Check if input file exists
        if (! File::exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");

            return Command::FAILURE;
        }

        // Read TOON from file
        $toonContent = File::get($inputPath);

        try {
            // Decode TOON to array
            $jsonData = Toon::decode($toonContent);

            // Convert to JSON with pretty print
            $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Error encoding to JSON: '.json_last_error_msg());

                return Command::FAILURE;
            }

            // Save to output file
            File::put($outputPath, $jsonContent);

            $this->info('Successfully converted TOON to JSON format!');
            $this->info("Output saved to: {$outputPath}");

            // Show preview with colors
            if ($this->option('preview') || $this->option('p')) {
                $this->newLine();
                $this->line('Preview (TOON input):');
                $this->line(Toon::console($jsonData, $this->output));
            }

            return Command::SUCCESS;
        } catch (InvalidToonFormatException $e) {
            $this->error('Invalid TOON format: '.$e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Error converting TOON to JSON: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
