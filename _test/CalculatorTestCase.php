<?php
/** Shared assertions for Calculator's framework-independent PHPUnit checks. */
abstract class CalculatorTestCase extends \PHPUnit\Framework\TestCase
{
    protected function assertFixture($path, $json): void
    {
        $this->assertFileExists($path);
        $this->assertIsString($json);
        $this->assertJsonStringEqualsJsonString(file_get_contents($path), $json, basename($path).' is out of date');
    }
}
