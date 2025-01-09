<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar\Tracy;

use Forrest79\TracyRemoteBar\Helper;
use Forrest79\TracyRemoteBar\Remote;
use Tracy;
use Tracy\DeferredContent;
use Tracy\Helpers;

class DevelopmentStrategy extends Tracy\DevelopmentStrategy
{
	private Tracy\BlueScreen $blueScreen;


	public function __construct(Tracy\Bar $bar, Tracy\BlueScreen $blueScreen, DeferredContent $defer)
	{
		parent::__construct($bar, $blueScreen, $defer);
		$this->blueScreen = $blueScreen;
	}


	public function handleException(\Throwable $exception, bool $firstTime): void
	{
		if (Remote::isEnabled() && Helper::isHttpAjax()) {
			Remote::addBar(Helpers::capture(function () use ($exception): void {
				(function () use ($exception): void {
					$this->renderTemplate($exception, Helper::classDir(Tracy\BlueScreen::class) . '/assets/page.phtml', FALSE);
				})->call($this->blueScreen);
			}));
			return;
		}

		parent::handleException($exception, $firstTime);
	}

}
