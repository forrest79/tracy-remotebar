<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteBar\Server;

use Forrest79\TracyRemoteBar\Helper;

class BarData
{
	private const MAX_SAVED_BAR_COUNT = 10;

	private const DATA_FILE = 'bars.json';
	private const LOCK_FILE = 'bars.json.lock';

	/** @var resource|false */
	private $lockHandle;

	/** @var array<int, string> */
	private array $data = [];

	private static string|null $dir = null;


	public function load(): void
	{
		if ($this->lockHandle !== null) {
			throw new \RuntimeException('Data are already loaded.');
		}

		$dir = self::dir();

		$this->lockHandle = fopen($dir . '/' . self::LOCK_FILE, 'c+');
		if (($this->lockHandle === false) || !flock($this->lockHandle, LOCK_EX)) {
			throw new \RuntimeException(\sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $dir . '/' . self::LOCK_FILE));
		}

		if (file_exists($dir . '/' . self::DATA_FILE)) {
			$content = file_get_contents($dir . '/' . self::DATA_FILE);
			if ($content === false) {
				@unlink($dir . '/' . self::DATA_FILE); // intentionally @ - file may not exist
			} else {
				$data = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
				assert(is_array($data));
				$this->data = $data;
			}
		}
	}


	public function barIdRange(): string
	{
		return $this->data === [] ? '0-0' : array_key_first($this->data) . '-' . array_key_last($this->data);
	}


	public function getBar(int $id): string|null
	{
		return $this->data[$id] ?? null;
	}


	public function addBar(string $bar): void
	{
		$newId = ($this->data === [] ? 0 : array_key_last($this->data)) + 1;

		$this->data[$newId] = $bar;
	}


	public function clear(): void
	{
		$this->data = [];
	}


	public function write(): void
	{
		if ($this->lockHandle === false) {
			throw new \RuntimeException('Data are not loaded.');
		}

		file_put_contents(self::dir() . '/' . self::DATA_FILE, json_encode(array_slice($this->data, -1 * self::MAX_SAVED_BAR_COUNT, preserve_keys: true)));

		flock($this->lockHandle, LOCK_UN);
		fclose($this->lockHandle);
	}


	private static function dir(): string
	{
		if (self::$dir === null) {
			self::$dir = Helper::createTempDir('tracy-remotebar/data');
		}

		return self::$dir;
	}


	public static function saveNewBar(string $bar): void
	{
		$barData = new self();
		$barData->load();
		$barData->addBar($bar);
		$barData->write();
	}

}
