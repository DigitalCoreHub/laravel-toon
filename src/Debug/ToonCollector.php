<?php

namespace DigitalCoreHub\Toon\Debug;

use Barryvdh\Debugbar\DataCollector\DataCollector;
use Barryvdh\Debugbar\DataCollector\DataCollectorInterface;
use Barryvdh\Debugbar\DataCollector\Renderable;

class ToonCollector extends DataCollector implements DataCollectorInterface, Renderable
{
    /**
     * Store TOON operations.
     */
    protected array $operations = [];

    /**
     * Add an encode operation.
     */
    public function addEncode(mixed $data, string $toon, float $duration): void
    {
        $this->operations[] = [
            'type' => 'encode',
            'input' => $this->formatInput($data),
            'output' => $toon,
            'duration' => $duration,
            'metadata' => $this->extractMetadata($data, $toon),
            'time' => microtime(true),
        ];
    }

    /**
     * Add a decode operation.
     */
    public function addDecode(string $toon, array $result, float $duration): void
    {
        $this->operations[] = [
            'type' => 'decode',
            'input' => $toon,
            'output' => $this->formatOutput($result),
            'duration' => $duration,
            'metadata' => $this->extractDecodeMetadata($toon, $result),
            'time' => microtime(true),
        ];
    }

    /**
     * Format input data for display.
     */
    protected function formatInput(mixed $data): string
    {
        if (is_array($data)) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return (string) $data;
    }

    /**
     * Format output data for display.
     */
    protected function formatOutput(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Extract metadata from encode operation.
     */
    protected function extractMetadata(mixed $data, string $toon): array
    {
        $metadata = [
            'toon_length' => strlen($toon),
            'toon_lines' => substr_count($toon, "\n") + 1,
        ];

        if (is_array($data)) {
            $metadata['keys_count'] = count($data);
            if (! empty($data) && is_array($data[0] ?? null)) {
                $metadata['rows_count'] = count($data);
            }
        }

        return $metadata;
    }

    /**
     * Extract metadata from decode operation.
     */
    protected function extractDecodeMetadata(string $toon, array $result): array
    {
        return [
            'toon_length' => strlen($toon),
            'toon_lines' => substr_count($toon, "\n") + 1,
            'keys_count' => ! empty($result) && is_array($result[0] ?? null) ? count($result[0]) : count($result),
            'rows_count' => is_array($result[0] ?? null) ? count($result) : 1,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        return [
            'operations' => $this->operations,
            'count' => count($this->operations),
            'total_encode_time' => $this->calculateTotalTime('encode'),
            'total_decode_time' => $this->calculateTotalTime('decode'),
        ];
    }

    /**
     * Calculate total time for operation type.
     */
    protected function calculateTotalTime(string $type): float
    {
        return array_sum(
            array_map(
                fn ($op) => $op['duration'] ?? 0,
                array_filter($this->operations, fn ($op) => $op['type'] === $type)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'toon';
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets(): array
    {
        return [
            'toon' => [
                'icon' => 'code',
                'widget' => 'PhpDebugbar.Widgets.ToonWidget',
                'map' => 'toon',
                'default' => '{}',
            ],
        ];
    }
}
