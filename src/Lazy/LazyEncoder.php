<?php

namespace DigitalCoreHub\Toon\Lazy;

use DigitalCoreHub\Toon\Toon;
use Generator;

class LazyEncoder
{
    protected Toon $toon;

    protected array $data;

    public function __construct(Toon $toon, array $data)
    {
        $this->toon = $toon;
        $this->data = $data;
    }

    /**
     * Generate TOON output line by line.
     */
    public function generate(): Generator
    {
        $toonString = $this->toon->encode($this->data);
        $lines = explode("\n", $toonString);

        foreach ($lines as $line) {
            yield $line;
        }
    }

    /**
     * Get all lines as an array.
     */
    public function toArray(): array
    {
        return iterator_to_array($this->generate());
    }

    /**
     * Write to a file line by line.
     */
    public function toFile(string $path): void
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Could not open file for writing: {$path}");
        }

        try {
            foreach ($this->generate() as $line) {
                fwrite($handle, $line."\n");
            }
        } finally {
            fclose($handle);
        }
    }
}

