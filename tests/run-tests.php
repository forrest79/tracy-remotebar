#!/bin/php
<?php declare(strict_types=1);

$tracyLogFile = __DIR__ . '/logs/tracy-remote-bar.log';

@unlink($tracyLogFile); // intentionally @ - file may not exists

if (exec('(cd ' . __DIR__ . ' && ./test-cli-request.php)', $output, $exitCode) === false) {
	echo 'An error occurred running test (exit code ' . $exitCode . '): ' . PHP_EOL . implode(PHP_EOL, $output) . PHP_EOL;
	exit(1);
}

if (!is_file($tracyLogFile)) {
	echo 'There is no tracy-remote-bar.log file.' . PHP_EOL;
	exit(2);
}

$log = file_get_contents($tracyLogFile);
if ($log === false) {
	echo 'Can\'t load data from tracy-remote-bar.log.' . PHP_EOL;
	exit(3);
}

if (!str_contains($log, '#28: Connection timed out after')) {
	echo 'There is missing info about timed out connection in tracy-remote-bar.log. Log data:' . PHP_EOL . $log . PHP_EOL;
	exit(4);
}

echo 'OK' . PHP_EOL;
