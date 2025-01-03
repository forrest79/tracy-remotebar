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
			'serverUrl' => Expect::string()->dynamic(),
		]);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		$config = (array) $this->config;
		$serverUrl = $config['serverUrl'];
		assert($serverUrl === NULL || is_string($serverUrl));

		if ($serverUrl !== NULL) {
			$this->initialization->addBody(TracyRemoteBar\Remote::class . '::setServerUrl(?);', [$serverUrl]);
		}
	}

}
