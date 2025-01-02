<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar\Server;

use Forrest79\TracyRemoteBar\Helper;

class BarData
{
	private const DATA_FILE = 'bars.json';
	private const LOCK_FILE = 'bars.json.lock';

	/** @var resource|FALSE */
	private $lockHandle;

	/** @var array<int, string> */
	private array $data = [];

	private static string|NULL $dir = NULL;


	public function load(): void
	{
		if ($this->lockHandle !== NULL) {
			throw new \RuntimeException('Data are already loaded.');
		}

		$dir = self::dir();

		$this->lockHandle = fopen($dir . '/' . self::LOCK_FILE, 'c+');
		if (($this->lockHandle === FALSE) || !flock($this->lockHandle, LOCK_EX)) {
			throw new \RuntimeException(\sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $dir . '/' . self::LOCK_FILE));
		}

		if (file_exists($dir . '/' . self::DATA_FILE)) {
			$content = file_get_contents($dir . '/' . self::DATA_FILE);
			if ($content === FALSE) {
				@unlink($dir . '/' . self::DATA_FILE); // intentionally @ - file may not exist
			} else {
				$data = json_decode($content);
				assert(is_array($data));
				$this->data = $data;
			}
		}
	}


	public function barCount(): int
	{
		return count($this->data);
	}


	public function getBar(int $id): string|NULL
	{
		return $this->data[$id - 1] ?? NULL;
	}


	public function addBar(string $bar): void
	{
		$this->data[] = self::fixBarHtml($bar);
	}


	public function clear(): void
	{
		$this->data = [];
	}


	public function write(): void
	{
		if ($this->lockHandle === FALSE) {
			throw new \RuntimeException('Data are not loaded.');
		}

		file_put_contents(self::dir() . '/' . self::DATA_FILE, json_encode($this->data));

		flock($this->lockHandle, LOCK_UN);
		fclose($this->lockHandle);
	}


	private static function fixBarHtml(string $html): string
	{
		$html = preg_replace('# src=\"(.)+_tracy_bar=#', ' src="/tracy-assets/?_tracy_bar=', $html); // correct assets URL
		assert($html !== NULL);
		$html = str_replace('<li><a href=\\"#\\" data-tracy-action=\\"close\\" title=\\"close debug bar\\">&times;</a></li>', '', $html); // hide close button
		$html = str_replace(' data-tracy-group=\\"cli\\">', ' data-tracy-group=\\"cli\\"><li>CLI</li>', $html); // add missing cli info
		return $html;
	}


	private static function dir(): string
	{
		if (self::$dir === NULL) {
			self::$dir = Helper::createTempDir('tracy-remotebar/data');
		}

		return self::$dir;
	}

}
