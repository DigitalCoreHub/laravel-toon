<?php

namespace DigitalCoreHub\Toon\Tests;

use DigitalCoreHub\Toon\Facades\Toon;

class ToonTest extends TestCase
{
    /**
     * Test simple object conversion.
     */
    public function test_simple_object_conversion(): void
    {
        $json = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 99.99,
        ];

        $result = Toon::encode($json);

        $this->assertStringContainsString('id, name, price;', $result);
        $this->assertStringContainsString('1, Test Product, 99.99', $result);
    }

    /**
     * Test array with 1 item.
     */
    public function test_array_with_one_item(): void
    {
        $json = [
            [
                'id' => 1,
                'customer' => 'John Doe',
                'rating' => 5,
            ]
        ];

        $result = Toon::encode($json);

        $this->assertStringContainsString('array[1]{', $result);
        $this->assertStringContainsString('id, customer, rating;', $result);
        $this->assertStringContainsString('1, John Doe, 5', $result);
    }

    /**
     * Test nested array.
     */
    public function test_nested_array(): void
    {
        $json = [
            'product' => 'Laptop',
            'reviews' => [
                [
                    'id' => 1,
                    'customer' => 'Alice',
                    'rating' => 5,
                ],
                [
                    'id' => 2,
                    'customer' => 'Bob',
                    'rating' => 4,
                ],
            ],
        ];

        $result = Toon::encode($json);

        $this->assertStringContainsString('product, reviews;', $result);
        $this->assertStringContainsString('reviews[2]{', $result);
        $this->assertStringContainsString('id, customer, rating;', $result);
        $this->assertStringContainsString('1, Alice, 5', $result);
        $this->assertStringContainsString('2, Bob, 4', $result);
    }
}

