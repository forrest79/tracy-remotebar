<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar\Bridges\Nette;

use Forrest79\TracyRemoteBar;
use Nette;
use Nette\Schema\Expect;

class TracyRemoteBarExtension extends Nette\DI\CompilerExtension
{

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'enabled' => Expect::bool(FALSE)->dynamic(),
			'serverUrl' => Expect::string()->dynamic(),
		]);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		$config = (array) $this->config;
		$enabled = $config['enabled'];
		$serverUrl = $config['serverUrl'];
		assert(is_bool($enabled) || is_string($serverUrl));

		if ($enabled) {
			$this->initialization->addBody(TracyRemoteBar\Remote::class . '::enable(?);', [$serverUrl]);
		}
	}

}
