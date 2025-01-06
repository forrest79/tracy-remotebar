<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteDevelopmentStrategy\Bridges\Nette;

use Forrest79\TracyRemoteDevelopmentStrategy;
use Nette;
use Nette\Schema\Expect;

class TracyRemoteDevelopmentStrategyExtension extends Nette\DI\CompilerExtension
{

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'serverUrl' => Expect::string()->required()->dynamic(),
		]);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class): void
	{
		$body = $class->getMethod('initialize')->getBody();
		$body = preg_replace('#// tracy\.\n\(function \(\) {?.+}\)\(\);\n#ms', '', $body);
		assert($body !== NULL);
		$class->getMethod('initialize')->setBody($body);

		$config = (array) $this->config;
		$this->initialization->addBody(TracyRemoteDevelopmentStrategy\RemoteBar::class . '::setServerUrl(?);', [$config['serverUrl']]);
	}

}
