<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteDevelopmentStrategy\Tracy;

use Forrest79\TracyRemoteDevelopmentStrategy\RemoteBar;
use Tracy;
use Tracy\DeferredContent;
use Tracy\Helpers;

final class DevelopmentStrategy extends Tracy\DevelopmentStrategy
{
	private Tracy\Bar $bar;

	private Tracy\BlueScreen $blueScreen;

	private DeferredContent $defer;


	public function __construct(Tracy\Bar $bar, Tracy\BlueScreen $blueScreen, DeferredContent $defer)
	{
		parent::__construct($bar, $blueScreen, $defer);
		$this->bar = $bar;
		$this->blueScreen = $blueScreen;
		$this->defer = $defer;
	}


	public function handleException(\Throwable $exception, bool $firstTime): void
	{
		if (RemoteBar::isRemoteActive() && RemoteBar::isHttpAjax()) {
			RemoteBar::add(Helpers::capture(function () use ($exception): void {
				(function () use ($exception): void {
					$this->renderTemplate($exception, RemoteBar::classDir(Tracy\BlueScreen::class) . '/assets/page.phtml', FALSE);
				})->call($this->blueScreen);
			}));
			return;
		}

		parent::handleException($exception, $firstTime);
	}


	public function renderBar(): void
	{
		if (RemoteBar::isRemoteActive()) {
			RemoteBar::add(Helpers::capture(function (): void {
				if (function_exists('ini_set')) {
					ini_set('display_errors', '1');
				}

				if (RemoteBar::isHttpAjax()) {
					$type = 'ajax';
				} elseif (Helpers::isCli()) {
					$type = 'cli';
				} elseif (Helpers::isRedirect()) {
					$type = 'redirect';
				} else {
					$type = 'main';
				}

				$content = (fn (): array => $this->renderPartial($type))->call($this->bar);
				assert(is_string($content['bar']) && is_string($content['panels']));

				$content = '<div id=tracy-debug-bar>' . $content['bar'] . '</div>' . $content['panels'];

				$requestId = $this->defer->getRequestId();
				$nonceAttr = Helpers::getNonceAttr();
				$async = FALSE;

				require RemoteBar::classDir(Tracy\Bar::class) . '/assets/loader.phtml';
			}));

			return;
		}

		parent::renderBar();
	}

}
