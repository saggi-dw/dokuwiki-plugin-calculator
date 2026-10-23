<?php
require_once __DIR__.'/CalculatorTestCase.php';

/** @group plugin_calculator */
class CalculatorStructIsolationTest extends CalculatorTestCase
{
    public function testStructAdapterWithRecordedNativeParserConfigurations(): void
    {
        $this->runIsolated('struct.check.php', '70 Struct integration checks passed.');
    }

    public function testChemistryWithoutStructInstalled(): void
    {
        $this->runIsolated('struct-optional.check.php', '6 optional Struct dependency checks passed.');
    }

    private function runIsolated($file, $expected): void
    {
        $process = proc_open([PHP_BINARY, __DIR__.'/isolated/'.$file], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $status = proc_close($process);
        $this->assertSame(0, $status, $stdout.$stderr);
        $this->assertStringContainsString($expected, $stdout);
    }
}
