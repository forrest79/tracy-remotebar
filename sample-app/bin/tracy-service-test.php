<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';


$container = App\Bootstrap::boot()->createContainer();

while (TRUE) {
	echo date('H:i:s') . PHP_EOL;
	bdump('Cli service request ' . date('H:i:s'));
	Tracy\Debugger::remoteDispatchBars();
	sleep(2);
}
