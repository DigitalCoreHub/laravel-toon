<?php

namespace DigitalCoreHub\Toon;

class ToonBuilder
{
    /**
     * The data to process.
     */
    protected mixed $data = null;

    /**
     * The operation type (encode or decode).
     */
    protected ?string $operation = null;

    /**
     * Create a new ToonBuilder instance.
     */
    public function __construct(protected Toon $toon) {}

    /**
     * Set data from JSON string.
     */
    public function fromJson(string $json): self
    {
        $this->data = $json;
        $this->operation = 'encode';

        return $this;
    }

    /**
     * Set data from array.
     */
    public function fromArray(array $array): self
    {
        $this->data = $array;
        $this->operation = 'encode';

        return $this;
    }

    /**
     * Set data from TOON string.
     */
    public function fromToon(string $toon): self
    {
        $this->data = $toon;
        $this->operation = 'decode';

        return $this;
    }

    /**
     * Encode the data to TOON format.
     */
    public function encode(): string
    {
        if ($this->operation !== 'encode') {
            throw new \RuntimeException('Cannot encode. Use fromJson() or fromArray() first.');
        }

        return $this->toon->encode($this->data);
    }

    /**
     * Decode the TOON string to array.
     */
    public function decode(): array
    {
        if ($this->operation !== 'decode') {
            throw new \RuntimeException('Cannot decode. Use fromToon() first.');
        }

        if (! is_string($this->data)) {
            throw new \RuntimeException('Data must be a string for decoding.');
        }

        return $this->toon->decode($this->data);
    }
}
