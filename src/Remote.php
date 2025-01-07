<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar;

use Tracy\Debugger;

class Remote
{
	private static string|NULL $serverUrl = NULL;

	private static int $curlConnectTimeout = 1;

	private static int $curlTimeout = 1;


	public static function setServerUrl(string $serverUrl): void
	{
		self::$serverUrl = $serverUrl;
	}


	public static function isActive(): bool
	{
		return self::$serverUrl !== NULL;
	}


	public static function setCurlTimeouts(int $connectTimeout, int $timeout): void
	{
		self::$curlConnectTimeout = $connectTimeout;
		self::$curlTimeout = $timeout;
	}


	public static function addBar(string $html): void
	{
		if (!self::isActive()) {
			return;
		}

		assert(self::$serverUrl !== NULL);

		$ch = curl_init();

		$html = self::fixBarHtml($html);

		curl_setopt($ch, CURLOPT_URL, rtrim(self::$serverUrl, '/') . '/api/');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $html);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$curlConnectTimeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::$curlTimeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: text/plain',
			'Content-Length: ' . strlen($html),
		]);

		curl_exec($ch);

		if (curl_errno($ch) !== CURLE_OK) {
			Debugger::log('#' . curl_errno($ch) . ': ' . curl_error($ch), 'tracy-remote-bar');
		} else {
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($httpCode !== 200) {
				Debugger::log('# HTTP code ' . $httpCode . ' was returned.', 'tracy-remote-bar');
			}
		}
	}


	private static function fixBarHtml(string $html): string
	{
		$html = preg_replace('# src=\"(.)+_tracy_bar=#', ' src="/tracy-assets/?_tracy_bar=', $html); // correct assets URL
		assert($html !== NULL);
		$html = str_replace('<li><a href=\\"#\\" data-tracy-action=\\"close\\" title=\\"close debug bar\\">&times;</a></li>', '', $html); // hide close button
		$html = str_replace(' data-tracy-group=\\"cli\\">', ' data-tracy-group=\\"cli\\"><li>CLI</li>', $html); // add missing cli info
		return trim($html);
	}


	public static function dispatchBars(): void
	{
		if (self::isActive()) {
			Debugger::removeOutputBuffers(FALSE);
			try {
				Debugger::getStrategy()->renderBar();
			} catch (\Throwable $e) {
				Debugger::exceptionHandler($e);
			}
		}
	}

}
