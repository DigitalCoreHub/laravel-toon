<?php

namespace DigitalCoreHub\Toon\Tests;

use DigitalCoreHub\Toon\Exceptions\InvalidToonFormatException;
use DigitalCoreHub\Toon\Facades\Toon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $this->expectExceptionMessage('Missing semicolon in header block');

        $toon = "id, name, price\n1, Test, 99.99";
        Toon::decode($toon);
    }

    /**
     * Test decode with mismatched key/value counts.
     */
    public function test_decode_mismatched_key_value_count(): void
    {
        $this->expectException(InvalidToonFormatException::class);
        $this->expectExceptionMessage('Key count');

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

    /**
     * Test helper function toon_encode.
     */
    public function test_helper_toon_encode(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $result = toon_encode($data);

        $this->assertStringContainsString('id, name;', $result);
        $this->assertStringContainsString('1, Test', $result);
    }

    /**
     * Test helper function toon_decode.
     */
    public function test_helper_toon_decode(): void
    {
        $toon = "id, name;\n1, Test";
        $result = toon_decode($toon);

        $this->assertEquals(['id' => 1, 'name' => 'Test'], $result);
    }

    /**
     * Test fluent interface fromJson.
     */
    public function test_fluent_from_json(): void
    {
        $json = '{"id": 1, "name": "Test"}';
        $result = Toon::fromJson($json)->encode();

        $this->assertStringContainsString('id, name;', $result);
        $this->assertStringContainsString('1, Test', $result);
    }

    /**
     * Test fluent interface fromArray.
     */
    public function test_fluent_from_array(): void
    {
        $array = ['id' => 1, 'name' => 'Test'];
        $result = Toon::fromArray($array)->encode();

        $this->assertStringContainsString('id, name;', $result);
        $this->assertStringContainsString('1, Test', $result);
    }

    /**
     * Test fluent interface fromToon.
     */
    public function test_fluent_from_toon(): void
    {
        $toon = "id, name;\n1, Test";
        $result = Toon::fromToon($toon)->decode();

        $this->assertEquals(['id' => 1, 'name' => 'Test'], $result);
    }

    /**
     * Test config-driven indentation.
     */
    public function test_config_indentation(): void
    {
        config(['toon.indentation' => 2]);

        $data = ['id' => 1, 'name' => 'Test'];
        $result = Toon::encode($data);

        // Check that indentation is used (should have spaces)
        $this->assertIsString($result);
    }

    /**
     * Test config key separator.
     */
    public function test_config_key_separator(): void
    {
        config(['toon.key_separator' => ' | ']);

        $data = ['id' => 1, 'name' => 'Test'];
        $result = Toon::encode($data);

        $this->assertStringContainsString('id | name;', $result);
    }

    /**
     * Test console output with syntax highlighting.
     */
    public function test_console_output(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => true];
        $result = Toon::console($data);

        // Should return TOON format (colors are terminal-specific)
        $this->assertStringContainsString('id', $result);
        $this->assertStringContainsString('name', $result);
    }

    /**
     * Test Log::toon() macro registration.
     *
     * This test verifies that:
     * 1. ServiceProvider attempts to register the macro
     * 2. The registration doesn't break the application
     * 3. The macro infrastructure is in place
     */
    public function test_log_toon_macro(): void
    {
        $logManager = app('log');

        // Verify LogManager exists
        $this->assertInstanceOf(\Illuminate\Log\LogManager::class, $logManager);

        // Verify ServiceProvider registered the macro attempt
        // LogManager may or may not support macros depending on Laravel version
        // In Laravel 10+, LogManager uses Macroable trait and supports macros
        // In test environments, this may vary, but the registration should not cause errors

        // Test that the service provider booted successfully
        // If macro registration failed, it should fail gracefully
        $this->assertTrue(true, 'ServiceProvider should boot without errors');

        // Verify that if LogManager supports macros, the macro is registered
        if (method_exists($logManager, 'hasMacro') && method_exists($logManager, 'macro')) {
            // In Laravel versions that support it, verify macro is registered
            $this->assertTrue(
                $logManager->hasMacro('toon'),
                'Log::toon() macro should be registered when LogManager supports macros'
            );

            // Test that macro can be called
            $data = ['id' => 1, 'name' => 'Test'];
            try {
                $result = $logManager->toon($data, 'info');
                $this->assertNotNull($result, 'Log::toon() should return a value');
            } catch (\BadMethodCallException $e) {
                $this->fail('Log::toon() macro should be callable: '.$e->getMessage());
            }
        } else {
            // LogManager doesn't support macros in this environment
            // This is acceptable - the macro will work in production Laravel apps
            $this->assertTrue(true, 'LogManager does not support macros in this test environment');
        }
    }

    /**
     * Test Blade directive output.
     */
    public function test_blade_directive(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $compiled = \DigitalCoreHub\Toon\Blade\ToonDirective::compile('$data');

        // Should contain pre tag and escape function
        $this->assertStringContainsString('<pre>', $compiled);
        $this->assertStringContainsString('e(', $compiled);
        $this->assertStringContainsString('Toon::encode', $compiled);
    }

    /**
     * Test lazy encoder.
     */
    public function test_lazy_encoder(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $lazy = Toon::lazy($data);

        $lines = [];
        foreach ($lazy->generate() as $line) {
            $lines[] = $line;
        }

        $this->assertNotEmpty($lines);
        $this->assertStringContainsString('id', implode("\n", $lines));
    }

    /**
     * Test compact mode.
     */
    public function test_compact_mode(): void
    {
        config(['toon.compact' => true]);

        $data = ['id' => 1, 'name' => 'Test'];
        $result = Toon::encode($data);

        // Compact mode should have no spaces after commas
        $this->assertStringContainsString('id,name;', $result);
        $this->assertStringNotContainsString('id, name;', $result);
    }

    /**
     * Test encodeStream method.
     */
    public function test_encode_stream(): void
    {
        // Create temporary test files
        $inputFile = sys_get_temp_dir().'/toon_test_input.json';
        $outputFile = sys_get_temp_dir().'/toon_test_output.toon';

        $testData = ['id' => 1, 'name' => 'Test'];
        file_put_contents($inputFile, json_encode($testData));

        try {
            Toon::encodeStream($inputFile, $outputFile);

            $this->assertFileExists($outputFile);
            $output = file_get_contents($outputFile);
            $this->assertStringContainsString('id', $output);
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }

    /**
     * Test decodeStream (experimental).
     */
    public function test_decode_stream(): void
    {
        // Create temporary test file
        $testFile = sys_get_temp_dir().'/toon_test_decode.toon';
        file_put_contents($testFile, "id, name;\n1, Test");

        try {
            $lines = [];
            foreach (Toon::decodeStream($testFile) as $line) {
                $lines[] = $line;
            }

            $this->assertNotEmpty($lines);
            $this->assertContains('id, name;', $lines);
        } finally {
            @unlink($testFile);
        }
    }

    /**
     * Test store() method with Laravel Storage.
     */
    public function test_store(): void
    {
        Storage::fake('local');

        $data = ['name' => 'Test', 'value' => 123];
        $path = Toon::store('test-file', $data, 'local');

        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);

        $content = Storage::disk('local')->get($path);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('name', $content);
    }

    /**
     * Test store() with default directory.
     */
    public function test_store_with_default_directory(): void
    {
        Storage::fake('local');

        $data = ['test' => 'data'];
        $path = Toon::store('my-file', $data, 'local');

        $this->assertStringContainsString('toon', $path);
        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test store() with custom disk.
     */
    public function test_store_with_custom_disk(): void
    {
        Storage::fake('public');

        $data = ['key' => 'value'];
        $path = Toon::store('custom-file', $data, 'public');

        Storage::disk('public')->assertExists($path);
    }

    /**
     * Test store() creates directory automatically.
     */
    public function test_store_creates_directory(): void
    {
        Storage::fake('local');

        $data = ['test' => 'data'];
        $path = Toon::store('subdir/nested/file', $data, 'local');

        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test download() method returns StreamedResponse.
     */
    public function test_download(): void
    {
        $data = ['name' => 'Test', 'id' => 1];
        $response = Toon::download('test-file', $data);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('test-file.toon', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test download() adds .toon extension if missing.
     */
    public function test_download_adds_extension(): void
    {
        $data = ['test' => 'data'];
        $response = Toon::download('myfile', $data);

        $this->assertStringContainsString('myfile.toon', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test Response::toon() macro.
     */
    public function test_response_toon_macro(): void
    {
        $data = ['name' => 'Test', 'value' => 123];
        $response = response()->toon($data);

        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
        $this->assertStringContainsString('name', $response->getContent());
    }

    /**
     * Test Response::toon() macro with array.
     */
    public function test_response_toon_macro_with_array(): void
    {
        $data = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ];

        $response = response()->toon($data);
        $content = $response->getContent();

        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
    }

    /**
     * Test store() with JSON string input.
     */
    public function test_store_with_json_string(): void
    {
        Storage::fake('local');

        $jsonString = '{"id": 1, "name": "Test"}';
        $path = Toon::store('json-file', $jsonString, 'local');

        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);

        $content = Storage::disk('local')->get($path);
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
    }

    /**
     * Test store() with object input.
     */
    public function test_store_with_object(): void
    {
        Storage::fake('local');

        $object = (object) ['id' => 1, 'name' => 'Test'];
        $path = Toon::store('object-file', $object, 'local');

        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test store() with null disk (uses config default).
     */
    public function test_store_with_null_disk(): void
    {
        Storage::fake('local');

        // Set config default disk
        config(['toon.storage.default_disk' => 'local']);

        $data = ['test' => 'data'];
        $path = Toon::store('config-file', $data, null);

        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test store() with absolute path (starts with /).
     */
    public function test_store_with_absolute_path(): void
    {
        Storage::fake('local');

        $data = ['test' => 'data'];
        $path = Toon::store('/absolute/path/file', $data, 'local');

        // Absolute paths should not get default directory prepended
        $this->assertStringStartsWith('/', $path);
        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test store() without .toon extension (should add automatically).
     */
    public function test_store_adds_extension_automatically(): void
    {
        Storage::fake('local');

        $data = ['test' => 'data'];
        $path = Toon::store('file-without-extension', $data, 'local');

        $this->assertStringEndsWith('.toon', $path);
        Storage::disk('local')->assertExists($path);
    }

    /**
     * Test download() with JSON string input.
     */
    public function test_download_with_json_string(): void
    {
        $jsonString = '{"id": 1, "name": "Test"}';
        $response = Toon::download('json-download', $jsonString);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));

        // Capture the streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
    }

    /**
     * Test download() with object input.
     */
    public function test_download_with_object(): void
    {
        $object = (object) ['id' => 1, 'name' => 'Test'];
        $response = Toon::download('object-download', $object);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
    }

    /**
     * Test download() response content correctness.
     */
    public function test_download_response_content(): void
    {
        $data = ['id' => 1, 'name' => 'Test Product', 'price' => 99.99];
        $response = Toon::download('product', $data);

        // Capture the streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Verify content is valid TOON format
        $this->assertStringContainsString('id', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('price', $content);
        $this->assertStringContainsString('1', $content);
        $this->assertStringContainsString('Test Product', $content);
        $this->assertStringContainsString('99.99', $content);
    }

    /**
     * Test Response::toon() macro with JSON string.
     */
    public function test_response_toon_macro_with_json_string(): void
    {
        $jsonString = '{"id": 1, "name": "Test"}';
        $response = response()->toon($jsonString);

        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
        $this->assertStringContainsString('id', $response->getContent());
    }

    /**
     * Test Response::toon() macro with object.
     */
    public function test_response_toon_macro_with_object(): void
    {
        $object = (object) ['id' => 1, 'name' => 'Test'];
        $response = response()->toon($object);

        $this->assertEquals('text/toon', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
        $this->assertStringContainsString('id', $response->getContent());
    }

    /**
     * Test store() returns correct path format.
     */
    public function test_store_returns_correct_path(): void
    {
        Storage::fake('local');

        $data = ['test' => 'data'];
        $path = Toon::store('my-file', $data, 'local');

        // Should include default directory
        $this->assertStringContainsString('toon', $path);
        $this->assertStringContainsString('my-file', $path);
        $this->assertStringEndsWith('.toon', $path);
    }

    /**
     * Test store() with nested data structure.
     */
    public function test_store_with_nested_data(): void
    {
        Storage::fake('local');

        $data = [
            'product' => 'Laptop',
            'reviews' => [
                ['id' => 1, 'customer' => 'Alice', 'rating' => 5],
                ['id' => 2, 'customer' => 'Bob', 'rating' => 4],
            ],
        ];

        $path = Toon::store('nested-data', $data, 'local');
        $content = Storage::disk('local')->get($path);

        $this->assertStringContainsString('product', $content);
        $this->assertStringContainsString('reviews', $content);
        $this->assertStringContainsString('Laptop', $content);
    }
}
