<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

App\Bootstrap::boot()->createContainer();

bdump('Cli request ' . date('H:i:s'));
