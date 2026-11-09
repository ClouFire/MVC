<?php

$coverageDirectory = getenv('TEST_SUBPROCESS_COVERAGE');

if ($coverageDirectory && function_exists('xdebug_start_code_coverage')
    && in_array('coverage', xdebug_info('mode'), true)) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

    register_shutdown_function(function () use ($coverageDirectory): void {
        file_put_contents(
            $coverageDirectory . '/' . bin2hex(random_bytes(8)) . '.json',
            json_encode(xdebug_get_code_coverage(), JSON_THROW_ON_ERROR)
        );
    });
}
