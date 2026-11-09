<?php

$file = $argv[1] ?? __DIR__ . '/.coverage/clover.xml';

if (!is_file($file)) {
    fwrite(STDERR, 'Coverage report is missing.' . PHP_EOL);

    exit(1);
}

$document = new DOMDocument();

if (!@$document->load($file, LIBXML_NONET)) {
    fwrite(STDERR, 'Coverage report is invalid.' . PHP_EOL);

    exit(1);
}

$metrics = (new DOMXPath($document))->query('/coverage/project/metrics')->item(0);

if (!$metrics instanceof DOMElement) {
    fwrite(STDERR, 'Coverage report has no project metrics.' . PHP_EOL);

    exit(1);
}

$statements = (int) $metrics->getAttribute('statements');
$covered = (int) $metrics->getAttribute('coveredstatements');

if ($statements < 1 || $statements !== $covered) {
    fwrite(STDERR, "Coverage requirement failed: {$covered}/{$statements} executable lines; 100% required." . PHP_EOL);

    exit(1);
}

fwrite(STDOUT, "Coverage requirement passed: {$covered}/{$statements} executable lines (100%)." . PHP_EOL);
