<?php

use PHPUnit\Framework\TestCase;

abstract class FrameworkTestCase extends TestCase {
    private ?string $subprocessCoverageDirectory = null;

    protected function setUp(): void {
        parent::setUp();

        if ($this->getTestResultObject()->getCodeCoverage() !== null) {
            $this->subprocessCoverageDirectory = sys_get_temp_dir() . '/mvc-subcoverage-' . bin2hex(random_bytes(8));

            mkdir($this->subprocessCoverageDirectory);
            putenv('TEST_SUBPROCESS_COVERAGE=' . $this->subprocessCoverageDirectory);
        }
    }

    protected function assertThrows(callable $operation, string $class): Throwable {
        try {
            $operation();
        } catch (Throwable $exception) {
            self::assertInstanceOf($class, $exception);

            return $exception;
        }

        self::fail('Expected exception: ' . $class);
    }

    protected function tearDown(): void {
        if ($this->subprocessCoverageDirectory !== null) {
            foreach (glob($this->subprocessCoverageDirectory . '/*.json') as $file) {
                $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

                $this->getTestResultObject()->getCodeCoverage()->append(
                    \SebastianBergmann\CodeCoverage\RawCodeCoverageData::fromXdebugWithoutPathCoverage($data),
                    $this
                );
                unlink($file);
            }

            rmdir($this->subprocessCoverageDirectory);
            putenv('TEST_SUBPROCESS_COVERAGE');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        parent::tearDown();
    }
}
