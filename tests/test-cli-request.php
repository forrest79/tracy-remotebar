#!/bin/php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Bootstrap\Configurator();

$configurator
	->setDebugMode(TRUE)
	->setTempDirectory(__DIR__ . '/temp')
	->addConfig(__DIR__ . '/config.neon')
	->enableTracy(__DIR__ . '/logs');

$configurator->createContainer();

bdump('Cli request ' . date('H:i:s'));
