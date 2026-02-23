<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar;

use Tracy\Bar;
use Tracy\Debugger;
use Tracy\Helpers;

class Remote
{
	private static bool $enabled = false;

	private static string|null $serverUrl = null;

	private static int $curlConnectTimeout = 1;

	private static int $curlTimeout = 1;


	public static function enable(string|null $serverUrl): void
	{
		if (!Debugger::isEnabled()) {
			return;
		}

		self::$enabled = true;

		self::$serverUrl = $serverUrl;

		Debugger::$showBar = false;

		register_shutdown_function(static function (): void {
			self::sendBar();

			foreach (headers_list() as $header) {
				if (str_starts_with($header, 'X-Tracy-Error-Log:')) {
					self::sendBluescreen(substr($header, 19));
					break;
				}
			}
		});
	}


	public static function isEnabled(): bool
	{
		return self::$enabled;
	}


	public static function setCurlTimeouts(int|null $connectTimeout, int|null $timeout): void
	{
		self::$curlConnectTimeout = $connectTimeout ?? self::$curlConnectTimeout;
		self::$curlTimeout = $timeout ?? self::$curlTimeout;
	}


	public static function dispatchBars(): void
	{
		if (self::isEnabled()) {
			Debugger::removeOutputBuffers(false);
			self::sendBar();
		}
	}


	private static function sendBar(): void
	{
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
		}

		try {
			self::send(self::fixBarHtml(Helpers::capture(function (): void {
				if (Helper::isHttpAjax()) {
					$type = 'ajax';
				} elseif (Helpers::isCli()) {
					$type = 'cli';
				} elseif (Helpers::isRedirect()) {
					$type = 'redirect';
				} else {
					$type = 'main';
				}

				$contentParent = (fn (): array => $this->renderPartial($type))->call(Debugger::getBar());
				assert(is_string($contentParent['bar']) && is_string($contentParent['panels']));

				$content = '<div id=tracy-debug-bar>' . $contentParent['bar'] . '</div>' . $contentParent['panels'];

				$requestId = '';
				$async = false;

				require Helper::classDir(Bar::class) . '/dist/loader.phtml';
			})));
		} catch (\Throwable $e) {
			Debugger::exceptionHandler($e);
		}
	}


	private static function fixBarHtml(string $html): string
	{
		$html = str_replace('<li><a href=\\"#\\" data-tracy-action=\\"close\\" title=\\"close debug bar\\">&times;</a></li>', '', $html); // hide close button
		return str_replace(' data-tracy-group=\\"cli\\">', ' data-tracy-group=\\"cli\\"><li>CLI</li>', $html); // add missing cli info
	}


	private static function sendBluescreen(string $file): void
	{
		if (is_file($file)) {
			$html = file_get_contents($file);
			if ($html !== false) {
				self::send($html);
			}
		}
	}


	private static function send(string $html): void
	{
		$html = trim($html);
		$error = null;

		if (self::$serverUrl === null) {
			Server\BarData::saveNewBar($html);
		} else if (!extension_loaded('curl')) {
			$error = 'curl extension must be installed and loaded or you can use set \'serverUrl\' as null to save bar directly into the /tmp file (TracyRemoteBar client application must run on the same server as the application in this case).';
		} else {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, rtrim(self::$serverUrl, '/') . '/api/');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $html);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // To hide error HTML messages from the output
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$curlConnectTimeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::$curlTimeout);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: text/plain',
				'Content-Length: ' . strlen($html),
			]);

			curl_exec($ch);

			if (curl_errno($ch) !== CURLE_OK) {
				$error = '#' . curl_errno($ch) . ': ' . curl_error($ch);
			} else {
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($httpCode !== 200) {
					$error = '# HTTP code ' . $httpCode . ' was returned.';
				}
			}
		}

		if ($error !== null) {
			if (Debugger::$logDirectory === null) {
				echo $error . PHP_EOL;
			} else {
				file_put_contents(Debugger::$logDirectory . '/tracy-remote-bar.log', date('[Y-m-d H-i-s]') . ' ' . $error . PHP_EOL, FILE_APPEND);
			}
		}
	}

}
