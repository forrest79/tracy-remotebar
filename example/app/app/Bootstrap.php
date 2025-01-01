<?php declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

class Bootstrap
{

	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$appDir = dirname(__DIR__);

		$configurator->setDebugMode(TRUE);
		$configurator->enableTracy($appDir . '/logs');

		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory($appDir . '/temp');

		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();

		$configurator
			->addConfig($appDir . '/config/common.neon')
			->addConfig($appDir . '/config/local.neon');

		return $configurator;
	}

}
