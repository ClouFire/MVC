<?php

final class CoverageGateTest extends FrameworkTestCase {
    public function testOnlyCompleteNonemptyReportsPass(): void {
        $file = tempnam(sys_get_temp_dir(), 'mvc-coverage-gate-');

        try {
            foreach ([
                ['', 1],
                ['<coverage/>', 1],
                ['<coverage><project><metrics statements="0" coveredstatements="0"/></project></coverage>', 1],
                ['<coverage><project><metrics statements="10" coveredstatements="9"/></project></coverage>', 1],
                ['<coverage><project><metrics statements="10" coveredstatements="10"/></project></coverage>', 0],
            ] as [$report, $expected]) {
                file_put_contents($file, $report);

                $process = proc_open(
                    [PHP_BINARY, __DIR__ . '/check_coverage.php', $file],
                    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
                    $pipes
                );

                self::assertIsResource($process);

                fclose($pipes[0]);
                stream_get_contents($pipes[1]);
                stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                self::assertSame($expected, proc_close($process));
            }
        } finally {
            unlink($file);
        }
    }
}
