<?php

namespace DigitalCoreHub\Toon\Tests;

use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
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
            ],
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

    /**
     * Test decode simple block TOON → JSON.
     */
    public function test_decode_simple_block(): void
    {
        $toon = "reviews[1]{\n  id, customer, rating, comment, verified;\n  101, Alex Rivera, 5, Excellent!, true\n}";

        $result = Toon::decode($toon);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(101, $result[0]['id']);
        $this->assertEquals('Alex Rivera', $result[0]['customer']);
        $this->assertEquals(5, $result[0]['rating']);
        $this->assertEquals('Excellent!', $result[0]['comment']);
        $this->assertTrue($result[0]['verified']);
    }

    /**
     * Test decode multiple rows.
     */
    public function test_decode_multiple_rows(): void
    {
        $toon = "reviews[2]{\n  id, customer, rating;\n  1, Alice, 5\n  2, Bob, 4\n}";

        $result = Toon::decode($toon);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Alice', $result[0]['customer']);
        $this->assertEquals(5, $result[0]['rating']);
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('Bob', $result[1]['customer']);
        $this->assertEquals(4, $result[1]['rating']);
    }

    /**
     * Test decode nested objects/arrays.
     */
    public function test_decode_nested_structures(): void
    {
        $toon = "product, reviews;\nLaptop\nreviews[2]{\n  id, customer, rating;\n  1, Alice, 5\n  2, Bob, 4\n}";

        $result = Toon::decode($toon);

        $this->assertIsArray($result);
        $this->assertEquals('Laptop', $result['product']);
        $this->assertIsArray($result['reviews']);
        $this->assertCount(2, $result['reviews']);
        $this->assertEquals(1, $result['reviews'][0]['id']);
        $this->assertEquals('Alice', $result['reviews'][0]['customer']);
    }

    /**
     * Test decode with incorrect TOON format - missing semicolon.
     */
    public function test_decode_missing_semicolon(): void
    {
        $this->expectException(InvalidToonFormatException::class);
        $this->expectExceptionMessage('Keys line must end with semicolon');

        $toon = "id, name, price\n1, Test, 99.99";
        Toon::decode($toon);
    }

    /**
     * Test decode with mismatched key/value counts.
     */
    public function test_decode_mismatched_key_value_count(): void
    {
        $this->expectException(InvalidToonFormatException::class);
        $this->expectExceptionMessage('Missing values for object keys');

        $toon = "id, name, price;\n1, Test";
        Toon::decode($toon);
    }

    /**
     * Test decode with unclosed brackets.
     */
    public function test_decode_unclosed_brackets(): void
    {
        $this->expectException(InvalidToonFormatException::class);
        $this->expectExceptionMessage('Unclosed array block');

        $toon = "reviews[1]{\n  id, customer;\n  1, Alice";
        Toon::decode($toon);
    }

    /**
     * Test round-trip: encode then decode.
     */
    public function test_encode_decode_round_trip(): void
    {
        $original = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 99.99,
            'active' => true,
        ];

        $encoded = Toon::encode($original);
        $decoded = Toon::decode($encoded);

        $this->assertEquals($original, $decoded);
    }
}
